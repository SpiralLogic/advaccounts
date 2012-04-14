<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  //require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");

  JS::open_window(800, 500);
  Page::start(_($help_context = "Item Stocktake Note"), SA_INVENTORYADJUSTMENT);
  Validation::check(Validation::COST_ITEMS, _("There are no inventory items defined in the system which can be adjusted (Purchased or Manufactured)."), STOCK_SERVICE);
  Validation::check(Validation::MOVEMENT_TYPES, _("There are no inventory movement types defined in the system. Please define at least one inventory adjustment type."));
  if (isset($_GET[ADDED_ID])) {
    $trans_no = $_GET[ADDED_ID];
    $trans_type = ST_INVADJUST;
    Event::success(_("Items adjustment has been processed"));
    Display::note(GL_UI::trans_view($trans_type, $trans_no, _("&View this adjustment")));
    Display::note(GL_UI::view($trans_type, $trans_no, _("View the GL &Postings for this Adjustment")), 1, 0);
    Display::link_no_params($_SERVER['PHP_SELF'], _("Enter &Another Adjustment"));
    Page::footer_exit();
  }

  if (isset($_POST['Process']) && can_process()) {
    foreach ($_SESSION['adj_items']->line_items as $line) {
      $item = new Item($line->stock_id);
      $current_stock = $item->getStockLevels($_POST['StockLocation']);
      $line->quantity -= $current_stock['qty'];
    }
    $trans_no = Inv_Adjustment::add($_SESSION['adj_items']->line_items, $_POST['StockLocation'], $_POST['AdjDate'], $_POST['type'], $_POST['Increase'], $_POST['ref'], $_POST['memo_']);
    Dates::new_doc_date($_POST['AdjDate']);
    $_SESSION['adj_items']->clear_items();
    unset($_SESSION['adj_items']);
    Display::meta_forward($_SERVER['PHP_SELF'], "AddedID=$trans_no");
  } /*end of process credit note */

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
  if (isset($_GET['NewAdjustment']) || !isset($_SESSION['adj_items'])) {
    handle_new_order();
  }
  start_form();
  Inv_Adjustment::header($_SESSION['adj_items']);
  start_outer_table('tablestyle width80 pad10');
  Inv_Adjustment::display_items(_("Adjustment Items"), $_SESSION['adj_items']);
  Inv_Adjustment::option_controls();
  end_outer_table(1, FALSE);
  submit_center_first('Update', _("Update"), '', NULL);
  submit_center_last('Process', _("Process Adjustment"), '', 'default');
  end_form();
  Page::end();
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
      $_SESSION['adj_items']->update_order_item($id, Validation::input_num('qty'), Validation::input_num('std_cost'));
    }
    Item_Line::start_focus('_stock_id_edit');
  }

  /**
   * @param $id
   */
  function handle_delete_item($id) {
    $_SESSION['adj_items']->remove_from_order($id);
    Item_Line::start_focus('_stock_id_edit');
  }

  function handle_new_item() {
    if (!check_item_data()) {
      return;
    }
    Item_Order::add_line($_SESSION['adj_items'], $_POST['stock_id'], Validation::input_num('qty'), Validation::input_num('std_cost'));
    Item_Line::start_focus('_stock_id_edit');
  }

  function handle_new_order() {
    if (isset($_SESSION['adj_items'])) {
      $_SESSION['adj_items']->clear_items();
      unset ($_SESSION['adj_items']);
    }
    $_SESSION['adj_items'] = new Item_Order(ST_INVADJUST);
    $_POST['AdjDate'] = Dates::new_doc_date();
    if (!Dates::is_date_in_fiscalyear($_POST['AdjDate'])) {
      $_POST['AdjDate'] = Dates::end_fiscalyear();
    }
    $_SESSION['adj_items']->tran_date = $_POST['AdjDate'];
  }

  /**
   * @return bool
   */
  function can_process() {
    $adj = &$_SESSION['adj_items'];
    if (count($adj->line_items) == 0) {
      Event::error(_("You must enter at least one non empty item line."));
      JS::set_focus('stock_id');
      return FALSE;
    }
    if (!Ref::is_valid($_POST['ref'])) {
      Event::error(_("You must enter a reference."));
      JS::set_focus('ref');
      return FALSE;
    }
    if (!Ref::is_new($_POST['ref'], ST_INVADJUST)) {
      $_POST['ref'] = Ref::get_next(ST_INVADJUST);
    }
    if (!Dates::is_date($_POST['AdjDate'])) {
      Event::error(_("The entered date for the adjustment is invalid."));
      JS::set_focus('AdjDate');
      return FALSE;
    }
    elseif (!Dates::is_date_in_fiscalyear($_POST['AdjDate'])) {
      Event::error(_("The entered date is not in fiscal year."));
      JS::set_focus('AdjDate');
      return FALSE;
    }
    return TRUE;
  }


