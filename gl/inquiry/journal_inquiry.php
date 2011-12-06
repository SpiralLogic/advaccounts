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
	$page_security = 'SA_GLANALYTIC';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::open_window(800, 500);
	Page::start(_($help_context = "Journal Inquiry"));

	// Ajax updates
	//
	if (get_post('Search')) {
		$Ajax->activate('journal_tbl');
	}

	if (!isset($_POST['filterType'])) {
		$_POST['filterType'] = -1;
	}
	start_form();
	start_table('tablestyle_noborder');
	start_row();
	ref_cells(_("Reference:"), 'Ref', '', null, _('Enter reference fragment or leave empty'));
	GL_Journal::cells(_("Type:"), "filterType");
	date_cells(_("From:"), 'FromDate', '', null, 0, -1, 0);
	date_cells(_("To:"), 'ToDate');
	check_cells(_("Show closed:"), 'AlsoClosed', null);
	submit_cells('Search', _("Search"), '', '', 'default');
	end_row();
	start_row();
	ref_cells(_("Memo:"), 'Memo', '', null, _('Enter memo fragment or leave empty'));
	end_row();
	end_table();
	function journal_pos($row)
	{
		return $row['gl_seq'] ? $row['gl_seq'] : '-';
	}

	function systype_name($dummy, $type)
	{
		global $systypes_array;
		return $systypes_array[$type];
	}

	function view_link($row)
	{
		return GL_UI::trans_view($row["type"], $row["type_no"]);
	}

	function gl_link($row)
	{
		return GL_UI::view($row["type"], $row["type_no"]);
	}

	$editors = array(
		0 => "/gl/gl_journal.php?ModifyGL=Yes&trans_no=%d&trans_type=%d",
//	1=> Bank Payment,
//	2=> Bank Deposit,
//	4=> Funds Transfer,
		ST_SALESINVOICE => "/sales/customer_invoice.php?ModifyInvoice=%d",
// 11=>
// free hand (debtors_trans.order_==0)
//	"/sales/credit_note_entry.php?ModifyCredit=%d"
// credit invoice
//	"/sales/customer_credit_invoice.php?ModifyCredit=%d"
//	 12=> Customer Payment,
		ST_CUSTDELIVERY => "/sales/customer_delivery.php?ModifyDelivery=%d",
// 16=> Location Transfer,
// 17=> Inventory Adjustment,
// 20=> Supplier Invoice,
// 21=> Supplier Credit Note,
// 22=> Supplier Payment,
// 25=> Purchase Order Delivery,
// 28=> Work Order Issue,
// 29=> Work Order Production",
// 35=> Cost Update,
	);
	function edit_link($row)
	{
		global $editors;
		return isset($editors[$row["type"]]) && !DB_AuditTrail::is_closed_trans($row["type"], $row["type_no"]) ?
		 DB_Pager::link(
			 _("Edit"),
			 sprintf($editors[$row["type"]], $row["type_no"], $row["type"]),
			 ICON_EDIT
		 ) : '';
	}

	/*
	 // Tom Hallman 11 Nov 2009
	 // IF(gl.type = 1... statement is for deposits/payments that may not actually result
	 // in a deposit, such as when a fix is made. Without that statement (and the
	 // joining of the bank_trans table), the fix deposit/payment amount would show up
	 // incorrectly as only the positive side of the fix.
	 $sql = "SELECT	IF(ISNULL(a.gl_seq),0,a.gl_seq) as gl_seq,
		 gl.tran_date,
		 gl.type,
		 gl.type_no,
		 refs.reference,
		 IF(gl.type = 1 OR gl.type = 2,
			 bank_trans.amount,
			 SUM(IF(gl.amount>0, gl.amount,0))) as amount,
		 com.memo_,
		 IF(ISNULL(u.user_id),'',u.user_id) as user_id
		 FROM ".''."gl_trans as gl
			LEFT JOIN ".''."audit_trail as a ON
			 (gl.type=a.type AND gl.type_no=a.trans_no)
			LEFT JOIN ".''."comments as com ON
			 (gl.type=com.type AND gl.type_no=com.id)
			LEFT JOIN ".''."refs as refs ON
			 (gl.type=refs.type AND gl.type_no=refs.id)
			LEFT JOIN ".''."users as u ON
			 a.user=u.id
			LEFT JOIN ".''."bank_trans as bank_trans ON
			 (gl.type=bank_trans.type AND gl.type_no=bank_trans.trans_no)
		 WHERE gl.tran_date >= '" . Dates::date2sql($_POST['FromDate']) . "'
		 AND gl.tran_date <= '" . Dates::date2sql($_POST['ToDate']) . "'
		 AND gl.amount!=0";
	 if (isset($_POST['Ref']) && $_POST['Ref'] != "") {
		 $sql .= " AND reference LIKE '%". $_POST['Ref'] . "%'";
	 }
	 if (get_post('filterType') != -1) {
		 $sql .= " AND gl.type=".get_post('filterType');
	 }
	 if (!check_value('AlsoClosed')) {
		 $sql .= " AND gl_seq=0";
	 }
	 $sql .= " GROUP BY gl.type, gl.type_no";
	 */
	$sql
	 = "SELECT	IF(ISNULL(a.gl_seq),0,a.gl_seq) as gl_seq,
 	gl.tran_date,
 	gl.type,
 	gl.type_no,
 	refs.reference,
 	SUM(IF(gl.amount>0, gl.amount,0)) as amount,
 	com.memo_,
 	IF(ISNULL(u.user_id),'',u.user_id) as user_id
 	FROM gl_trans as gl
 	 LEFT JOIN audit_trail as a ON
 		(gl.type=a.type AND gl.type_no=a.trans_no)
 	 LEFT JOIN comments as com ON
 		(gl.type=com.type AND gl.type_no=com.id)
 	 LEFT JOIN refs as refs ON
 		(gl.type=refs.type AND gl.type_no=refs.id)
 	 LEFT JOIN users as u ON
 		a.user=u.id
 	WHERE gl.tran_date >= '" . Dates::date2sql($_POST['FromDate']) . "'
 	AND gl.tran_date <= '" . Dates::date2sql($_POST['ToDate']) . "'
 	AND gl.amount!=0";
	if (isset($_POST['Ref']) && $_POST['Ref'] != "") {
		$sql .= " AND reference LIKE '%" . $_POST['Ref'] . "%'";
	}
	if (isset($_POST['Memo']) && $_POST['Memo'] != "") {
		$sql .= " AND com.memo_ LIKE '%" . $_POST['Memo'] . "%'";
	}
	if (get_post('filterType') != -1) {
		$sql .= " AND gl.type=" . get_post('filterType');
	}
	if (!check_value('AlsoClosed')) {
		$sql .= " AND gl_seq=0";
	}
	$sql .= " GROUP BY gl.type, gl.type_no";
	$cols = array(
		_("#") => array(
			'fun' => 'journal_pos',
			'align' => 'center'
		),
		_("Date") => array(
			'name' => 'tran_date',
			'type' => 'date',
			'ord' => 'desc'
		),
		_("Type") => array('fun' => 'systype_name'),
		_("Trans #") => array('fun' => 'view_link'),
		_("Reference"),
		_("Amount") => array('type' => 'amount'),
		_("Memo"),
		_("User"),
		_("View") => array(
			'insert' => true,
			'fun' => 'gl_link'
		),
		array(
			'insert' => true,
			'fun' => 'edit_link'
		)
	);
	if (!check_value('AlsoClosed')) {
		$cols[_("#")] = 'skip';
	}
	$table =& db_pager::new_db_pager('journal_tbl', $sql, $cols);
	$table->width = "80%";
	DB_Pager::display($table);
	end_form();
	end_page();

?>
