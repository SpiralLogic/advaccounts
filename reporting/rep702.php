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
$page_security = 'SA_GLANALYTIC';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	List of Journal Entries
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/includes/ui/ui_view.inc");

//----------------------------------------------------------------------------------------------------

print_list_of_journal_entries();

//----------------------------------------------------------------------------------------------------

function print_list_of_journal_entries()
{
    global $path_to_root, $systypes_array;

    $from = $_POST['PARAM_0'];
    $to = $_POST['PARAM_1'];
    $systype = $_POST['PARAM_2'];
    $comments = $_POST['PARAM_3'];
	$destination = $_POST['PARAM_4'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $dec = user_price_dec();

    $cols = array(0, 100, 240, 300, 400, 460, 520, 580);

    $headers = array(_('Type/Account'), _('Reference').'/'._('Account Name'), _('Date/Dim.'),
    	_('Person/Item/Memo'), _('Debit'), _('Credit'));

    $aligns = array('left', 'left', 'left', 'left', 'right', 'right');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from,'to' => $to),
                    	2 => array('text' => _('Type'), 'from' => 
						$systype == -1 ? _('All') : $systypes_array[$systype],
                            'to' => ''));

    $rep = new FrontReport(_('List of Journal Entries'), "JournalEntries", user_pagesize());

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();

    if ($systype == -1)
        $systype = null;

    $trans = get_gl_transactions($from, $to, -1, null, 0, 0, $systype);

    $typeno = $type = 0;
    while ($myrow=db_fetch($trans))
    {
        if ($type != $myrow['type'] || $typeno != $myrow['type_no'])
        {
            if ($typeno != 0)
            {
                $rep->Line($rep->row  + 4);
                $rep->NewLine();
            }
            $typeno = $myrow['type_no'];
            $type = $myrow['type'];
            $TransName = $systypes_array[$myrow['type']];
            $rep->TextCol(0, 1, $TransName . " # " . $myrow['type_no']);
            $rep->TextCol(1, 2, get_reference($myrow['type'], $myrow['type_no']));
            $rep->DateCol(2, 3, $myrow['tran_date'], true);
            $coms =  payment_person_name($myrow["person_type_id"],$myrow["person_id"]);
            $memo = get_comments_string($myrow['type'], $myrow['type_no']);
            if ($memo != '')
            {
            	if ($coms == "")
            		$coms = $memo;
            	else
            		$coms .= " / ".$memo;
            }		
            $rep->TextCol(3, 6, $coms);
            $rep->NewLine(2);
        }
        $rep->TextCol(0, 1, $myrow['account']);
        $rep->TextCol(1, 2, $myrow['account_name']);
        $dim_str = get_dimension_string($myrow['dimension_id']);
        $dim_str2 = get_dimension_string($myrow['dimension2_id']);
        if ($dim_str2 != "")
        	$dim_str .= "/".$dim_str2;
        $rep->TextCol(2, 3, $dim_str);
        $rep->TextCol(3, 4, $myrow['memo_']);
        if ($myrow['amount'] > 0.0)
            $rep->AmountCol(4, 5, abs($myrow['amount']), $dec);
        else
            $rep->AmountCol(5, 6, abs($myrow['amount']), $dec);
        $rep->NewLine(1, 2);
    }
    $rep->Line($rep->row  + 4);
    $rep->End();
}

?>