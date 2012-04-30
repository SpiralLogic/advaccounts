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
  Page::start(_($help_context = "View or Print Transactions"), SA_VIEWPRINTTRANSACTION);

  if (isset($_POST['ProcessSearch'])) {
    if (!check_valid_entries()) {
      unset($_POST['ProcessSearch']);
    }
    Ajax::i()->activate('transactions');
  }
  start_form(FALSE);
  viewing_controls();
  handle_search();
  end_form(2);
  Page::end();
  /**
   * @param $trans
   *
   * @return null|string
   */
  function view_link($trans) {
    return GL_UI::trans_view($trans["type"], $trans["trans_no"]);
  }

  /**
   * @param $row
   *
   * @return string
   */
  function prt_link($row) {
    if ($row['type'] != ST_CUSTPAYMENT && $row['type'] != ST_CUSTREFUND && $row['type'] != ST_BANKDEPOSIT
    ) // customer payment or bank deposit printout not defined yet.
    {
      return Reporting::print_doc_link($row['trans_no'], _("Print"), TRUE, $row['type'], ICON_PRINT);
    }
  }

  /**
   * @param $row
   *
   * @return string
   */
  function gl_view($row) {
    return GL_UI::view($row["type"], $row["trans_no"]);
  }

  function viewing_controls() {
    Event::warning(_("Only documents can be printed."));
    start_table('tablestyle_noborder');
    start_row();
    SysTypes::cells(_("Type:"), 'filterType', NULL, TRUE);
    if (!isset($_POST['FromTransNo'])) {
      $_POST['FromTransNo'] = "1";
    }
    if (!isset($_POST['ToTransNo'])) {
      $_POST['ToTransNo'] = "999999";
    }
    ref_cells(_("from #:"), 'FromTransNo');
    ref_cells(_("to #:"), 'ToTransNo');
    submit_cells('ProcessSearch', _("Search"), '', '', 'default');
    end_row();
    end_table(1);
  }

  /**
   * @return bool
   */
  function check_valid_entries() {
    if (!is_numeric($_POST['FromTransNo']) OR $_POST['FromTransNo'] <= 0) {
      Event::error(_("The starting transaction number is expected to be numeric and greater than zero."));
      return FALSE;
    }
    if (!is_numeric($_POST['ToTransNo']) OR $_POST['ToTransNo'] <= 0) {
      Event::error(_("The ending transaction number is expected to be numeric and greater than zero."));
      return FALSE;
    }
    return TRUE;
  }

  function handle_search() {
    if (check_valid_entries() == TRUE) {
      $db_info = SysTypes::get_db_info($_POST['filterType']);
      if ($db_info == NULL) {
        return;
      }
      $table_name = $db_info[0];
      $type_name = $db_info[1];
      $trans_no_name = $db_info[2];
      $trans_ref = $db_info[3];
      $sql = "SELECT DISTINCT $trans_no_name as trans_no";
      if ($trans_ref) {
        $sql .= " ,$trans_ref ";
      }
      $sql .= ", " . $_POST['filterType'] . " as type FROM $table_name
			WHERE $trans_no_name >= " . DB::quote($_POST['FromTransNo']) . "
			AND $trans_no_name <= " . DB::quote($_POST['ToTransNo']);
      if ($type_name != NULL) {
        $sql .= " AND `$type_name` = " . DB::quote($_POST['filterType']);
      }
      $sql .= " ORDER BY $trans_no_name";
      $print_type = $_POST['filterType'];
      $print_out = ($print_type == ST_SALESINVOICE || $print_type == ST_CUSTCREDIT || $print_type == ST_CUSTDELIVERY || $print_type == ST_PURCHORDER || $print_type == ST_SALESORDER || $print_type == ST_SALESQUOTE);
      $cols = array(
        _("#"), _("Reference"), _("View") => array(
          'insert' => TRUE, 'fun' => 'view_link'
        ), _("Print") => array(
          'insert' => TRUE, 'fun' => 'prt_link'
        ), _("GL") => array(
          'insert' => TRUE, 'fun' => 'gl_view'
        )
      );
      if (!$print_out) {
        Arr::remove($cols, 3);
      }
      if (!$trans_ref) {
        Arr::remove($cols, 1);
      }
      $table =& db_pager::new_db_pager('transactions', $sql, $cols);
      $table->width = "40%";
      DB_Pager::display($table);
    }
  }

