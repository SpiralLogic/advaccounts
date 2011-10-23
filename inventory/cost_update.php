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
	$page_security = 'SA_STANDARDCOST';

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");

	$js = "";
	if (Config::get('ui.windows.popups'))
		$js .= ui_view::get_js_open_window(900, 500);
	page(_($help_context = "Inventory Item Cost Update"), false, false, "", $js);

	//--------------------------------------------------------------------------------------

	Validation::check(Validation::COST_ITEMS, _("There are no costable inventory items defined in the system (Purchased or manufactured items)."), STOCK_SERVICE);

	if (isset($_GET['stock_id'])) {
		$_POST['stock_id'] = $_GET['stock_id'];
	}

	//--------------------------------------------------------------------------------------
	if (isset($_POST['UpdateData'])) {

		$old_cost = $_POST['OldMaterialCost'] + $_POST['OldLabourCost']
		 + $_POST['OldOverheadCost'];
		$new_cost = input_num('material_cost') + input_num('labour_cost')
		 + input_num('overhead_cost');

		$should_update = true;

		if (!Validation::is_num('material_cost') || !Validation::is_num('labour_cost') ||
		 !Validation::is_num('overhead_cost')
		) {
			ui_msgs::display_error(_("The entered cost is not numeric."));
			ui_view::set_focus('material_cost');
			$should_update = false;
		}
		elseif ($old_cost == $new_cost)
		{
			ui_msgs::display_error(_("The new cost is the same as the old cost. Cost was not updated."));
			$should_update = false;
		}

		if ($should_update) {
			$update_no = stock_cost_update($_POST['stock_id'],
				input_num('material_cost'), input_num('labour_cost'),
				input_num('overhead_cost'), $old_cost);

			ui_msgs::display_notification(_("Cost has been updated."));

			if ($update_no > 0) {
				ui_msgs::display_note(ui_view::get_gl_view_str(ST_COSTUPDATE, $update_no, _("View the GL Journal Entries for this Cost Update")), 0, 1);
			}
		}
	}

	if (list_updated('stock_id'))
		$Ajax->activate('cost_table');
	//-----------------------------------------------------------------------------------------

	start_form();

	if (!Input::post('stock_id'))
		$_POST['stock_id'] = ui_globals::get_global_stock_item();

	echo "<center>" . _("Item:") . "&nbsp;";
	echo stock_costable_items_list('stock_id', $_POST['stock_id'], false, true);

	echo "</center><hr>";
	ui_globals::set_global_stock_item($_POST['stock_id']);

	$sql    = "SELECT description, units, material_cost, labour_cost,
	overhead_cost, mb_flag
	FROM stock_master
	WHERE stock_id=" . DBOld::escape($_POST['stock_id']) . "
	GROUP BY description, units, material_cost, labour_cost, overhead_cost, mb_flag";
	$result = DBOld::query($sql);
	Errors::check_db_error("The cost details for the item could not be retrieved", $sql);

	$myrow = DBOld::fetch($result);
	div_start('cost_table');
	hidden("OldMaterialCost", $myrow["material_cost"]);
	hidden("OldLabourCost", $myrow["labour_cost"]);
	hidden("OldOverheadCost", $myrow["overhead_cost"]);

	start_table(Config::get('tables.style2'));
	$dec1                   = $dec2 = $dec3 = 0;
	$_POST['material_cost'] = price_decimal_format($myrow["material_cost"], $dec1);
	$_POST['labour_cost']   = price_decimal_format($myrow["labour_cost"], $dec2);
	$_POST['overhead_cost'] = price_decimal_format($myrow["overhead_cost"], $dec3);

	amount_row(_("Standard Material Cost Per Unit"), "material_cost", null, "class='tableheader2'", null, $dec1);
	if ($myrow["mb_flag"] == STOCK_MANUFACTURE) {
		amount_row(_("Standard Labour Cost Per Unit"), "labour_cost", null, "class='tableheader2'", null, $dec2);
		amount_row(_("Standard Overhead Cost Per Unit"), "overhead_cost", null, "class='tableheader2'", null, $dec3);
	}
	else
	{
		hidden("labour_cost", 0);
		hidden("overhead_cost", 0);
	}

	end_table(1);
	div_end();
	submit_center('UpdateData', _("Update"), true, false, 'default');

	end_form();
	end_page();

?>
