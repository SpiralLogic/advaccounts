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
	$page_security = 'SA_SALESTRANSVIEW';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	$help_context = "";
	JS::open_window(900, 600);
	$trans_type = $_GET['trans_type'];
	Page::start(_($help_context), true);
	if (isset($_GET["trans_no"])) {
		$trans_id = $_GET["trans_no"];
	}
	if (isset($_POST)) {
		unset($_POST);
	}
	$receipt = Sales_Trans::get($trans_id, $trans_type);
	echo "<br>";
	start_table(Config::get('tables_style2') . "  width=90%");
	echo "<tr valign=top class='tableheader2'><th colspan=6>";
	if ($trans_type == ST_CUSTPAYMENT) {
		Display::heading(sprintf(_("Customer Payment #%d"), $trans_id));
	} else {
		Display::heading(sprintf(_("Customer Refund #%d"), $trans_id));
	}
	echo "</td></tr>";
	start_row();
	label_cells(_("From Customer"), $receipt['DebtorName'], 'class="label"');
	label_cells(_("Into Bank Account"), $receipt['bank_account_name'], 'class="label"');
	label_cells(_("Date of Deposit"), Dates::sql2date($receipt['tran_date']), 'class="label"');
	end_row();
	start_row();
	label_cells(_("Payment Currency"), $receipt['curr_code'], 'class="label"');
	label_cells(_("Amount"), Num::price_format($receipt['Total'] - $receipt['ov_discount']), 'class="label"');
	label_cells(_("Discount"), Num::price_format($receipt['ov_discount']), 'class="label"');
	end_row();
	start_row();
	label_cells(_("Payment Type"), $bank_transfer_types[$receipt['BankTransType']], 'class="label"');
	label_cells(_("Reference"), $receipt['reference'], 'class="label" colspan=1');
	end_form();
	end_row();
	DB_Comments::display_row($trans_type, $trans_id);
	end_table(1);
	$voided = Display::is_voided($trans_type, $trans_id, _("This customer payment has been voided."));
	if (!$voided && ($trans_type != ST_CUSTREFUND)) {
		GL_Allocation::display(PT_CUSTOMER, $receipt['debtor_no'], ST_CUSTPAYMENT, $trans_id, $receipt['Total']);
	}
	if (Input::get('popup')) {
		return;
	}
	submenu_print(_("&Print This Receipt"), $trans_type, $_GET['trans_no'], 'prtopt');
	end_page(true);
?>