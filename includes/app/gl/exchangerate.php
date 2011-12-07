<!--suppress ALL -->
 <?php
	/**********************************************************************
	Copyright (C) Advanced Group PTY LTD
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	class GL_ExchangeRate
	{

		public static function get($rate_id)
			{
				$sql = "SELECT * FROM exchange_rates WHERE id=" . DB::escape($rate_id);
				$result = DB::query($sql, "could not get exchange rate for $rate_id");
				return DB::fetch($result);
			}

		// Retrieves buy exchange rate for given currency/date, zero if no result
		public static function get_date($curr_code, $date_)
			{
				$date = Dates::date2sql($date_);
				$sql = "SELECT rate_buy FROM exchange_rates WHERE curr_code=" . DB::escape($curr_code)
				 . " AND date_='$date'";
				$result = DB::query($sql, "could not get exchange rate for $curr_code - $date_");
				if (DB::num_rows($result) == 0) {
					return 0;
				}
				$row = DB::fetch($result);
				return $row[0];
			}


		public static function update($curr_code, $date_, $buy_rate, $sell_rate)
			{
				if (Bank_Currency::is_company($curr_code)) {
					Errors::show_db_error("Exchange rates cannot be set for company currency", "", true);
				}
				$date = Dates::date2sql($date_);
				$sql = "UPDATE exchange_rates SET rate_buy=$buy_rate, rate_sell=" . DB::escape($sell_rate)
				 . " WHERE curr_code=" . DB::escape($curr_code) . " AND date_='$date'";
				DB::query($sql, "could not add exchange rate for $curr_code");
			}


		public static function add($curr_code, $date_, $buy_rate, $sell_rate)
			{
				if (Bank_Currency::is_company($curr_code)) {
					Errors::show_db_error("Exchange rates cannot be set for company currency", "", true);
				}
				$date = Dates::date2sql($date_);
				$sql = "INSERT INTO exchange_rates (curr_code, date_, rate_buy, rate_sell)
		VALUES (" . DB::escape($curr_code) . ", '$date', " . DB::escape($buy_rate)
				 . ", " . DB::escape($sell_rate) . ")";
				DB::query($sql, "could not add exchange rate for $curr_code");
			}


		public static function delete($rate_id)
			{
				$sql = "DELETE FROM exchange_rates WHERE id=" . DB::escape($rate_id);
				DB::query($sql, "could not delete exchange rate $rate_id");
			}


		//	Retrieve exchange rate as of date $date from external source (usually inet)
		//
		public static function retrieve($curr_b, $date)
			{
				global $Hooks;
				if (method_exists($Hooks, 'retrieve_exrate')) {
					return $Hooks->retrieve_exrate($curr_b, $date);
				}
				else
				{
					return static::get_external($curr_b, 'ECB', $date);
				}
			}


		public static function get_external($curr_b, $provider = 'ECB', $date)
			{
				$curr_a = DB_Company::get_pref('curr_default');
				if ($provider == 'ECB') {
					$filename = "/stats/eurofxref/eurofxref-daily.xml";
					$site = "www.ecb.int";
				}
				elseif ($provider == 'YAHOO')
				{
					$filename = "/q?s={$curr_a}{$curr_b}=X";
					$site = "finance.yahoo.com";
				}
				elseif ($provider == 'GOOGLE')
				{
					$filename = "/finance/converter?a=1&from={$curr_a}&to={$curr_b}";
					$site = "finance.google.com";
				}
				$contents = '';
				if (function_exists('curl_init')) { // first check with curl as we can set short timeout;
					$retry = 1;
					do {
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, 'http://' . $site . $filename);
						curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
						curl_setopt($ch, CURLOPT_TIMEOUT, 3);
						$contents = curl_exec($ch);
						curl_close($ch);
						// due to resolver bug in some curl versions (e.g. 7.15.5)
						// try again for constant IP.
						$site = "195.128.2.97";
					} while (($contents == '') && $retry--);
				} else {
					$handle = @fopen("http://" . $site . $filename, 'rb');
					if ($handle) {
						do
						{
							$data = @fread($handle, 4096);
							if (strlen($data) == 0) {
								break;
							}
							$contents .= $data; // with this syntax only text will be translated, whole text with htmlspecialchars($data)
						}
						while (true);
						@fclose($handle);
					} // end handle
				}
				if (!$contents) {
					Errors::warning(_("Cannot retrieve currency rate from $provider page. Please set the rate manually."));
				}
				if ($provider == 'ECB') {
					$contents = str_replace("<Cube currency='USD'", " <Cube currency='EUR' rate='1'/> <Cube currency='USD'", $contents);
					$from_mask = "|<Cube\s*currency=\'" . $curr_a . "\'\s*rate=\'([\d.,]*)\'\s*/>|i";
					preg_match($from_mask, $contents, $out);
					$val_a = isset($out[1]) ? $out[1] : 0;
					$val_a = str_replace(',', '', $val_a);
					$to_mask = "|<Cube\s*currency=\'" . $curr_b . "\'\s*rate=\'([\d.,]*)\'\s*/>|i";
					preg_match($to_mask, $contents, $out);
					$val_b = isset($out[1]) ? $out[1] : 0;
					$val_b = str_replace(',', '', $val_b);
					if ($val_b) {
						$val = $val_a / $val_b;
					} else {
						$val = 0;
					}
				}
				elseif ($provider == 'YAHOO')
				{
					$val = '';
					if (preg_match('/Last\sTrade:(.*?)Trade\sTime/s', $contents, $matches)) {
						$val = strip_tags($matches[1]);
						$val = str_replace(',', '', $val);
						if ($val != 0) {
							$val = 1 / $val;
						}
					}
				}
				elseif ($provider == 'GOOGLE')
				{
					$val = '';
					$regexp = "%([\d|.]+)\s+{$curr_a}\s+=\s+<span\sclass=(.*)>([\d|.]+)\s+{$curr_b}\s*</span>%s";
					if (preg_match($regexp, $contents, $matches)) {
						$val = $matches[3];
						$val = str_replace(',', '', $val);
						if ($val != 0) {
							$val = 1 / $val;
						}
					}
				}
				return $val;
			} /* end function get_extern_rate */

		// Displays currency exchange rate for given date.
		// When there is no exrate for today,
		// gets it form ECB and stores in local database.
		//
		public	static function display($from_currency, $to_currency, $date_, $edit_rate = false)
			{
				$Ajax = Ajax::i();
				if ($from_currency != $to_currency) {
					$comp_currency = Bank_Currency::for_company();
					if ($from_currency == $comp_currency) {
						$currency = $to_currency;
					}
					else {
						$currency = $from_currency;
					}
					$rate = 0;
					if ($date_ == Dates::Today()) {
						$rate = GL_ExchangeRate::get_date($currency, $date_);
						if (!$rate) {
							$row = GL_Currency::get($currency);
							if ($row['auto_update']) {
								$rate = GL_ExchangeRate::retrieve($currency, $date_);
								if ($rate) {
									GL_ExchangeRate::add($currency, $date_, $rate, $rate);
								}
							}
						}
					}
					if (!$rate) {
						$rate = Bank_Currency::exchange_rate_from_home($currency, $date_);
					}
					if ($from_currency != $comp_currency) {
						$rate = 1 / ($rate / Bank_Currency::exchange_rate_from_home($to_currency, $date_));
					}
					$rate = Num::format($rate, User::exrate_dec());
					if ($edit_rate) {
						text_cells(_("Exchange Rate:"), '_ex_rate', $rate, 8, 8, null, "class='label'", " $from_currency = 1 $to_currency");
					}
					else {
						label_cells(_("Exchange Rate:"),
							"<span style='vertical-align:top;' id='_ex_rate'>$rate</span> $from_currency = 1 $to_currency",
							'');
					}
					$Ajax->addUpdate('_ex_rate', '_ex_rate', $rate);
				}
			}
	}