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


Page::set_security(SA_SALESBULKREP);

	print_order_status_list();
  /**
   * @param      $from
   * @param      $to
   * @param int  $category
   * @param null $location
   * @param int  $backorder
   *
   * @return null|PDOStatement
   */
  function GetSalesOrders($from, $to, $category = 0, $location = NULL, $backorder = 0)
	{
		$fromdate = Dates::date2sql($from);
		$todate = Dates::date2sql($to);
		$sql
		 = "SELECT sales_orders.order_no,
				sales_orders.debtor_no,
 sales_orders.branch_id,
 sales_orders.customer_ref,
 sales_orders.ord_date,
 sales_orders.from_stk_loc,
 sales_orders.delivery_date,
 sales_order_details.stk_code,
 stock_master.description,
 stock_master.units,
 sales_order_details.quantity,
 sales_order_details.qty_sent
 FROM sales_orders
 	INNER JOIN sales_order_details
 	 ON (sales_orders.order_no = sales_order_details.order_no
 	 AND sales_orders.trans_type = sales_order_details.trans_type
 	 AND sales_orders.trans_type = " . ST_SALESORDER . ")
 	INNER JOIN stock_master
 	 ON sales_order_details.stk_code = stock_master.stock_id
 WHERE sales_orders.ord_date >='$fromdate'
 AND sales_orders.ord_date <='$todate'";
		if ($category > 0) {
			$sql .= " AND stock_master.category_id=" . DB::escape($category);
		}
		if ($location != NULL) {
			$sql .= " AND sales_orders.from_stk_loc=" . DB::escape($location);
		}
		if ($backorder) {
			$sql .= " AND sales_order_details.quantity - sales_order_details.qty_sent > 0";
		}
		$sql .= " ORDER BY sales_orders.order_no";
		return DB::query($sql, "Error getting order details");
	}


	function print_order_status_list()
	{
		$from = $_POST['PARAM_0'];
		$to = $_POST['PARAM_1'];
		$category = $_POST['PARAM_2'];
		$location = $_POST['PARAM_3'];
		$backorder = $_POST['PARAM_4'];
		$comments = $_POST['PARAM_5'];
		$destination = $_POST['PARAM_6'];
		if ($destination) {
			include_once(APPPATH . "reports/excel.php");
		}
		else
		{
			include_once(APPPATH . "reports/pdf.php");
		}
		if ($category == ALL_NUMERIC) {
			$category = 0;
		}
		if ($location == ALL_TEXT) {
			$location = NULL;
		}
		if ($category == 0) {
			$cat = _('All');
		}
		else
		{
			$cat = Item_Category::get_name($category);
		}
		if ($location == NULL) {
			$loc = _('All');
		}
		else
		{
			$loc = Inv_Location::get_name($location);
		}
		if ($backorder == 0) {
			$back = _('All Orders');
		}
		else
		{
			$back = _('Back Orders Only');
		}
		$cols = array(0, 60, 150, 260, 325, 385, 450, 515);
		$headers2 = array(
			_('Order'), _('Customer'), _('Branch'), _('Customer Ref'),
			_('Ord Date'), _('Del Date'), _('Loc')
		);
		$aligns = array('left', 'left', 'right', 'right', 'right', 'right', 'right');
		$headers = array(
			_('Code'), _('Description'), _('Ordered'), _('Invoiced'),
			_('Outstanding'), ''
		);
		$params = array(
			0 => $comments,
			1 => array(
				'text' => _('Period'),
				'from' => $from,
				'to' => $to),
			2 => array(
				'text' => _('Category'),
				'from' => $cat,
				'to' => ''),
			3 => array(
				'text' => _('location'),
				'from' => $loc,
				'to' => ''),
			4 => array(
				'text' => _('Selection'),
				'from' => $back,
				'to' => '')
		);
		$cols2 = $cols;
		$aligns2 = $aligns;
		$rep = new ADVReport(_('Order Status Listing'), "OrderStatusListing", User::pagesize());
		$rep->Font();
		$rep->Info($params, $cols, $headers, $aligns, $cols2, $headers2, $aligns2);
		$rep->Header();
		$orderno = 0;
		$result = GetSalesOrders($from, $to, $category, $location, $backorder);
		while ($myrow = DB::fetch($result))
		{
			if ($rep->row < $rep->bottomMargin + (2 * $rep->lineHeight)) {
				$orderno = 0;
				$rep->Header();
			}
			$rep->NewLine(0, 2, FALSE, $orderno);
			if ($orderno != $myrow['order_no']) {
				if ($orderno != 0) {
					$rep->Line($rep->row);
					$rep->NewLine();
				}
				$rep->TextCol(0, 1, $myrow['order_no']);
				$rep->TextCol(1, 2, Debtor::get_name($myrow['debtor_no']));
				$rep->TextCol(2, 3, Sales_Branch::get_name($myrow['branch_id']));
				$rep->TextCol(3, 4, $myrow['customer_ref']);
				$rep->DateCol(4, 5, $myrow['ord_date'], TRUE);
				$rep->DateCol(5, 6, $myrow['delivery_date'], TRUE);
				$rep->TextCol(6, 7, $myrow['from_stk_loc']);
				$rep->NewLine(2);
				$orderno = $myrow['order_no'];
			}
			$rep->TextCol(0, 1, $myrow['stk_code']);
			$rep->TextCol(1, 2, $myrow['description']);
			$dec = Item::qty_dec($myrow['stk_code']);
			$rep->AmountCol(2, 3, $myrow['quantity'], $dec);
			$rep->AmountCol(3, 4, $myrow['qty_sent'], $dec);
			$rep->AmountCol(4, 5, $myrow['quantity'] - $myrow['qty_sent'], $dec);
			if ($myrow['quantity'] - $myrow['qty_sent'] > 0) {
				$rep->Font('italic');
				$rep->TextCol(5, 6, _('Outstanding'));
				$rep->Font();
			}
			$rep->NewLine();
			if ($rep->row < $rep->bottomMargin + (2 * $rep->lineHeight)) {
				$orderno = 0;
				$rep->Header();
			}
		}
		$rep->Line($rep->row);
		$rep->End();
	}

