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
	$page_security = 'SA_SALESTRANSVIEW';

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");

	include_once(APP_PATH . "reporting/includes/reporting.php");
	$help_context = $js = "";
	if (Config::get('ui.windows.popups'))
		$js .= ui_view::get_js_open_window(900, 600);
	$trans_type = $_GET['trans_type'];
	Renderer::page(_($help_context), true, false, "", $js);

	if (isset($_GET["trans_no"])) {
		$trans_id = $_GET["trans_no"];
	}

	if (isset($_POST)) {
		unset($_POST);
	}
	$receipt = get_customer_trans($trans_id, $trans_type);
	if ($trans_type == ST_CUSTPAYMENT) {
		ui_msgs::display_heading(sprintf(_("Customer Payment #%d"), $trans_id));
	}
	else {
		ui_msgs::display_heading(sprintf(_("Customer Refund #%d"), $trans_id));
	}

	echo "<br>";
	start_table(Config::get('tables.style') . "  width=90%");
	start_row();
	start_form();

	label_cells(_("From Customer"), $receipt['DebtorName'], "class='tableheader2'");

	label_cells(_("Into Bank Account"), $receipt['bank_account_name'], "class='tableheader2'");

	label_cells(_("Date of Deposit"), Dates::sql2date($receipt['tran_date']), "class='tableheader2'");
	end_row();
	start_row();
	label_cells(_("Payment Currency"), $receipt['curr_code'], "class='tableheader2'");
	label_cells(_("Amount"), price_format($receipt['Total'] - $receipt['ov_discount']), "class='tableheader2'");
	label_cells(_("Discount"), price_format($receipt['ov_discount']), "class='tableheader2'");
	end_row();
	start_row();
	label_cells(_("Payment Type"),
		$bank_transfer_types[$receipt['BankTransType']], "class='tableheader2'");
	label_cells(_("Reference"), $receipt['reference'], "class='tableheader2'", "colspan=4");
	end_form();
	end_row();
	ui_view::comments_display_row($trans_type, $trans_id);

	end_table(1);

	$voided = ui_view::is_voided_display($trans_type, $trans_id, _("This customer payment has been voided."));

	if (!$voided && ($trans_type != ST_CUSTREFUND)) {
		ui_view::display_allocations_from(PT_CUSTOMER, $receipt['debtor_no'], ST_CUSTPAYMENT, $trans_id, $receipt['Total']);
	}
	submenu_print(_("&Print This Receipt"), $trans_type, $_GET['trans_no'], 'prtopt');
	Renderer::end_page(true);
?>