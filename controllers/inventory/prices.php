<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Inventory Item Sales prices"), SA_SALESPRICE, Input::_request('frame'));
  Validation::check(Validation::STOCK_ITEMS, _("There are no items defined in the system."));
  Validation::check(Validation::SALES_TYPES, _("There are no sales types in the system. Please set up sales types befor entering pricing."));
  list($Mode, $selected_id) = Page::simple_mode(true);
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
  Forms::start(false, $_SERVER['DOCUMENT_URI'] . '?frame=1');
  if (!Input::_post('stock_id')) {
    $_POST['stock_id'] = Session::_getGlobal('stock_id');
  }
  if (!Input::_request('frame')) {
    echo "<div class='bold center pad10 font15'><span class='pad10'>" . _("Item:") . '</span>';
    echo Sales_UI::items('stock_id', $_POST['stock_id'], false, true, '', array('submitonselect' => true, 'size' => 40));
    echo "<br><br><hr></div>";
  }
  Session::_setGlobal('stock_id', $_POST['stock_id']);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    if (!Validation::post_num('price', 0)) {
      $input_error = 1;
      Event::error(_("The price entered must be numeric."));
      JS::_setFocus('price');
    }
    if ($input_error != 1) {
      if ($selected_id != -1) {
        //editing an existing price
        $result = Item_Price::update($selected_id, $_POST['sales_type_id'], $_POST['curr_abrev'], Validation::input_num('price'));
        if ($result) {
          $msg = _("This price has been updated.");
        }
      } else {
        $result = Item_Price::add($_POST['stock_id'], $_POST['sales_type_id'], $_POST['curr_abrev'], Validation::input_num('price'));
        if ($result) {
          $msg = _("The new price has been added.");
        }
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
  if (Forms::isListUpdated('stock_id')) {
    Ajax::_activate('price_table');
    Ajax::_activate('price_details');
  }
  if (Forms::isListUpdated('stock_id') || isset($_POST['_curr_abrev_update']) || isset($_POST['_sales_type_id_update'])) {
    // after change of stock, currency or salestype selector
    // display default calculated price for new settings.
    // If we have this price already in db it is overwritten later.
    unset($_POST['price']);
    Ajax::_activate('price_details');
  }
  $prices_list = Item_Price::getAll($_POST['stock_id']);
  Display::div_start('price_table');
  Table::start('tablestyle grid width90 ');
  $th = array(_("Currency"), _("Sales Type"), _("Price"), "", "");
  Table::header($th);
  $k          = 0; //row colour counter
  $calculated = false;
  while ($myrow = DB::_fetch($prices_list)) {
    Cell::label($myrow["curr_abrev"]);
    Cell::label($myrow["sales_type"]);
    Cell::amount($myrow["price"]);
    Forms::buttonEditCell("Edit" . $myrow['id'], _("Edit"));
    Forms::buttonDeleteCell("Delete" . $myrow['id'], _("Delete"));
    Row::end();
  }
  Table::end();
  if (DB::_numRows($prices_list) == 0) {
    if (DB_Company::get_pref('add_pct') != -1) {
      $calculated = true;
    }
    Event::warning(_("There are no prices set up for this part."),false);
  }
  Display::div_end();
  echo "<br>";
  if ($Mode == MODE_EDIT) {
    $myrow                  = Item_Price::get($selected_id);
    $_POST['curr_abrev']    = $myrow["curr_abrev"];
    $_POST['sales_type_id'] = $myrow["sales_type_id"];
    $_POST['price']         = Num::_priceFormat($myrow["price"]);
  }
  Forms::hidden('selected_id', $selected_id);
  Forms::hidden('stock_id');
  Display::div_start('price_details');
  Table::start('tableinfo');
  GL_Currency::row(_("Currency:"), 'curr_abrev', null, true);
  Sales_Type::row(_("Sales Type:"), 'sales_type_id', null, true);
  if (!isset($_POST['price'])) {
    $_POST['price'] = Num::_priceFormat(Item_Price::get_kit(Input::_post('stock_id'), Input::_post('curr_abrev'), Input::_post('sales_type_id')));
  }
  $kit = Item_Code::get_defaults($_POST['stock_id']);
  Forms::SmallAmountRow(_("Price:"), 'price', null, '', _('per') . ' ' . $kit["units"]);
  Table::end(1);
  if ($calculated) {
    Event::warning(_("The price is calculated."), 0, 1);
  }
  Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Display::div_end();
  Forms::end();
  if (Input::_request('frame')) {
    Page::end(true);
  } else {
    Page::end();
  }
