<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 6/11/11
	 * Time: 1:37 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class Display
	{
		public 	static function heading($msg)
			{
				echo "<center><span class='headingtext'>$msg</span></center>\n";
			}

		public 	static function note($msg, $br = 0, $br2 = 0, $extra = "")
			{
				for ($i = 0; $i < $br; $i++)
				{
					echo "<br>";
				}
				if ($extra != "") {
					echo "<center><span $extra>$msg</span></center>\n";
				}
				else
				{
					echo "<center><span class='note_msg'>$msg</span></center>\n";
				}
				for ($i = 0; $i < $br2; $i++)
				{
					echo "<br>";
				}
			}

		public 	static function item_heading($stock_id)
			{
				if ($stock_id != "") {
					$result = DB::query("SELECT description, units FROM stock_master WHERE stock_id=" . DB::escape($stock_id));
					$myrow = DB::fetch_row($result);
					static::heading("$stock_id - $myrow[0]");
					$units = $myrow[1];
					static::heading(_("in units of : ") . $units);
				}
			}

		public 	static function backtrace($cond = true, $msg = '')
			{
				if ($cond) {
					if ($msg) {
						$str = "<center><span class='headingtext'>$msg</span></center>\n";
					}
					else {
						$str = '';
					}
					$str .= '<table border=0>';
					$trace = debug_backtrace();
					foreach (
						$trace as $trn => $tr
					) {
						if (!$trn) {
							continue;
						}
						$str .= '<tr><td>';
						$str .= $tr['file'] . ':' . $tr['line'] . ': ';
						$str .= '</td><td>';
						if (isset($tr['type'])) {
							if ($tr['type'] == '::') {
								$str .= $tr['class'] . '::';
							}
							else {
								if ($tr['type'] == '->') {
									$str .= '(' . $tr['class'] . ' Object)' . '->';
								}
							}
						}
						foreach ($tr['args'] as $n => $a) {
							if (is_object($tr['args'][$n])) {
								$tr['args'][$n] = "(" . get_class($tr['args'][$n]) . " Object)";
							}
							if (is_array($tr['args'][$n])) {
								$tr['args'][$n] = "(Array[" . count($tr['args'][$n]) . "])";
							}
							else {
								$tr['args'][$n] = "'" . $tr['args'][$n] . "'";
							}
						}
						$str .= $tr['function'] . '(' . implode(',', $tr['args']) . ')</td>';
						$str .= '</tr>';
					}
					$str .= '</table>';
					Errors::error($str);
				}
			}


		public 	static function is_voided($type, $id, $label)
			{
				$void_entry = Voiding::get($type, $id);
				if ($void_entry == null) {
					return false;
				}
				start_table("width=50%  " . Config::get('tables_style'));
				echo "<tr><td align=center><font color=red>$label</font><br>";
				echo "<font color=red>" . _("Date Voided:") . " " . Dates::sql2date($void_entry["date_"]) . "</font><br>";
				if (strlen($void_entry["memo_"]) > 0) {
					echo "<center><font color=red>" . _("Memo:") . " " . $void_entry["memo_"] . "</font></center><br>";
				}
				echo "</td></tr>";
				end_table(1);
				return true;
			}


		public 	static function debit_or_credit_cells($value)
			{
				$value = Num::round($value, User::price_dec());
				if ($value >= 0) {
					amount_cell($value);
					label_cell("");
				}
				elseif ($value < 0) {
					label_cell("");
					amount_cell(abs($value));
				}
			}
	}
