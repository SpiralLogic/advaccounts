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
	$page_security = 'SA_SALESANALYTIC';
	// ----------------------------------------------------------------
	// $ Revision:	2.0 $
	// Creator:	Joe Hunt
	// date_:	2005-05-19
	// Title:	Inventory Sales Report
	// ----------------------------------------------------------------

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");

	include_once(APP_PATH . "inventory/includes/db/items_category_db.inc");

	//----------------------------------------------------------------------------------------------------

	print_inventory_sales();

	function getTransactions($category, $location, $fromcust, $from, $to) {
		$from = Dates::date2sql($from);
		$to = Dates::date2sql($to);
		$sql = "SELECT stock_master.category_id,
			stock_category.description AS cat_description,
			stock_master.stock_id,
			stock_master.description, stock_master.inactive,
			stock_moves.loc_code,
			debtor_trans.debtor_no,
			debtors_master.name AS debtor_name,
			stock_moves.tran_date,
			SUM(-stock_moves.qty) AS qty,
			SUM(-stock_moves.qty*stock_moves.price*(1-stock_moves.discount_percent)) AS amt,
			SUM(-stock_moves.qty *(stock_master.material_cost + stock_master.labour_cost + stock_master.overhead_cost)) AS cost
		FROM stock_master,
			stock_category,
			debtor_trans,
			debtors_master,
			stock_moves
		WHERE stock_master.stock_id=stock_moves.stock_id
		AND stock_master.category_id=stock_category.category_id
		AND debtor_trans.debtor_no=debtors_master.debtor_no
		AND stock_moves.type=debtor_trans.type
		AND stock_moves.trans_no=debtor_trans.trans_no
		AND stock_moves.tran_date>='$from'
		AND stock_moves.tran_date<='$to'
		AND ((debtor_trans.type=" . ST_CUSTDELIVERY . " AND debtor_trans.version=1) OR stock_moves.type=" . ST_CUSTCREDIT . ")
		AND (stock_master.mb_flag='" . STOCK_PURCHASED . "' OR stock_master.mb_flag='" . STOCK_MANUFACTURE . "')";
		if ($category != 0)
			$sql .= " AND stock_master.category_id = " . DBOld::escape($category);
		if ($location != 'all')
			$sql .= " AND stock_moves.loc_code = " . DBOld::escape($location);
		if ($fromcust != -1)
			$sql .= " AND debtors_master.debtor_no = " . DBOld::escape($fromcust);
		$sql .= " GROUP BY stock_master.stock_id, debtors_master.name ORDER BY stock_master.category_id,
			stock_master.stock_id, debtors_master.name";
		return DBOld::query($sql, "No transactions were returned");
	}

	//----------------------------------------------------------------------------------------------------

	function print_inventory_sales() {

		$from = $_POST['PARAM_0'];
		$to = $_POST['PARAM_1'];
		$category = $_POST['PARAM_2'];
		$location = $_POST['PARAM_3'];
		$fromcust = $_POST['PARAM_4'];
		$comments = $_POST['PARAM_5'];
		$destination = $_POST['PARAM_6'];
		if ($destination)
			include_once(APP_PATH . "reporting/includes/excel_report.inc");
		else
			include_once(APP_PATH . "reporting/includes/pdf_report.inc");

		$dec = user_price_dec();

		if ($category == ALL_NUMERIC)
			$category = 0;
		if ($category == 0)
			$cat = _('All');
		else
			$cat = get_category_name($category);

		if ($location == ALL_TEXT)
			$location = 'all';
		if ($location == 'all')
			$loc = _('All');
		else
			$loc = get_location_name($location);

		if ($fromcust == ALL_NUMERIC)
			$fromc = _('All');
		else
			$fromc = get_customer_name($fromcust);

		$cols = array(0, 75, 175, 250, 300, 375, 450, 515);

		$headers =
		 array(_('Category'), _('Description'), _('Customer'), _('Qty'), _('Sales'), _('Cost'), _('Contribution'));
		if ($fromcust != ALL_NUMERIC)
			$headers[2] = '';

		$aligns = array('left', 'left', 'left', 'right', 'right', 'right', 'right');

		$params = array(0 => $comments,
			1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
			2 => array('text' => _('Category'), 'from' => $cat, 'to' => ''),
			3 => array('text' => _('Location'), 'from' => $loc, 'to' => ''),
			4 => array('text' => _('Customer'), 'from' => $fromc, 'to' => '')
		);

		$rep = new FrontReport(_('Inventory Sales Report'), "InventorySalesReport", user_pagesize());

		$rep->Font();
		$rep->Info($params, $cols, $headers, $aligns);
		$rep->Header();

		$res = getTransactions($category, $location, $fromcust, $from, $to);
		$total = $grandtotal = 0.0;
		$total1 = $grandtotal1 = 0.0;
		$total2 = $grandtotal2 = 0.0;
		$catt = '';
		while ($trans = DBOld::fetch($res))
		{
			if ($catt != $trans['cat_description']) {
				if ($catt != '') {
					$rep->NewLine(2, 3);
					$rep->TextCol(0, 4, _('Total'));
					$rep->AmountCol(4, 5, $total, $dec);
					$rep->AmountCol(5, 6, $total1, $dec);
					$rep->AmountCol(6, 7, $total2, $dec);
					$rep->Line($rep->row - 2);
					$rep->NewLine();
					$rep->NewLine();
					$total = $total1 = $total2 = 0.0;
				}
				$rep->TextCol(0, 1, $trans['category_id']);
				$rep->TextCol(1, 6, $trans['cat_description']);
				$catt = $trans['cat_description'];
				$rep->NewLine();
			}

			$curr = Banking::get_customer_currency($trans['debtor_no']);
			$rate = Banking::get_exchange_rate_from_home_currency($curr, Dates::sql2date($trans['tran_date']));
			$trans['amt'] *= $rate;
			$cb = $trans['amt'] - $trans['cost'];
			$rep->NewLine();
			$rep->fontsize -= 2;
			$rep->TextCol(0, 1, $trans['stock_id']);
			if ($fromcust == ALL_NUMERIC) {
				$rep->TextCol(1, 2,
				 $trans['description'] . ($trans['inactive'] == 1 ? " (" . _("Inactive") . ")" : ""), -1);
				$rep->TextCol(2, 3, $trans['debtor_name']);
			}
			else
				$rep->TextCol(1, 3,
				 $trans['description'] . ($trans['inactive'] == 1 ? " (" . _("Inactive") . ")" : ""), -1);
			$rep->AmountCol(3, 4, $trans['qty'], get_qty_dec($trans['stock_id']));
			$rep->AmountCol(4, 5, $trans['amt'], $dec);
			$rep->AmountCol(5, 6, $trans['cost'], $dec);
			$rep->AmountCol(6, 7, $cb, $dec);
			$rep->fontsize += 2;
			$total += $trans['amt'];
			$total1 += $trans['cost'];
			$total2 += $cb;
			$grandtotal += $trans['amt'];
			$grandtotal1 += $trans['cost'];
			$grandtotal2 += $cb;
		}
		$rep->NewLine(2, 3);
		$rep->TextCol(0, 4, _('Total'));
		$rep->AmountCol(4, 5, $total, $dec);
		$rep->AmountCol(5, 6, $total1, $dec);
		$rep->AmountCol(6, 7, $total2, $dec);
		$rep->Line($rep->row - 2);
		$rep->NewLine();
		$rep->NewLine(2, 1);
		$rep->TextCol(0, 4, _('Grand Total'));
		$rep->AmountCol(4, 5, $grandtotal, $dec);
		$rep->AmountCol(5, 6, $grandtotal1, $dec);
		$rep->AmountCol(6, 7, $grandtotal2, $dec);

		$rep->Line($rep->row - 4);
		$rep->NewLine();
		$rep->End();
	}

?>