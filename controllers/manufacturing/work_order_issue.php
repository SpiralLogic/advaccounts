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
  Page::start(_($help_context = "Issue Items to Work Order"), SA_MANUFISSUE);
  if (isset($_GET[ADDED_ID])) {
    Event::success(_("The work order issue has been entered."));
    Display::note(GL_UI::trans_view(ST_WORKORDER, $_GET[ADDED_ID], _("View this Work Order")));
    Display::link_no_params("search_work_orders.php", _("Select another &Work Order to Process"));
    Page::footer_exit();
  }


  function handle_new_order() {
    if (isset($_SESSION['issue_items'])) {
      $_SESSION['issue_items']->clear_items();
      unset ($_SESSION['issue_items']);
    }
    Session_register("issue_items");
    $_SESSION['issue_items'] = new Item_Order(28);
    $_SESSION['issue_items']->order_id = $_GET['trans_no'];
  }

  /**
   * @return bool
   */
  function can_process() {
    if (!Dates::is_date($_POST['date_'])) {
      Event::error(_("The entered date for the issue is invalid."));
      JS::set_focus('date_');
      return FALSE;
    }
    elseif (!Dates::is_date_in_fiscalyear($_POST['date_'])) {
      Event::error(_("The entered date is not in fiscal year."));
      JS::set_focus('date_');
      return FALSE;
    }
    if (!Ref::is_valid($_POST['ref'])) {
      Event::error(_("You must enter a reference."));
      JS::set_focus('ref');
      return FALSE;
    }
    if (!Ref::is_new($_POST['ref'], ST_MANUISSUE)) {
      $_POST['ref'] = Ref::get_next(ST_MANUISSUE);
    }
    $failed_item = $_SESSION['issue_items']->check_qoh($_POST['location'], $_POST['date_'], !$_POST['IssueType']);
    if ($failed_item != -1) {
      Event::error(_("The issue cannot be processed because an entered item would cause a negative inventory balance :") . " " . $failed_item->stock_id . " - " . $failed_item->description);
      return FALSE;
    }
    return TRUE;
  }

  if (isset($_POST['Process']) && can_process()) {
    // if failed, returns a stockID
    $failed_data = WO_Issue::add($_SESSION['issue_items']->order_id, $_POST['ref'], $_POST['IssueType'], $_SESSION['issue_items']->line_items, $_POST['location'], $_POST['WorkCentre'], $_POST['date_'], $_POST['memo_']);
    if ($failed_data != NULL) {
      Event::error(_("The process cannot be completed because there is an insufficient total quantity for a component.") . "<br>" . _("Component is :") . $failed_data[0] . "<br>" . _("From location :") . $failed_data[1] . "<br>");
    }
    else {
      Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=" . $_SESSION['issue_items']->order_id);
    }
  } /*end of process credit note */
  /**
   * @return bool
   */
  function check_item_data() {
    if (!Validation::post_num('qty', 0)) {
      Event::error(_("The quantity entered is negative or invalid."));
      JS::set_focus('qty');
      return FALSE;
    }
    if (!Validation::post_num('std_cost', 0)) {
      Event::error(_("The entered standard cost is negative or invalid."));
      JS::set_focus('std_cost');
      return FALSE;
    }
    return TRUE;
  }

  function handle_update_item() {
    if ($_POST['UpdateItem'] != "" && check_item_data()) {
      $id = $_POST['LineNo'];
      $_SESSION['issue_items']->update_order_item($id, Validation::input_num('qty'), Validation::input_num('std_cost'));
    }
    Item_Line::start_focus('_stock_id_edit');
  }

  /**
   * @param $id
   */
  function handle_delete_item($id) {
    $_SESSION['issue_items']->remove_from_order($id);
    Item_Line::start_focus('_stock_id_edit');
  }

  function handle_new_item() {
    if (!check_item_data()) {
      return;
    }
    WO_Issue::add_to($_SESSION['issue_items'], $_POST['stock_id'], Validation::input_num('qty'), Validation::input_num('std_cost'));
    Item_Line::start_focus('_stock_id_edit');
  }

  $id = find_submit(MODE_DELETE);
  if ($id != -1) {
    handle_delete_item($id);
  }
  if (isset($_POST['AddItem'])) {
    handle_new_item();
  }
  if (isset($_POST['UpdateItem'])) {
    handle_update_item();
  }
  if (isset($_POST['CancelItemChanges'])) {
    Item_Line::start_focus('_stock_id_edit');
  }
  if (isset($_GET['trans_no'])) {
    handle_new_order();
  }
  WO_Cost::display($_SESSION['issue_items']->order_id);
  echo "<br>";
  start_form();
  start_table('tablesstyle width90 pad10');
  echo "<tr><td>";
  WO_Issue::display_items(_("Items to Issue"), $_SESSION['issue_items']);
  WO_Issue::option_controls();
  echo "</td></tr>";
  end_table();
  submit_center('Process', _("Process Issue"), TRUE, '', 'default');
  end_form();
  Page::end();

