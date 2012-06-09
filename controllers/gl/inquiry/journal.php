<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  JS::open_window(800, 500);
  Page::start(_($help_context = "Journal Inquiry"), SA_GLANALYTIC);
  // Ajax updates
  //
  if (Input::post('Search')) {
    Ajax::i()->activate('journal_tbl');
  }
  if (!isset($_POST['filterType'])) {
    $_POST['filterType'] = -1;
  }
  Form::start();
  Table::start('tablestyle_noborder');
  Row::start();
   Form::refCells(_("Reference:"), 'Ref', '', null, _('Enter reference fragment or leave empty'));
  GL_Journal::cells(_("Type:"), "filterType");
   Form::dateCells(_("From:"), 'FromDate', '', null, 0, -1, 0);
   Form::dateCells(_("To:"), 'ToDate');
   Form::checkCells(_("Show closed:"), 'AlsoClosed', null);
   Form::refCells(_("Memo:"), 'Memo', '', null, _('Enter memo fragment or leave empty'));
  Form::submitCells('Search', _("Search"), '', '', 'default');
  Row::end();
  Table::end();
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
  if (Input::post('filterType') != -1) {
    $sql .= " AND gl.type=".Input::post('filterType');
  }
  if (!Form::hasPost('AlsoClosed')) {
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
  if (Input::post('filterType') != -1) {
    $sql .= " AND gl.type=" . Input::post('filterType');
  }
  if (!Form::hasPost('AlsoClosed')) {
    $sql .= " AND gl_seq=0";
  }
  $sql .= " GROUP BY gl.type, gl.type_no";
  $cols = array(
    _("#")             => array(
      'fun' => 'journal_pos', 'align' => 'center'
    ),
    _("Date")          => array(
      'name' => 'tran_date', 'type' => 'date', 'ord' => 'desc'
    ),
    _("Type")          => array('fun' => 'systype_name'),
    _("Trans #")       => array('fun' => 'view_link'),
    _("Reference"),
    _("Amount")        => array('type' => 'amount'),
    _("Memo"),
    _("User"),
    _("View")          => array(
      'insert' => true, 'fun' => 'gl_link'
    ),
    array(
      'insert' => true, 'fun' => 'edit_link'
    )
  );
  if (!Form::hasPost('AlsoClosed')) {
    $cols[_("#")] = 'skip';
  }
  $table        =& db_pager::new_db_pager('journal_tbl', $sql, $cols);
  $table->width = "80%";
  DB_Pager::display($table);
  Form::end();
  Page::end();
  /**
   * @param $row
   *
   * @return string
   */
  function journal_pos($row)
  {
    return $row['gl_seq'] ? $row['gl_seq'] : '-';
  }

  /**
   * @param $dummy
   * @param $type
   *
   * @return mixed
   */
  function systype_name($dummy, $type)
  {
    global $systypes_array;

    return $systypes_array[$type];
  }

  /**
   * @param $row
   *
   * @return null|string
   */
  function view_link($row)
  {
    return GL_UI::trans_view($row["type"], $row["type_no"]);
  }

  /**
   * @param $row
   *
   * @return string
   */
  function gl_link($row)
  {
    return GL_UI::view($row["type"], $row["type_no"]);
  }

  /**
   * @param $row
   *
   * @return string
   */
  function edit_link($row)
  {
    global $editors;

    return isset($editors[$row["type"]]) && !DB_AuditTrail::is_closed_trans($row["type"], $row["type_no"]) ?
      DB_Pager::link(_("Edit"), sprintf($editors[$row["type"]], $row["type_no"], $row["type"]), ICON_EDIT) : '';
  }
