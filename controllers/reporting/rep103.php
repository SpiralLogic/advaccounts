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


	Page::set_security(SA_CUSTBULKREP);

	print_customer_details_listing();
  /**
   * @param int $area
   * @param int $salesid
   *
   * @return null|PDOStatement
   */function get_customer_details_for_report($area = 0, $salesid = 0)
	{
		$sql
		 = "SELECT debtors.debtor_no,
			debtors.name,
			debtors.address,
			sales_types.sales_type,
			branches.branch_id,
			branches.br_name,
			branches.br_address,
			branches.contact_name,
			branches.phone,
			branches.fax,
			branches.email,
			branches.area,
			branches.salesman,
			areas.description,
			salesman.salesman_name
		FROM debtors
		INNER JOIN branches
			ON debtors.debtor_no=branches.debtor_no
		INNER JOIN sales_types
			ON debtors.sales_type=sales_types.id
		INNER JOIN areas
			ON branches.area = areas.area_code
		INNER JOIN salesman
			ON branches.salesman=salesman.salesman_code";
		if ($area != 0) {
			if ($salesid != 0) {
				$sql .= " WHERE salesman.salesman_code=" . DB::escape($salesid) . "
				AND areas.area_code=" . DB::escape($area);
			}
			else
			{
				$sql .= " WHERE areas.area_code=" . DB::escape($area);
			}
		}
		elseif ($salesid != 0)
		{
			$sql .= " WHERE salesman.salesman_code=" . DB::escape($salesid);
		}
		$sql
		 .= " ORDER BY description,
			salesman.salesman_name,
			debtors.debtor_no,
			branches.branch_id";
		return DB::query($sql, "No transactions were returned");
	}

  /**
   * @param $debtorno
   * @param $branchcode
   * @param $date
   *
   * @return mixed
   */function get_transactions($debtorno, $branchcode, $date)
	{
		$date = Dates::date2sql($date);
		$sql
		 = "SELECT SUM((ov_amount+ov_freight+ov_discount)*rate) AS Turnover
		FROM debtor_trans
		WHERE debtor_no=" . DB::escape($debtorno) . "
		AND branch_id=" . DB::escape($branchcode) . "
		AND (type=" . ST_SALESINVOICE . " OR type=" . ST_CUSTCREDIT . ")
		AND trandate >='$date'";
		$result = DB::query($sql, "No transactions were returned");
		$row = DB::fetch_row($result);
		return $row[0];
	}


	function print_customer_details_listing()
	{
		$from = $_POST['PARAM_0'];
		$area = $_POST['PARAM_1'];
		$folk = $_POST['PARAM_2'];
		$more = $_POST['PARAM_3'];
		$less = $_POST['PARAM_4'];
		$comments = $_POST['PARAM_5'];
		$destination = $_POST['PARAM_6'];
		if ($destination) {
			include_once(APPPATH . "reports/excel.php");
		}
		else
		{
			include_once(APPPATH . "reports/pdf.php");
		}
		$dec = 0;
		if ($area == ALL_NUMERIC) {
			$area = 0;
		}
		if ($folk == ALL_NUMERIC) {
			$folk = 0;
		}
		if ($area == 0) {
			$sarea = _('All Areas');
		}
		else
		{
			$sarea = get_area($area);
		}
		if ($folk == 0) {
			$salesfolk = _('All Sales Folk');
		}
		else
		{
			$salesfolk = Debtor::get_salesman($folk);
		}
		if ($more != '') {
			$morestr = _('Greater than ') . Num::format($more, $dec);
		}
		else
		{
			$morestr = '';
		}
		if ($less != '') {
			$lessstr = _('Less than ') . Num::format($less, $dec);
		}
		else
		{
			$lessstr = '';
		}
		$more = (double)$more;
		$less = (double)$less;
		$cols = array(0, 150, 300, 400, 550);
		$headers = array(_('Customer Postal Address'), _('Price/Turnover'), _('Branch Contact Information'),
			_('Branch Delivery Address')
		);
		$aligns = array('left', 'left', 'left', 'left');
		$params = array(0 => $comments,
			1 => array('text' => _('Activity Since'), 'from' => $from, 'to' => ''),
			2 => array('text' => _('Sales Areas'), 'from' => $sarea, 'to' => ''),
			3 => array('text' => _('Sales Folk'), 'from' => $salesfolk, 'to' => ''),
			4 => array('text' => _('Activity'), 'from' => $morestr, 'to' => $lessstr)
		);
		$rep = new ADVReport(_('Customer Details Listing'), "CustomerDetailsListing", User::pagesize());
		$rep->Font();
		$rep->Info($params, $cols, $headers, $aligns);
		$rep->Header();
		$result = get_customer_details_for_report($area, $folk);
		$carea = '';
		$sman = '';
		while ($myrow = DB::fetch($result))
		{
			$printcustomer = TRUE;
			if ($more != '' || $less != '') {
				$turnover = get_transactions($myrow['debtor_no'], $myrow['branch_id'], $from);
				if ($more != 0.0 && $turnover <= (double)$more) {
					$printcustomer = FALSE;
				}
				if ($less != 0.0 && $turnover >= (double)$less) {
					$printcustomer = FALSE;
				}
			}
			if ($printcustomer) {
				if ($carea != $myrow['description']) {
					$rep->fontSize += 2;
					$rep->NewLine(2, 7);
					$rep->Font('bold');
					$rep->TextCol(0, 3, _('Customers in') . " " . $myrow['description']);
					$carea = $myrow['description'];
					$rep->fontSize -= 2;
					$rep->Font();
					$rep->NewLine();
				}
				if ($sman != $myrow['salesman_name']) {
					$rep->fontSize += 2;
					$rep->NewLine(1, 7);
					$rep->Font('bold');
					$rep->TextCol(0, 3, $myrow['salesman_name']);
					$sman = $myrow['salesman_name'];
					$rep->fontSize -= 2;
					$rep->Font();
					$rep->NewLine();
				}
				$rep->NewLine();
				$rep->TextCol(0, 1, $myrow['name']);
				$adr = Explode("\n", $myrow['address']);
				$count1 = count($adr);
				for ($i = 0; $i < $count1; $i++)
				{
					$rep->TextCol(0, 1, $adr[$i], 0, ($i + 1) * $rep->lineHeight);
				}
				$count1++;
				$rep->TextCol(1, 2, _('Price List') . ": " . $myrow['sales_type']);
				if ($more != 0.0 || $less != 0.0) {
					$rep->TextCol(1, 2, _('Turnover') . ": " . Num::format($turnover, $dec), 0, $rep->lineHeight);
				}
				$rep->TextCol(2, 3, $myrow['br_name']);
				$rep->TextCol(2, 3, $myrow['contact_name'], 0, $rep->lineHeight);
				$rep->TextCol(2, 3, _('Ph') . ": " . $myrow['phone'], 0, 2 * $rep->lineHeight);
				$rep->TextCol(2, 3, _('Fax') . ": " . $myrow['fax'], 0, 3 * $rep->lineHeight);
				$adr = Explode("\n", $myrow['br_address']);
				$count2 = count($adr);
				for ($i = 0; $i < $count2; $i++)
				{
					$rep->TextCol(3, 4, $adr[$i], 0, ($i + 1) * $rep->lineHeight);
				}
				$rep->TextCol(3, 4, $myrow['email'], 0, ($count2 + 1) * $rep->lineHeight);
				$count2++;
				$count1 = Max($count1, $count2);
				$count1 = Max($count1, 4);
				$rep->NewLine($count1);
				$rep->Line($rep->row + 8);
				$rep->NewLine(0, 3);
			}
		}
		$rep->End();
	}

