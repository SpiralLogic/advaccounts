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
$page_security = $_POST['PARAM_0'] == $_POST['PARAM_1'] ?
	'SA_MANUFTRANSVIEW' : 'SA_MANUFBULKREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Janusz Dobrowolski
// date_:	2008-01-14
// Title:	Print Workorders
// draft version!
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/manufacturing/includes/manufacturing_db.inc");

//----------------------------------------------------------------------------------------------------

print_workorders();

//----------------------------------------------------------------------------------------------------

function print_workorders()
{
	global $path_to_root, $SysPrefs;

	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$email = $_POST['PARAM_2'];
	$comments = $_POST['PARAM_3'];

	if ($from == null)
		$from = 0;
	if ($to == null)
		$to = 0;
	$dec = user_price_dec();

	$fno = explode("-", $from);
	$tno = explode("-", $to);

	$cols = array(4, 60, 190, 255, 320, 385, 450, 515);

	// $headers in doctext.inc
	$aligns = array('left',	'left',	'left', 'left', 'right', 'right', 'right');

	$params = array('comments' => $comments);

	$cur = get_company_Pref('curr_default');

	if ($email == 0)
	{
		$rep = new FrontReport(_('WORK ORDER'), "WorkOrderBulk", user_pagesize());
		$rep->currency = $cur;
		$rep->Font();
		$rep->Info($params, $cols, null, $aligns);
	}

	for ($i = $fno[0]; $i <= $tno[0]; $i++)
	{
		$myrow = get_work_order($i);
		if ($myrow === false)
			continue;
		$date_ = sql2date($myrow["date_"]);			
		if ($email == 1)
		{
			$rep = new FrontReport("", "", user_pagesize());
			$rep->currency = $cur;
			$rep->Font();
				$rep->title = _('WORK ORDER');
				$rep->filename = "WorkOrder" . $myrow['reference'] . ".pdf";
			$rep->Info($params, $cols, null, $aligns);
		}
		else
			$rep->title = _('WORK ORDER');
		$rep->Header2($myrow, null, null, '', 26);

		$result = get_wo_requirements($i);
		$rep->TextCol(0, 5,_("Work Order Requirements"), -2);
		$rep->NewLine(2);
		$has_marked = false;
		while ($myrow2=db_fetch($result))
		{
			$qoh = 0;
			$show_qoh = true;
			// if it's a non-stock item (eg. service) don't show qoh
			if (!has_stock_holding($myrow2["mb_flag"]))
				$show_qoh = false;

			if ($show_qoh)
				$qoh = get_qoh_on_date($myrow2["stock_id"], $myrow2["loc_code"], $date_);

			if ($show_qoh && ($myrow2["units_req"] * $myrow["units_issued"] > $qoh) &&
				!$SysPrefs->allow_negative_stock())
			{
				// oops, we don't have enough of one of the component items
				$has_marked = true;
			}
			else
				$has_marked = false;
			if ($has_marked)
				$str = $myrow2['stock_id']." ***";
			else
				$str = $myrow2['stock_id'];
			$rep->TextCol(0, 1,	$str, -2);
			$rep->TextCol(1, 2, $myrow2['description'], -2);

			$rep->TextCol(2, 3,	$myrow2['location_name'], -2);
			$rep->TextCol(3, 4,	$myrow2['WorkCentreDescription'], -2);
			$dec = get_qty_dec($myrow2["stock_id"]);

			$rep->AmountCol(4, 5,	$myrow2['units_req'], $dec, -2);
			$rep->AmountCol(5, 6,	$myrow2['units_req'] * $myrow['units_issued'], $dec, -2);
			$rep->AmountCol(6, 7,	$myrow2['units_issued'], $dec, -2);
			$rep->NewLine(1);
			if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight))
				$rep->Header2($myrow, null, null,'',26);
		}
		$rep->NewLine(1);
		$rep->TextCol(0, 5," *** = "._("Insufficient stock"), -2);

		$comments = get_comments(ST_WORKORDER, $i);
		if ($comments && db_num_rows($comments))
		{
			$rep->NewLine();
			while ($comment=db_fetch($comments))
				$rep->TextColLines(0, 6, $comment['memo_'], -2);
		}
	}
	if ($email == 0)
		$rep->End();
}

?>