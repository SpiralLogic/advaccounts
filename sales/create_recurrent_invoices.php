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
	JS::open_window(900, 600);
	Page::start(_($help_context = "Create and Print Recurrent Invoices"), SA_SALESINVOICE);
	if (isset($_GET['recurrent'])) {
		$date = Dates::Today();
		if (Dates::is_date_in_fiscalyear($date)) {
			$invs = array();
			$sql = "SELECT * FROM recurrent_invoices WHERE id=" . DB::escape($_GET['recurrent']);
			$result = DB::query($sql, "could not get recurrent invoice");
			$myrow = DB::fetch($result);
			if ($myrow['debtor_no'] == 0) {
				$cust = Sales_Branch::get_from_group($myrow['group_no']);
				while ($row = DB::fetch($cust)) {
					$invs[] = create_recurrent_invoices($row['debtor_no'], $row['branch_id'], $myrow['order_no'], $myrow['id']);
				}
			}
			else {
				$invs[] = create_recurrent_invoices($myrow['debtor_no'], $myrow['group_no'], $myrow['order_no'], $myrow['id']);
			}
			if (count($invs) > 0) {
				$min = min($invs);
				$max = max($invs);
			}
			else {
				$min = $max = 0;
			}
			Event::notice(sprintf(_("%s recurrent invoice(s) created, # $min - # $max."), count($invs)));
			if (count($invs) > 0) {
				$ar = array(
					'PARAM_0' => $min . "-" . ST_SALESINVOICE,
					'PARAM_1' => $max . "-" . ST_SALESINVOICE,
					'PARAM_2' => "",
					'PARAM_3' => 0,
					'PARAM_4' => 0,
					'PARAM_5' => "",
					'PARAM_6' => ST_SALESINVOICE
				);
				Event::warning(Reporting::print_link(_("&Print Recurrent Invoices # $min - # $max"), 107, $ar), 0, 1);
				$ar['PARAM_3'] = 1;
				Event::warning(Reporting::print_link(_("&Email Recurrent Invoices # $min - # $max"), 107, $ar), 0, 1);
			}
		}
		else {
			Event::error(_("The entered date is not in fiscal year."));
		}
	}
	$sql = "SELECT * FROM recurrent_invoices ORDER BY description, group_no, debtor_no";
	$result = DB::query($sql, "could not get recurrent invoices");
	start_table('tablestyle width70');
	$th = array(
		_("Description"), _("Template No"), _("Customer"), _("Branch") . "/" . _("Group"), _("Days"), _("Monthly"), _("Begin"), _("End"), _("Last Created"), ""
	);
	table_header($th);
	$k = 0;
	$today = Dates::add_days(Dates::Today(), 1);
	$due = false;
	while ($myrow = DB::fetch($result)) {
		$begin = Dates::sql2date($myrow["begin"]);
		$end = Dates::sql2date($myrow["end"]);
		$last_sent = Dates::sql2date($myrow["last_sent"]);
		if ($myrow['monthly'] > 0) {
			$due_date = Dates::begin_month($last_sent);
		}
		else {
			$due_date = $last_sent;
		}
		$due_date = Dates::add_months($due_date, $myrow['monthly']);
		$due_date = Dates::add_days($due_date, $myrow['days']);
		$overdue = Dates::date1_greater_date2($today, $due_date) && Dates::date1_greater_date2($today, $begin) && Dates::date1_greater_date2($end, $today);
		if ($overdue) {
			start_row("class='overduebg'");
			$due = true;
		}
		else {
			alt_table_row_color($k);
		}
		label_cell($myrow["description"]);
		label_cell(Debtor::trans_view(30, $myrow["order_no"]));
		if ($myrow["debtor_no"] == 0) {
			label_cell("");
			label_cell(get_sales_group_name($myrow["group_no"]));
		}
		else {
			label_cell(Debtor::get_name($myrow["debtor_no"]));
			label_cell(Sales_Branch::get_name($myrow['group_no']));
		}
		label_cell($myrow["days"]);
		label_cell($myrow['monthly']);
		label_cell($begin);
		label_cell($end);
		label_cell($last_sent);
		if ($overdue) {
			label_cell("<a href='/sales/create_recurrent_invoices.php?recurrent=" . $myrow["id"] . "'>" . _("Create Invoices") . "</a>");
		}
		else {
			label_cell("");
		}
		end_row();
	}
	end_table();
	if ($due) {
		Event::warning(_("Marked items are due."), 1, 0, "class='overduefg'");
	}
	else {
		Event::warning(_("No recurrent invoices are due."), 1, 0);
	}
	echo '<br>';
	Page::end();
	function set_last_sent($id, $date) {
		$date = Dates::date2sql($date);
		$sql = "UPDATE recurrent_invoices SET last_sent='$date' WHERE id=" . DB::escape($id);
		DB::query($sql, "The recurrent invoice could not be updated or added");
	}

	function create_recurrent_invoices($customer_id, $branch_id, $order_no, $tmpl_no) {
		$doc = new Sales_Order(ST_SALESORDER, array($order_no));
		$doc->customer_to_order($customer_id, $branch_id);
		$doc->trans_type = ST_SALESORDER;
		$doc->trans_no = 0;
		$doc->document_date = Dates::Today(); // 2006-06-15. Added so Invoices and Deliveries get current day
		$doc->due_date = Sales_Order::get_invoice_duedate($doc->customer_id, $doc->document_date);
		$doc->reference = Ref::get_next($doc->trans_type);
		//$doc->Comments='';
		foreach ($doc->line_items as $line_no => $item) {
			$line = &$doc->line_items[$line_no];
			$line->price = Item_Price::get_calculated_price($line->stock_id, $doc->customer_currency, $doc->sales_type, $doc->price_factor, $doc->document_date);
		}
		$order = $doc;
		$order->trans_type = ST_SALESINVOICE;
		$order->reference = Ref::get_next($order->trans_type);
		$invno = $order->write(1);
		set_last_sent($tmpl_no, $order->document_date);
		return $invno;
	}

	function get_sales_group_name($group_no) {
		$sql = "SELECT description FROM groups WHERE id = " . DB::escape($group_no);
		$result = DB::query($sql, "could not get group");
		$row = DB::fetch($result);
		return $row[0];
	}

?>
