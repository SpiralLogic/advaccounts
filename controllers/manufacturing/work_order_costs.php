<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  JS::open_window(900, 500);
  Page::start(_($help_context = "Work Order Additional Costs"), SA_WORKORDERCOST);
  if (isset($_GET['trans_no']) && $_GET['trans_no'] != "") {
    $_POST['selected_id'] = $_GET['trans_no'];
  }
  if (isset($_GET[ADDED_ID])) {
    $id    = $_GET[ADDED_ID];
    $stype = ST_WORKORDER;
    Event::success(_("The additional cost has been entered."));
    Display::note(GL_UI::trans_view($stype, $id, _("View this Work Order")));
    Display::note(GL_UI::view($stype, $id, _("View the GL Journal Entries for this Work Order")), 1);
    Display::link_params("work_order_costs.php", _("Enter another additional cost."), "trans_no=$id");
    Display::link_no_params("search_work_orders.php", _("Select another &Work Order to Process"));
    Page::end();
    exit;
  }
  $wo_details = WO::get($_POST['selected_id']);
  if (strlen($wo_details[0]) == 0) {
    Event::error(_("The order number sent is not valid."));
    exit;
  }
  /**
   * @return bool
   */
  function can_process()
  {
    global $wo_details;
    if (!Validation::post_num('costs', 0)) {
      Event::error(_("The amount entered is not a valid number or less then zero."));
      JS::set_focus('costs');

      return false;
    }
    if (!Dates::is_date($_POST['date_'])) {
      Event::error(_("The entered date is invalid."));
      JS::set_focus('date_');

      return false;
    } elseif (!Dates::is_date_in_fiscalyear($_POST['date_'])) {
      Event::error(_("The entered date is not in fiscal year."));
      JS::set_focus('date_');

      return false;
    }
    if (Dates::date_diff2(Dates::sql2date($wo_details["released_date"]), $_POST['date_'], "d") > 0) {
      Event::error(_("The additional cost date cannot be before the release date of the work order."));
      JS::set_focus('date_');

      return false;
    }

    return true;
  }

  if (isset($_POST['process']) && can_process() == true) {
    DB::begin();
    GL_Trans::add_std_cost(ST_WORKORDER, $_POST['selected_id'], $_POST['date_'], $_POST['cr_acc'], 0, 0, $wo_cost_types[$_POST['PaymentType']], -Validation::input_num('costs'), PT_WORKORDER, $_POST['PaymentType']);
    $is_bank_to = Bank_Account::is($_POST['cr_acc']);
    if ($is_bank_to) {
      Bank_Trans::add(ST_WORKORDER, $_POST['selected_id'], $is_bank_to, "", $_POST['date_'], -Validation::input_num('costs'), PT_WORKORDER, $_POST['PaymentType'], Bank_Currency::for_company(), "Cannot insert a destination bank transaction");
    }
    GL_Trans::add_std_cost(ST_WORKORDER, $_POST['selected_id'], $_POST['date_'], $_POST['db_acc'], $_POST['dim1'], $_POST['dim2'], $wo_cost_types[$_POST['PaymentType']], Validation::input_num('costs'), PT_WORKORDER, $_POST['PaymentType']);
    DB::commit();
    Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=" . $_POST['selected_id']);
  }
  WO_Cost::display($_POST['selected_id']);
  start_form();
  hidden('selected_id', $_POST['selected_id']);
  //hidden('WOReqQuantity', $_POST['WOReqQuantity']);
  Table::start('tablestyle2');
  Display::br();
  yesno_list_row(_("Type:"), 'PaymentType', null, $wo_cost_types[WO_OVERHEAD], $wo_cost_types[WO_LABOUR]);
  date_row(_("Date:"), 'date_');
  $item_accounts   = Item::get_gl_code($wo_details['stock_id']);
  $_POST['db_acc'] = $item_accounts['assembly_account'];
  $sql             = "SELECT DISTINCT account_code FROM bank_accounts";
  $rs              = DB::query($sql, "could not get bank accounts");
  $r               = DB::fetch_row($rs);
  $_POST['cr_acc'] = $r[0];
  amount_row(_("Additional Costs:"), 'costs');
  GL_UI::all_row(_("Debit Account"), 'db_acc', null);
  GL_UI::all_row(_("Credit Account"), 'cr_acc', null);
  Table::end(1);
  hidden('dim1', $item_accounts["dimension_id"]);
  hidden('dim2', $item_accounts["dimension2_id"]);
  submit_center('process', _("Process Additional Cost"), true, '', true);
  end_form();
  Page::end();

