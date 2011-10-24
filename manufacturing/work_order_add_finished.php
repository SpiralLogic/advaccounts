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
	$page_security = 'SA_MANUFRECEIVE';

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");

	include_once(APP_PATH . "gl/includes/db/gl_db_bank_trans.php");

	include_once(APP_PATH . "manufacturing/includes/manufacturing_ui.php");

	$js = "";
	if (Config::get('ui.windows.popups'))
		$js .= ui_view::get_js_open_window(900, 500);

	page(_($help_context = "Produce or Unassemble Finished Items From Work Order"), false, false, "", $js);

	if (isset($_GET['trans_no']) && $_GET['trans_no'] != "") {
		$_POST['selected_id'] = $_GET['trans_no'];
	}

	//--------------------------------------------------------------------------------------------------

	if (isset($_GET['AddedID'])) {
		include_once(APP_PATH . "reporting/includes/reporting.php");
		$id = $_GET['AddedID'];
		$stype = ST_WORKORDER;

		ui_msgs::display_notification(_("The manufacturing process has been entered."));

		ui_msgs::display_warning(ui_view::get_trans_view_str($stype, $id, _("View this Work Order")));

		ui_msgs::display_warning(ui_view::get_gl_view_str($stype, $id, _("View the GL Journal Entries for this Work Order")), 1);
		$ar = array('PARAM_0' => $_GET['date'], 'PARAM_1' => $_GET['date'], 'PARAM_2' => $stype);
		ui_msgs::display_warning(print_link(_("Print the GL Journal Entries for this Work Order"), 702, $ar), 1);

		hyperlink_no_params("search_work_orders.php", _("Select another &Work Order to Process"));

		end_page();
		exit;
	}

	//--------------------------------------------------------------------------------------------------

	$wo_details = get_work_order($_POST['selected_id']);

	if (strlen($wo_details[0]) == 0) {
		ui_msgs::display_error(_("The order number sent is not valid."));
		exit;
	}

	//--------------------------------------------------------------------------------------------------

	function can_process() {
		global $wo_details;

		if (!Refs::is_valid($_POST['ref'])) {
			ui_msgs::display_error(_("You must enter a reference."));
			ui_view::set_focus('ref');
			return false;
		}

		if (!is_new_reference($_POST['ref'], 29)) {
			ui_msgs::display_error(_("The entered reference is already in use."));
			ui_view::set_focus('ref');
			return false;
		}

		if (!Validation::is_num('quantity', 0)) {
			ui_msgs::display_error(_("The quantity entered is not a valid number or less then zero."));
			ui_view::set_focus('quantity');
			return false;
		}

		if (!Dates::is_date($_POST['date_'])) {
			ui_msgs::display_error(_("The entered date is invalid."));
			ui_view::set_focus('date_');
			return false;
		}
		elseif (!Dates::is_date_in_fiscalyear($_POST['date_']))
		{
			ui_msgs::display_error(_("The entered date is not in fiscal year."));
			ui_view::set_focus('date_');
			return false;
		}
		if (Dates::date_diff2(Dates::sql2date($wo_details["released_date"]), $_POST['date_'], "d") > 0) {
			ui_msgs::display_error(_("The production date cannot be before the release date of the work order."));
			ui_view::set_focus('date_');
			return false;
		}

		// if unassembling we need to check the qoh
		if (($_POST['ProductionType'] == 0) && !SysPrefs::allow_negative_stock()) {
			$wo_details = get_work_order($_POST['selected_id']);

			$qoh = get_qoh_on_date($wo_details["stock_id"], $wo_details["loc_code"], $_POST['date_']);
			if (-input_num('quantity') + $qoh < 0) {
				ui_msgs::display_error(_("The unassembling cannot be processed because there is insufficient stock."));
				ui_view::set_focus('quantity');
				return false;
			}
		}

		// if production we need to check the qoh of the wo requirements
		if (($_POST['ProductionType'] == 1) && !SysPrefs::allow_negative_stock()) {
			$err = false;
			$result = get_wo_requirements($_POST['selected_id']);
			while ($row = DBOld::fetch($result))
			{
				if ($row['mb_flag'] == 'D') // service, non stock
					continue;
				$qoh = get_qoh_on_date($row["stock_id"], $row["loc_code"], $_POST['date_']);
				if ($qoh - $row['units_req'] * input_num('quantity') < 0) {
					ui_msgs::display_error(_("The production cannot be processed because a required item would cause a negative inventory balance :") .
						 " " . $row['stock_id'] . " - " . $row['description']);
					$err = true;
				}
			}
			if ($err) {
				ui_view::set_focus('quantity');
				return false;
			}
		}
		return true;
	}

	//--------------------------------------------------------------------------------------------------

	if ((isset($_POST['Process']) || isset($_POST['ProcessAndClose'])) && can_process() == true) {

		$close_wo = 0;
		if (isset($_POST['ProcessAndClose']) && ($_POST['ProcessAndClose'] != ""))
			$close_wo = 1;

		// if unassembling, negate quantity
		if ($_POST['ProductionType'] == 0)
			$_POST['quantity'] = -$_POST['quantity'];

		$id = work_order_produce($_POST['selected_id'], $_POST['ref'], input_num('quantity'),
			$_POST['date_'], $_POST['memo_'], $close_wo);

		meta_forward($_SERVER['PHP_SELF'], "AddedID=" . $_POST['selected_id'] . "&date=" . $_POST['date_']);
	}

	//-------------------------------------------------------------------------------------

	display_wo_details($_POST['selected_id']);

	//-------------------------------------------------------------------------------------

	start_form();

	hidden('selected_id', $_POST['selected_id']);
	//hidden('WOReqQuantity', $_POST['WOReqQuantity']);

	$dec = get_qty_dec($wo_details["stock_id"]);
	if (!isset($_POST['quantity']) || $_POST['quantity'] == '')
		$_POST['quantity'] = qty_format(max($wo_details["units_reqd"] - $wo_details["units_issued"], 0),
			$wo_details["stock_id"], $dec);

	start_table(Config::get('tables.style2'));
	br();

	ref_row(_("Reference:"), 'ref', '', Refs::get_next(29));

	if (!isset($_POST['ProductionType']))
		$_POST['ProductionType'] = 1;

	yesno_list_row(_("Type:"), 'ProductionType', $_POST['ProductionType'],
		_("Produce Finished Items"), _("Return Items to Work Order"));

	small_qty_row(_("Quantity:"), 'quantity', null, null, null, $dec);

	date_row(_("Date:"), 'date_');

	textarea_row(_("Memo:"), 'memo_', null, 40, 3);

	end_table(1);

	submit_center_first('Process', _("Process"), '', 'default');
	submit_center_last('ProcessAndClose', _("Process And Close Order"), '', true);

	end_form();

	end_page();

?>