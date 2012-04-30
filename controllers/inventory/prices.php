<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/


  Page::start(_($help_context = "Inventory Item Sales prices"), SA_SALESPRICE, Input::request('frame'));
  Validation::check(Validation::STOCK_ITEMS, _("There are no items defined in the system."));
  Validation::check(Validation::SALES_TYPES, _("There are no sales types in the system. Please set up sales types befor entering pricing."));
  list($Mode, $selected_id) = Page::simple_mode(TRUE);
  $input_error = 0;
  if (isset($_GET['stock_id'])) {
    $_POST['stock_id'] = $_GET['stock_id'];
  }
  if (isset($_GET['Item'])) {
    $_POST['stock_id'] = $_GET['Item'];
  }
  if (!isset($_POST['curr_abrev'])) {
    $_POST['curr_abrev'] = Bank_Currency::for_company();
  }
  start_form(FALSE, $_SERVER['REQUEST_URI']);
  if (!Input::post('stock_id')) {
    Session::i()->setGlobal('stock_id',$_POST['stock_id']);
  }
  if (!Input::request('frame')) {
    echo "<div class='bold center pad10 font15'><span class='pad10'>" . _("Item:") . '</span>';
    echo Sales_UI::items('stock_id', $_POST['stock_id'], FALSE, TRUE, '', array('submitonselect' => TRUE, 'size' => 40));
    echo "<br><br><hr></div>";
  }
  Session::i()->setGlobal('stock_id',$_POST['stock_id']);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    if (!Validation::post_num('price', 0)) {
      $input_error = 1;
      Event::error(_("The price entered must be numeric."));
      JS::set_focus('price');
    }
    if ($input_error != 1) {
      if ($selected_id != -1) {
        //editing an existing price
        Item_Price::update($selected_id, $_POST['sales_type_id'], $_POST['curr_abrev'], Validation::input_num('price'));
        $msg = _("This price has been updated.");
      }
      else {
        Item_Price::add($_POST['stock_id'], $_POST['sales_type_id'], $_POST['curr_abrev'], Validation::input_num('price'));
        $msg = _("The new price has been added.");
      }
      Event::success($msg);
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_DELETE) {
    //the link to delete a selected record was clicked
    Item_Price::delete($selected_id);
    Event::notice(_("The selected price has been deleted."));
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
  }
  if (list_updated('stock_id')) {
    Ajax::i()->activate('price_table');
    Ajax::i()->activate('price_details');
  }
  if (list_updated('stock_id') || isset($_POST['_curr_abrev_update']) || isset($_POST['_sales_type_id_update'])) {
    // after change of stock, currency or salestype selector
    // display default calculated price for new settings.
    // If we have this price already in db it is overwritten later.
    unset($_POST['price']);
    Ajax::i()->activate('price_details');
  }
  $prices_list = Item_Price::get_all($_POST['stock_id']);
  Display::div_start('price_table');
  if (Input::request('frame')) {
    start_table('tablestyle width90');
  }
  else {
    start_table('tablestyle width30');
  }
  $th = array(_("Currency"), _("Sales Type"), _("Price"), "", "");
  table_header($th);
  $k = 0; //row colour counter
  $calculated = FALSE;
  while ($myrow = DB::fetch($prices_list)) {
    alt_table_row_color($k);
    label_cell($myrow["curr_abrev"]);
    label_cell($myrow["sales_type"]);
    amount_cell($myrow["price"]);
    edit_button_cell("Edit" . $myrow['id'], _("Edit"));
    delete_button_cell("Delete" . $myrow['id'], _("Delete"));
    end_row();
  }
  end_table();
  if (DB::num_rows($prices_list) == 0) {
    if (DB_Company::get_pref('add_pct') != -1) {
      $calculated = TRUE;
    }
    Event::warning(_("There are no prices set up for this part."), 1);
  }
  Display::div_end();
  echo "<br>";
  if ($Mode == MODE_EDIT) {
    $myrow = Item_Price::get($selected_id);
    $_POST['curr_abrev'] = $myrow["curr_abrev"];
    $_POST['sales_type_id'] = $myrow["sales_type_id"];
    $_POST['price'] = Num::price_format($myrow["price"]);
  }
  hidden('selected_id', $selected_id);
  Display::div_start('price_details');
  start_table('tableinfo');
  GL_Currency::row(_("Currency:"), 'curr_abrev', NULL, TRUE);
  Sales_Type::row(_("Sales Type:"), 'sales_type_id', NULL, TRUE);
  if (!isset($_POST['price'])) {
    $_POST['price'] = Num::price_format(Item_Price::get_kit(get_post('stock_id'), get_post('curr_abrev'), get_post('sales_type_id')));
  }
  $kit = Item_Code::get_defaults($_POST['stock_id']);
  small_amount_row(_("Price:"), 'price', NULL, '', _('per') . ' ' . $kit["units"]);
  end_table(1);
  if ($calculated) {
    Event::warning(_("The price is calculated."), 0, 1);
  }
  submit_add_or_update_center($selected_id == -1, '', 'both');
  Display::div_end();
  end_form();
  if (Input::request('frame')) {
    Page::end(TRUE);
  }
  else {
    Page::end();
  }
