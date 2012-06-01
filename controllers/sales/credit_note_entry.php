<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  //
  //	Entry/Modify free hand Credit Note
  //

  JS::open_window(900, 500);
  $order = Orders::session_get() ? : null;
  if (isset($_GET[Orders::NEW_CREDIT])) {
    $_SESSION['page_title'] = _($help_context = "Customer Credit Note");
    $order                  = handle_new_credit(0);
  } elseif (isset($_GET[Orders::MODIFY_CREDIT])) {
    $_SESSION['page_title'] = sprintf(_("Modifying Customer Credit Note #%d"), $_GET[Orders::MODIFY_CREDIT]);
    $order                  = handle_new_credit($_GET[Orders::MODIFY_CREDIT]);
    $help_context           = "Modifying Customer Credit Note";
  } else {
    $_SESSION['page_title'] = _($help_context = "Customer Credit Note");
  }
  Page::start($_SESSION['page_title'], SA_SALESCREDIT);
  Validation::check(Validation::STOCK_ITEMS, _("There are no items defined in the system."));
  Validation::check(Validation::BRANCHES_ACTIVE, _("There are no customers, or there are no customers with branches. Please define customers and customer branches."));
  if (list_updated('branch_id')) {
    // when branch is selected via external editor also customer can change
    $br                   = Sales_Branch::get(get_post('branch_id'));
    $_POST['customer_id'] = $br['debtor_id'];
    Ajax::i()->activate('customer_id');
  }
  if (isset($_GET[ADDED_ID])) {
    $credit_no  = $_GET[ADDED_ID];
    $trans_type = ST_CUSTCREDIT;
    Event::success(sprintf(_("Credit Note # %d has been processed"), $credit_no));
    Display::note(Debtor::trans_view($trans_type, $credit_no, _("&View this credit note")), 0, 1);
    Display::note(Reporting::print_doc_link($credit_no . "-" . $trans_type, _("&Print This Credit Invoice"), true, ST_CUSTCREDIT), 0, 1);
    Display::note(Reporting::print_doc_link($credit_no . "-" . $trans_type, _("&Email This Credit Invoice"), true, ST_CUSTCREDIT, false, "printlink", "", 1), 0, 1);
    Display::note(GL_UI::view($trans_type, $credit_no, _("View the GL &Journal Entries for this Credit Note")));
    Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter Another &Credit Note"), "NewCredit=yes");
    Display::link_params("/system/attachments.php", _("Add an Attachment"), "filterType=$trans_type&trans_no=$credit_no");
    Page::footer_exit();
  }
  if (isset($_POST[Orders::CANCEL_CHANGES])) {
    $type     = $order->trans_type;
    $order_no = (is_array($order->trans_no)) ? key($order->trans_no) : $order->trans_no;
    Orders::session_delete($_POST['order_id']);
    $order = handle_new_credit($order_no);
  }
  $id = find_submit(MODE_DELETE);
  if ($id != -1) {
    $order->remove_from_order($line_no);
    Item_Line::start_focus('_stock_id_edit');
  }
  if (isset($_POST[Orders::ADD_ITEM]) && check_item_data()) {
    $order->add_line($_POST['stock_id'], Validation::input_num('qty'), Validation::input_num('price'), Validation::input_num('Disc') / 100, $_POST['description']);
    Item_Line::start_focus('_stock_id_edit');
  }
  if (isset($_POST[Orders::UPDATE_ITEM])) {
    if ($_POST[Orders::UPDATE_ITEM] != "" && check_item_data()) {
      $order->update_order_item($_POST['line_no'], Validation::input_num('qty'), Validation::input_num('price'), Validation::input_num('Disc') / 100, $_POST['description']);
    }
    Item_Line::start_focus('_stock_id_edit');
  }
  if (isset($_POST['cancelItem'])) {
    Item_Line::start_focus('_stock_id_edit');
  }
  if (isset($_POST['ProcessCredit']) && can_process($order)) {
    if ($_POST['CreditType'] == "WriteOff" && (!isset($_POST['WriteOffGLCode']) || $_POST['WriteOffGLCode'] == '')) {
      Event::warning(_("For credit notes created to write off the stock, a general ledger account is required to be selected."), 1, 0);
      Event::warning(_("Please select an account to write the cost of the stock off to, then click on Process again."), 1, 0);
      exit;
    }
    if (!isset($_POST['WriteOffGLCode'])) {
      $_POST['WriteOffGLCode'] = 0;
    }
    $credit    = copy_to_cn($order);
    $credit_no = $credit->write($_POST['WriteOffGLCode']);
    Dates::new_doc_date($credit->document_date);
    Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=$credit_no");
  } /*end of process credit note */
  start_form();
  hidden('order_id', $_POST['order_id']);
  $customer_error = Sales_Credit::header($order);
  if ($customer_error == "") {
    Table::start('tables_style2 width90 pad10');
    echo "<tr><td>";
    Sales_Credit::display_items(_("Credit Note Items"), $order);
    Sales_Credit::option_controls($order);
    echo "</td></tr>";
    Table::end();
  } else {
    Event::error($customer_error);
  }
  submit_center_first(Orders::CANCEL_CHANGES, _("Cancel Changes"), _("Revert this document entry back to its former state."));
  submit_center_last('ProcessCredit', _("Process Credit Note"), '', false);
  echo "</tr></table></div>";
  end_form();
  Page::end();
  /***
   * @param $order
   *
   * @return Sales_Order
   */
  function copy_to_cn($order)
  {
    $order->Comments      = $_POST['CreditText'];
    $order->document_date = $_POST['OrderDate'];
    $order->freight_cost  = Validation::input_num('ChargeFreightCost');
    $order->location      = (isset($_POST['location']) ? $_POST['location'] : "");
    $order->sales_type    = $_POST['sales_type_id'];
    if ($order->trans_no == 0) {
      $order->reference = $_POST['ref'];
    }
    $order->ship_via      = $_POST['ShipperID'];
    $order->dimension_id  = $_POST['dimension_id'];
    $order->dimension2_id = $_POST['dimension2_id'];

    return $order;
  }

  /**
   * @param $order
   */
  function copy_from_cn($order)
  {
    $order                      = Sales_Order::check_edit_conflicts($order);
    $_POST['CreditText']        = $order->Comments;
    $_POST['customer_id']       = $order->customer_id;
    $_POST['branch_id']         = $order->Branch;
    $_POST['OrderDate']         = $order->document_date;
    $_POST['ChargeFreightCost'] = Num::price_format($order->freight_cost);
    $_POST['location']          = $order->location;
    $_POST['sales_type_id']     = $order->sales_type;
    if ($order->trans_no == 0) {
      $_POST['ref'] = $order->reference;
    }
    $_POST['ShipperID']     = $order->ship_via;
    $_POST['dimension_id']  = $order->dimension_id;
    $_POST['dimension2_id'] = $order->dimension2_id;
    $_POST['order_id']      = $order->order_id;
    Orders::session_set($order);
  }

  /**
   * @param $trans_no
   *
   * @return Sales_Order
   */
  function handle_new_credit($trans_no)
  {
    $order = new Sales_Order(ST_CUSTCREDIT, $trans_no);
    Orders::session_delete($order->order_id);
    $order->start();
    copy_from_cn($order);

    return $order;
  }

  /**
   * @param $order
   *
   * @return bool
   */
  function can_process($order)
  {
    $input_error = 0;
    if ($order->count_items() == 0 && (!Validation::post_num('ChargeFreightCost', 0))) {
      return false;
    }
    if ($order->trans_no == 0) {
      if (!Ref::is_valid($_POST['ref'])) {
        Event::error(_("You must enter a reference."));
        JS::set_focus('ref');
        $input_error = 1;
      } elseif (!Ref::is_new($_POST['ref'], ST_CUSTCREDIT)) {
        $_POST['ref'] = Ref::get_next(ST_CUSTCREDIT);
      }
    }
    if (!Dates::is_date($_POST['OrderDate'])) {
      Event::error(_("The entered date for the credit note is invalid."));
      JS::set_focus('OrderDate');
      $input_error = 1;
    } elseif (!Dates::is_date_in_fiscalyear($_POST['OrderDate'])) {
      Event::error(_("The entered date is not in fiscal year."));
      JS::set_focus('OrderDate');
      $input_error = 1;
    }

    return ($input_error == 0);
  }

  /**
   * @return bool
   */
  function check_item_data()
  {
    if (!Validation::post_num('qty', 0)) {
      Event::error(_("The quantity must be greater than zero."));
      JS::set_focus('qty');

      return false;
    }
    if (!Validation::post_num('price', 0)) {
      Event::error(_("The entered price is negative or invalid."));
      JS::set_focus('price');

      return false;
    }
    if (!Validation::post_num('Disc', 0, 100)) {
      Event::error(_("The entered discount percent is negative, greater than 100 or invalid."));
      JS::set_focus('Disc');

      return false;
    }

    return true;
  }
