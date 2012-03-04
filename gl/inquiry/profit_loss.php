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
	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
	$js = "";
	Page::start(_($help_context = "Profit & Loss Drilldown"), SA_GLANALYTIC);
	// Ajax updates
	if (get_post('Show')) {
		Ajax::i()->activate('pl_tbl');
	}
	if (isset($_GET["TransFromDate"])) {
		$_POST["TransFromDate"] = $_GET["TransFromDate"];
	}
	if (isset($_GET["TransToDate"])) {
		$_POST["TransToDate"] = $_GET["TransToDate"];
	}
	if (isset($_GET["Compare"])) {
		$_POST["Compare"] = $_GET["Compare"];
	}
	if (isset($_GET["AccGrp"])) {
		$_POST["AccGrp"] = $_GET["AccGrp"];
	}
	start_form();
	inquiry_controls();
	display_profit_and_loss();
	end_form();
	Page::end();
	function display_type($type, $typename, $from, $to, $begin, $end, $compare, $convert, &$dec, &$pdec, &$rep, $dimension = 0, $dimension2 = 0, $drilldown, $path_to_root = PATH_TO_ROOT) {
		global $levelptr, $k;
		$code_per_balance = 0;
		$code_acc_balance = 0;
		$per_balance_total = 0;
		$acc_balance_total = 0;
		unset($totals_arr);
		$totals_arr = array();
		//Get Accounts directly under this group/type
		$result = GL_Account::get_all(null, null, $type);
		while ($account = DB::fetch($result)) {
			$per_balance = GL_Trans::get_from_to($from, $to, $account["account_code"], $dimension, $dimension2);
			if ($compare == 2) {
				$acc_balance = GL_Trans::get_budget_from_to($begin, $end, $account["account_code"], $dimension, $dimension2);
			}
			else {
				$acc_balance = GL_Trans::get_from_to($begin, $end, $account["account_code"], $dimension, $dimension2);
			}
			if (!$per_balance && !$acc_balance) {
				continue;
			}
			if ($drilldown && $levelptr == 0) {
				$url = "<a href='" . PATH_TO_ROOT . "/gl/inquiry/gl_account_inquiry.php?TransFromDate=" . $from . "&TransToDate=" . $to
				 . "&account=" . $account['account_code'] . "'>" . $account['account_code'] . " " . $account['account_name'] . "</a>";
				start_row("class='stockmankobg'");
				label_cell($url);
				amount_cell($per_balance * $convert);
				amount_cell($acc_balance * $convert);
				amount_cell(Achieve($per_balance, $acc_balance));
				end_row();
			}
			$code_per_balance += $per_balance;
			$code_acc_balance += $acc_balance;
		}
		$levelptr = 1;
		//Get Account groups/types under this group/type
		$result = GL_Type::get_all(false, false, $type);
		while ($accounttype = DB::fetch($result)) {
			$totals_arr = display_type($accounttype["id"], $accounttype["name"], $from, $to, $begin, $end, $compare, $convert, $dec, $pdec, $rep, $dimension, $dimension2, $drilldown);
			$per_balance_total += $totals_arr[0];
			$acc_balance_total += $totals_arr[1];
		}
		//Display Type Summary if total is != 0
		if (($code_per_balance + $per_balance_total + $code_acc_balance + $acc_balance_total) != 0) {
			if ($drilldown && $type == $_POST["AccGrp"]) {
				start_row("class='inquirybg' style='font-weight:bold'");
				label_cell(_('Total') . " " . $typename);
				amount_cell(($code_per_balance + $per_balance_total) * $convert);
				amount_cell(($code_acc_balance + $acc_balance_total) * $convert);
				amount_cell(Achieve(($code_per_balance + $per_balance_total), ($code_acc_balance + $acc_balance_total)));
				end_row();
			}
			//START Patch#1 : Display only direct child types
			$acctype1 = GL_Type::get($type);
			$parent1 = $acctype1["parent"];
			if ($drilldown && $parent1 == $_POST["AccGrp"]
			) //END Patch#2
				//elseif ($drilldown && $type != $_POST["AccGrp"])
			{
				$url = "<a href='" . PATH_TO_ROOT . "/gl/inquiry/profit_loss.php?TransFromDate=" . $from . "&TransToDate=" . $to .
				 "&Compare=" . $compare . "&AccGrp=" . $type . "'>" . $typename . "</a>";
				alt_table_row_color($k);
				label_cell($url);
				amount_cell(($code_per_balance + $per_balance_total) * $convert);
				amount_cell(($code_acc_balance + $acc_balance_total) * $convert);
				amount_cell(Achieve(($code_per_balance + $per_balance_total), ($code_acc_balance + $acc_balance_total)));
				end_row();
			}
		}
		$totals_arr[0] = $code_per_balance + $per_balance_total;
		$totals_arr[1] = $code_acc_balance + $acc_balance_total;
		return $totals_arr;
	}

	function Achieve($d1, $d2) {
		if ($d1 == 0 && $d2 == 0) {
			return 0;
		}
		elseif ($d2 == 0) {
			return 999;
		}
		$ret = ($d1 / $d2 * 100.0);
		if ($ret > 999) {
			$ret = 999;
		}
		return $ret;
	}

	function inquiry_controls() {
		start_table('tablestyle_noborder');
		date_cells(_("From:"), 'TransFromDate', '', null, -30);
		date_cells(_("To:"), 'TransToDate');
		//Compare Combo
		global $sel;
		$sel = array(_("Accumulated"), _("Period Y-1"), _("Budget"));
		echo "<td>" . _("Compare to") . ":</td>\n";
		echo "<td>";
		echo array_selector('Compare', null, $sel);
		echo "</td>\n";
		submit_cells('Show', _("Show"), '', '', 'default');
		end_table();
		hidden('AccGrp');
	}

	function display_profit_and_loss() {
		global $sel;
		$dim = DB_Company::get_pref('use_dimension');
		$dimension = $dimension2 = 0;
		$from = $_POST['TransFromDate'];
		$to = $_POST['TransToDate'];
		$compare = $_POST['Compare'];
		if (isset($_POST["AccGrp"]) && (strlen($_POST['AccGrp']) > 0)) {
			$drilldown = 1;
		} // Deeper Level
		else {
			$drilldown = 0;
		} // Root level
		$dec = 0;
		$pdec = User::percent_dec();
		if ($compare == 0 || $compare == 2) {
			$end = $to;
			if ($compare == 2) {
				$begin = $from;
			}
			else {
				$begin = Dates::begin_fiscalyear();
			}
		}
		elseif ($compare == 1) {
			$begin = Dates::add_months($from, -12);
			$end = Dates::add_months($to, -12);
		}
		Display::div_start('pl_tbl');
		start_table('tablestyle width50');
		$tableheader = "<tr>
 <td class='tablehead'>" . _("Group/Account Name") . "</td>
 <td class='tablehead'>" . _("Period") . "</td>
		<td class='tablehead'>" . $sel[$compare] . "</td>
		<td class='tablehead'>" . _("Achieved %") . "</td>
 </tr>";
		if (!$drilldown) //Root Level
		{
			$parent = -1;
			$classper = $classacc = $salesper = $salesacc = 0.0;
			//Get classes for PL
			$classresult = GL_Class::get_all(false, 0);
			while ($class = DB::fetch($classresult)) {
				$class_per_total = 0;
				$class_acc_total = 0;
				$convert = Systypes::get_class_type_convert($class["ctype"]);
				//Print Class Name
				table_section_title($class["class_name"], 4);
				echo $tableheader;
				//Get Account groups/types under this group/type
				$typeresult = GL_Type::get_all(false, $class['cid'], -1);
				while ($accounttype = DB::fetch($typeresult)) {
					$TypeTotal = display_type($accounttype["id"], $accounttype["name"], $from, $to, $begin, $end, $compare, $convert, $dec, $pdec, $rep, $dimension, $dimension2, $drilldown);
					$class_per_total += $TypeTotal[0];
					$class_acc_total += $TypeTotal[1];
					if ($TypeTotal[0] != 0 || $TypeTotal[1] != 0) {
						$url = "<a href='" . PATH_TO_ROOT . "/gl/inquiry/profit_loss.php?TransFromDate=" . $from . "&TransToDate=" . $to .
						 "&Compare=" . $compare . "&AccGrp=" . $accounttype['id'] . "'>" . $accounttype['name'] . "</a>";
						alt_table_row_color($k);
						label_cell($url);
						amount_cell($TypeTotal[0] * $convert);
						amount_cell($TypeTotal[1] * $convert);
						amount_cell(Achieve($TypeTotal[0], $TypeTotal[1]));
						end_row();
					}
				}
				//Print Class Summary
				start_row("class='inquirybg' style='font-weight:bold'");
				label_cell(_('Total') . " " . $class["class_name"]);
				amount_cell($class_per_total * $convert);
				amount_cell($class_acc_total * $convert);
				amount_cell(Achieve($class_per_total, $class_acc_total));
				end_row();
				$salesper += $class_per_total;
				$salesacc += $class_acc_total;
			}
			start_row("class='inquirybg' style='font-weight:bold'");
			label_cell(_('Calculated Return'));
			amount_cell($salesper * -1);
			amount_cell($salesacc * -1);
			amount_cell(achieve($salesper, $salesacc));
			end_row();
		}
		else {
			//Level Pointer : Global variable defined in order to control display of root
			global $levelptr;
			$levelptr = 0;
			$accounttype = GL_Type::get($_POST["AccGrp"]);
			$classid = $accounttype["class_id"];
			$class = GL_Class::get($classid);
			$convert = Systypes::get_class_type_convert($class["ctype"]);
			//Print Class Name
			table_section_title(GL_Type::get_name($_POST["AccGrp"]), 4);
			echo $tableheader;
			$classtotal = display_type($accounttype["id"], $accounttype["name"], $from, $to, $begin, $end, $compare, $convert, $dec, $pdec, $rep, $dimension, $dimension2, $drilldown);
		}
		end_table(1); // outer table
		Display::div_end();
	}

?>
