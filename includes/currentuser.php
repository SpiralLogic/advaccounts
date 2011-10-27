<?php
	/**********************************************************************
	Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/

	//--------------------------------------------------------------------------
	class CurrentUser {

		var $user;
		var $loginname;
		var $username;
		var $name;
		var $company = 1;
		var $pos;
		var $salesmanid = false;
		var $access;
		var $timeout;
		var $last_act;
		var $role_set = false;
		var $old_db;
		var $logged;
		var $ui_mode = 0;

		var $prefs;

		function CurrentUser() {
			$this->loginname = $this->username = $this->name = "";
			$this->company = Config::get('company_default') ? Config::get('company_default') : 1;
			$this->logged = false;
			$this->prefs = new userPrefs();
		}

		function set_salesman($salesman_code = null) {
			if ($salesman_code == null) {
				$salesman_name = $this->name;
				$sql = "SELECT salesman_code FROM salesman WHERE salesman_name = " . DBOld::escape($salesman_name);
				$query = DBOld::query($sql, 'Couldn\'t find current salesman');
				$result = DBOld::fetch_assoc($query);
				if (!empty($result['salesman_code'])) $this->salesmanid = $result['salesman_code'];
			}
			if ($salesman_code != null) $this->salesmanid = $salesman_code;
		}

		function logged_in() {
			return $this->logged;
		}

		function set_company($company) {
			$this->company = $company;
		}

		function login($company, $loginname, $password) {
			$this->set_company($company);
			$this->logged = false;

			$Auth_Result = User::get_for_login($loginname, $password);
			if (DBOld::num_rows($Auth_Result) > 0) {
				$myrow = DBOld::fetch($Auth_Result);

				if (!@$myrow["inactive"]) {
					$this->role_set = array();
					$this->access = $myrow["role_id"];
					// store area codes available for current user role
					$role = Security::get_role($this->access);
					if (!$role) return false;
					foreach ($role['areas'] as $code) // filter only area codes for enabled security sections
					{
						if (in_array($code & ~0xff, $role['sections'])) $this->role_set[] = $code;
					}
				}
				if ($myrow['change_password']) $_SESSION['change_password'] = true;
				$this->name = $myrow["real_name"];
				$this->pos = $myrow["pos"];
				$this->loginname = $loginname;
				$this->username = $this->loginname;
				$this->prefs = new userPrefs($myrow);
				$this->user = @$myrow["id"];
				User::update_visitdate($this->username);
				$this->logged = true;
				$this->last_act = time();
				$this->timeout = session_timeout();
				$this->set_salesman();
			}
			return $this->logged;
		}

		function can_access($page_level) {
			global $security_areas;

			if ($page_level === 'SA_OPEN') return true;
			if ($page_level === 'SA_DENIED' || $page_level === '') return false;
			$code = $security_areas[$page_level][0];
			// only first registered company has site admin privileges
			return $code && in_array($code, $this->role_set) && ($this->company == 1 || (($code & ~0xff) != SS_SADMIN));
		}

		function can_access_page($page_level) {
			return $this->can_access($page_level);
		}

		function get_db_connection($id = -1) {
			$id = $id == -1 ? $this->company : 1;

			$connection = Config::get('db.' . $id);
			$db = mysql_connect($connection["host"], $connection["dbuser"], $connection["dbpassword"]);
			mysql_select_db($connection["dbname"], $db);
			if (!defined('TB_PREF')) define('TB_PREF', '');
			return $db;
		}

		function update_prefs($price_dec, $qty_dec, $exrate_dec, $percent_dec, $showgl, $showcodes, $date_format, $date_sep, $tho_sep, $dec_sep, $theme, $pagesize, $show_hints, $profile, $rep_popup,
													$query_size, $graphic_links, $lang, $stickydate, $startup_tab) {
			$user = array('prices_dec' => $price_dec,
				'qty_dec' => $qty_dec,
				'rates_dec' => $exrate_dec,
				'percent_dec' => $percent_dec,
				'show_gl' => $showgl,
				'show_codes' => $showcodes,
				'date_format' => $date_format,
				'date_sep' => $date_sep,
				'tho_sep' => $tho_sep,
				'dec_sep' => $dec_sep,
				'theme' => $theme,
				'page_size' => $pagesize,
				'show_hints' => $show_hints,
				'print_profile' => $profile,
				'rep_popup' => $rep_popup,
				'query_size' => $query_size,
				'graphic_links' => $graphic_links,
				'language' => $lang,
				'sticky_doc_date' => $stickydate,
				'startup_tab' => $startup_tab
			);
			if (!Config::get('demo_mode')) {
				User::update_display_prefs($this->user, $price_dec, $qty_dec, $exrate_dec, $percent_dec, $showgl, $showcodes, $date_format, $date_sep, $tho_sep, $dec_sep, $theme, $pagesize, $show_hints,
					$profile, $rep_popup, $query_size, $graphic_links, $lang, $stickydate, $startup_tab);
			}
			$this->prefs = new userPrefs(User::get($this->user));
		}
	}

	//--------------------------------------------------------------------------
	function round2($number, $decimals = 0) {
		$delta = ($number < 0 ? -.0000000001 : .0000000001);
		//  $delta -=  pow(10,-($decimals+1));
		return round($number, $decimals, PHP_ROUND_HALF_EVEN);
	}

	function number_format2($number, $decimals = 0) {
		$tsep = Config::get('separators_thousands', $_SESSION["wa_current_user"]->prefs->tho_sep());
		$dsep = Config::get('separators_decimal', $_SESSION["wa_current_user"]->prefs->dec_sep());
		//return number_format($number, $decimals, $dsep,	$tsep);
		$delta = ($number < 0 ? -.0000000001 : .0000000001);
		$number = number_format($number + $delta, $decimals, $dsep, $tsep);
		return ($number == -0 ? 0 : $number);
	}

	//
	//	Current ui mode.
	//
	function fallback_mode() {
		return $_SESSION["wa_current_user"]->ui_mode == 0;
	}

	function price_format($number) {
		return number_format2($number, $_SESSION["wa_current_user"]->prefs->price_dec());
	}

	function price_decimal_format($number, &$dec) {
		$dec = user_price_dec();
		$str = strval($number);
		$pos = strpos($str, '.');
		if ($pos !== false) {
			$len = strlen(substr($str, $pos + 1));
			if ($len > $dec) $dec = $len;
		}
		return number_format2($number, $dec);
	}

	// function money_format doesn't exist in OS Win.
	if (!function_exists('money_format')) {
		function money_format($format, $number) {
			return price_format($number);
		}
	}
	// 2008-06-15. Added extra parameter $stock_id and reference for $dec
	//--------------------------------------------------------------------
	function qty_format($number, $stock_id = null, &$dec) {
		$dec = get_qty_dec($stock_id);
		return number_format2($number, $dec);
	}

	// and get_qty_dec
	function get_qty_dec($stock_id = null) {

		include_once(APP_PATH . "inventory/includes/db/items_units_db.php");
		if ($stock_id != null) $dec = get_unit_dec($stock_id);
		if ($stock_id == null || $dec == -1 || $dec == null) $dec = $_SESSION["wa_current_user"]->prefs->qty_dec();
		return $dec;
	}

	//-------------------------------------------------------------------
	function exrate_format($number) {
		return number_format2($number, $_SESSION["wa_current_user"]->prefs->exrate_dec());
	}

	function percent_format($number) {
		return number_format2($number, $_SESSION["wa_current_user"]->prefs->percent_dec());
	}

	function user_numeric($input) {
		$num = trim($input);
		$sep = Config::get('separators_thousands', user_tho_sep());
		if ($sep != '') $num = str_replace($sep, '', $num);
		$sep = Config::get('separators_decimal', user_dec_sep());
		if ($sep != '.') $num = str_replace($sep, '.', $num);
		if (!is_numeric($num)) return false;
		$num = (float)$num;
		if ($num == (int)$num) return (int)$num; else
			return $num;
	}

	function user_company() {
		//	return $_SESSION["wa_current_user"]->company;
		return '';
	}

	function user_pos() {
		return $_SESSION["wa_current_user"]->pos;
	}

	function user_language() {
		return $_SESSION["wa_current_user"]->prefs->language();
	}

	function user_qty_dec() {
		return $_SESSION["wa_current_user"]->prefs->qty_dec();
	}

	function user_price_dec() {
		return $_SESSION["wa_current_user"]->prefs->price_dec();
	}

	function user_exrate_dec() {
		return $_SESSION["wa_current_user"]->prefs->exrate_dec();
	}

	function user_percent_dec() {
		return $_SESSION["wa_current_user"]->prefs->percent_dec();
	}

	function user_show_gl_info() {
		return $_SESSION["wa_current_user"]->prefs->show_gl_info();
	}

	function user_show_codes() {
		return $_SESSION["wa_current_user"]->prefs->show_codes();
	}

	function user_date_format() {
		return $_SESSION["wa_current_user"]->prefs->date_format();
	}

	function user_date_display() {
		return $_SESSION["wa_current_user"]->prefs->date_display();
	}

	function user_date_sep() {
		return (isset($_SESSION["wa_current_user"])) ? $_SESSION["wa_current_user"]->prefs->date_sep() : 0;
	}

	function user_tho_sep() {
		return $_SESSION["wa_current_user"]->prefs->tho_sep();
	}

	function user_dec_sep() {
		return $_SESSION["wa_current_user"]->prefs->dec_sep();
	}

	function user_theme() {
		return $_SESSION["wa_current_user"]->prefs->get_theme();
	}

	function user_pagesize() {
		return $_SESSION["wa_current_user"]->prefs->get_pagesize();
	}

	function user_hints() {
		return $_SESSION["wa_current_user"]->prefs->show_hints();
	}

	function user_print_profile() {
		return $_SESSION["wa_current_user"]->prefs->print_profile();
	}

	function user_rep_popup() {
		return $_SESSION["wa_current_user"]->prefs->rep_popup();
	}

	function user_query_size() {
		return $_SESSION["wa_current_user"]->prefs->query_size();
	}

	function user_graphic_links() {
		return $_SESSION["wa_current_user"]->prefs->graphic_links();
	}

	function sticky_doc_date() {
		return $_SESSION["wa_current_user"]->prefs->sticky_date();
	}

	function user_startup_tab() {
		return $_SESSION["wa_current_user"]->prefs->start_up_tab();
	}

	function set_user_prefs($price_dec, $qty_dec, $exrate_dec, $percent_dec, $showgl, $showcodes, $date_format, $date_sep, $tho_sep, $dec_sep, $theme, $pagesize, $show_hints, $print_profile, $rep_popup,
													$query_size, $graphic_links, $lang, $stickydate, $startup_tab) {
		$_SESSION["wa_current_user"]->update_prefs($price_dec, $qty_dec, $exrate_dec, $percent_dec, $showgl, $showcodes, $date_format, $date_sep, $tho_sep, $dec_sep, $theme, $pagesize, $show_hints,
			$print_profile, $rep_popup, $query_size, $graphic_links, $lang, $stickydate, $startup_tab);
	}

	function add_user_js_data() {

		$js = "\nvar user = {
					 \n theme: '/themes/" . user_theme() . "/',
					 \nloadtxt: '" . _('Requesting data...') . "',
					 \ndate: '" . Dates::Today() . "',
					 \ndatesys: " . Config::get('accounts_datesystem') . ",
					 \ndatefmt: " . user_date_format() . ",
					 \ndatesep: '" . Config::get('ui_date_format') . "',
					 \nts: '" . Config::get('separators_thousands', user_tho_sep()) . "',
					 \nds: '" . Config::get('separators_decimal', user_dec_sep()) . "',
					 \npdec: " . user_price_dec() . "}\n";
		JS::beforeload($js);
	}

?>