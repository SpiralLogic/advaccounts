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
	$page_security = 'SA_DIMENSIONREP';
	// ----------------------------------------------------------------
	// $ Revision:	2.0 $
	// Creator:	Joe Hunt
	// date_:	2005-05-19
	// Title:	Dimension Summary
	// ----------------------------------------------------------------
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	//----------------------------------------------------------------------------------------------------
	print_dimension_summary();
	function getTransactions($from, $to)
	{
		$sql
		 = "SELECT *
		FROM
			dimensions
		WHERE reference >= " . DB::escape($from) . "
		AND reference <= " . DB::escape($to) . "
		ORDER BY
			reference";
		return DBOld::query($sql, "No transactions were returned");
	}

	function getYTD($dim)
	{
		$date = Dates::Today();
		$date = Dates::begin_fiscalyear($date);
		Dates::date2sql($date);
		$sql
		 = "SELECT SUM(amount) AS Balance
		FROM
			gl_trans
		WHERE (dimension_id = '$dim' OR dimension2_id = '$dim')
		AND tran_date >= '$date'";
		$TransResult = DBOld::query($sql, "No transactions were returned");
		if (DBOld::num_rows($TransResult) == 1) {
			$DemandRow = DBOld::fetch_row($TransResult);
			$balance = $DemandRow[0];
		}
		else
		{
			$balance = 0.0;
		}
		return $balance;
	}

	//----------------------------------------------------------------------------------------------------
	function print_dimension_summary()
	{
		$fromdim = $_POST['PARAM_0'];
		$todim = $_POST['PARAM_1'];
		$showbal = $_POST['PARAM_2'];
		$comments = $_POST['PARAM_3'];
		$destination = $_POST['PARAM_4'];
		if ($destination) {
			include_once(APP_PATH . "includes/reports/excel.php");
		}
		else
		{
			include_once(APP_PATH . "includes/reports/pdf.php");
		}
		$cols = array(0, 50, 210, 250, 320, 395, 465, 515);
		$headers = array(_('Reference'), _('Name'), _('Type'), _('Date'), _('Due Date'), _('Closed'), _('YTD'));
		$aligns = array('left', 'left', 'left', 'left', 'left', 'left', 'right');
		$params = array(0 => $comments,
			1 => array('text' => _('Dimension'), 'from' => $fromdim, 'to' => $todim)
		);
		$rep = new FrontReport(_('Dimension Summary'), "DimensionSummary", User::pagesize());
		$rep->Font();
		$rep->Info($params, $cols, $headers, $aligns);
		$rep->Header();
		$res = getTransactions($fromdim, $todim);
		while ($trans = DBOld::fetch($res))
		{
			$rep->TextCol(0, 1, $trans['reference']);
			$rep->TextCol(1, 2, $trans['name']);
			$rep->TextCol(2, 3, $trans['type_']);
			$rep->DateCol(3, 4, $trans['date_'], true);
			$rep->DateCol(4, 5, $trans['due_date'], true);
			if ($trans['closed']) {
				$str = _('Yes');
			}
			else
			{
				$str = _('No');
			}
			$rep->TextCol(5, 6, $str);
			if ($showbal) {
				$balance = getYTD($trans['id']);
				$rep->AmountCol(6, 7, $balance, 0);
			}
			$rep->NewLine(1, 2);
		}
		$rep->Line($rep->row);
		$rep->End();
	}

?>