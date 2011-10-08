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
	$page_security = 'SA_BOMREP';
	// ----------------------------------------------------------------
	// $ Revision:	2.0 $
	// Creator:	Joe Hunt
	// date_:	2005-05-19
	// Title:	Bill Of Material
	// ----------------------------------------------------------------

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");

	include_once(APP_PATH . "inventory/includes/db/items_db.inc");

	//----------------------------------------------------------------------------------------------------

	print_bill_of_material();

	function getTransactions($from, $to) {
		$sql = "SELECT bom.parent,
			bom.component,
			stock_master.description as CompDescription,
			bom.quantity,
			bom.loc_code,
			bom.workcentre_added
		FROM
			stock_master,
			bom
		WHERE stock_master.stock_id=bom.component
		AND bom.parent >= " . db_escape($from) . "
		AND bom.parent <= " . db_escape($to) . "
		ORDER BY
			bom.parent,
			bom.component";

		return db_query($sql, "No transactions were returned");
	}

	//----------------------------------------------------------------------------------------------------

	function print_bill_of_material() {

		$frompart = $_POST['PARAM_0'];
		$topart = $_POST['PARAM_1'];
		$comments = $_POST['PARAM_2'];
		$destination = $_POST['PARAM_3'];
		if ($destination)
			include_once(APP_PATH . "reporting/includes/excel_report.inc");
		else
			include_once(APP_PATH . "reporting/includes/pdf_report.inc");

		$cols = array(0, 50, 305, 375, 445, 515);

		$headers = array(_('Component'), _('Description'), _('Loc'), _('Wrk Ctr'), _('Quantity'));

		$aligns = array('left', 'left', 'left', 'left', 'right');

		$params = array(0 => $comments,
			1 => array('text' => _('Component'), 'from' => $frompart, 'to' => $topart)
		);

		$rep = new FrontReport(_('Bill of Material Listing'), "BillOfMaterial", user_pagesize());

		$rep->Font();
		$rep->Info($params, $cols, $headers, $aligns);
		$rep->Header();

		$res = getTransactions($frompart, $topart);
		$parent = '';
		while ($trans = db_fetch($res))
		{
			if ($parent != $trans['parent']) {
				if ($parent != '') {
					$rep->Line($rep->row - 2);
					$rep->NewLine(2, 3);
				}
				$rep->TextCol(0, 1, $trans['parent']);
				$desc = get_item($trans['parent']);
				$rep->TextCol(1, 2, $desc['description']);
				$parent = $trans['parent'];
				$rep->NewLine();
			}

			$rep->NewLine();
			$dec = get_qty_dec($trans['component']);
			$rep->TextCol(0, 1, $trans['component']);
			$rep->TextCol(1, 2, $trans['CompDescription']);
			//$rep->TextCol(2, 3, $trans['loc_code']);
			//$rep->TextCol(3, 4, $trans['workcentre_added']);
			$wc = get_work_centre($trans['workcentre_added']);
			$rep->TextCol(2, 3, get_location_name($trans['loc_code']));
			$rep->TextCol(3, 4, $wc['name']);
			$rep->AmountCol(4, 5, $trans['quantity'], $dec);
		}
		$rep->Line($rep->row - 4);
		$rep->NewLine();
		$rep->End();
	}

?>