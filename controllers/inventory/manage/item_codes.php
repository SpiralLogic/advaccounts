<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  Page::start(_($help_context = "Foreign Item Codes"), SA_FORITEMCODE);
  Validation::check(Validation::PURCHASE_ITEMS, _("There are no inventory items defined in the system."), STOCK_PURCHASED);
  list($Mode, $selected_id) = Page::simple_mode(TRUE);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    $input_error = 0;
    if ($_POST['stock_id'] == "" || !isset($_POST['stock_id'])) {
      $input_error = 1;
      Event::error(_("There is no item selected."));
      JS::set_focus('stock_id');
    }
    elseif (!Validation::input_num('quantity')) {
      $input_error = 1;
      Event::error(_("The price entered was not positive number."));
      JS::set_focus('quantity');
    }
    elseif ($_POST['description'] == '') {
      $input_error = 1;
      Event::error(_("Item code description cannot be empty."));
      JS::set_focus('description');
    }
    elseif ($selected_id == -1) {
      $kit = Item_Code::get_kit($_POST['item_code']);
      if (DB::num_rows($kit)) {
        $input_error = 1;
        Event::error(_("This item code is already assigned to stock item or sale kit."));
        JS::set_focus('item_code');
      }
    }
    if ($input_error == 0) {
      if ($Mode == ADD_ITEM) {
        Item_Code::add($_POST['item_code'], $_POST['stock_id'], $_POST['description'], $_POST['category_id'], $_POST['quantity'], 1);
        Event::success(_("New item code has been added."));
      }
      else {
        Item_Code::update($selected_id, $_POST['item_code'], $_POST['stock_id'], $_POST['description'], $_POST['category_id'], $_POST['quantity'], 1);
        Event::success(_("Item code has been updated."));
      }
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_DELETE) {
    Item_Code::delete($selected_id);
    Event::notice(_("Item code has been sucessfully deleted."));
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    unset($_POST);
  }
  if (list_updated('stock_id')) {
    Ajax::i()->activate('_page_body');
  }
  start_form();
  if (!Input::post('stock_id')) {
    Session::i()->setGlobal('stock_id', $_POST['stock_id']);
  }
  echo "<div class='center'>" . _("Item:") . "&nbsp;";
  echo Item_Purchase::select('stock_id', $_POST['stock_id'], FALSE, TRUE, FALSE, FALSE);
  echo "<hr></div>";
  Session::i()->setGlobal('stock_id', $_POST['stock_id']);
  $result    = Item_Code::get_defaults($_POST['stock_id']);
  $dec       = $result['decimals'];
  $units     = $result['units'];
  $dflt_desc = $result['description'];
  $dflt_cat  = $result['category_id'];
  $result    = Item_Code::get_all($_POST['stock_id']);
  Display::div_start('code_table');
  Table::start('tablestyle grid width60');
  $th = array(
    _("EAN/UPC Code"), _("Quantity"), _("Units"), _("Description"), _("Category"), "", ""
  );
  Table::header($th);
  $k = $j = 0; //row colour counter
  while ($myrow = DB::fetch($result)) {

    Cell::label($myrow["item_code"]);
    Cell::qty($myrow["quantity"], $dec);
    Cell::label($units);
    Cell::label($myrow["description"]);
    Cell::label($myrow["cat_name"]);
    edit_button_cell("Edit" . $myrow['id'], _("Edit"));
    edit_button_cell("Delete" . $myrow['id'], _("Delete"));
    Row::end();
    $j++;
    If ($j == 12) {
      $j = 1;
      Table::header($th);
    } //end of page full new headings
  } //end of while loop
  Table::end();
  Display::div_end();
  if ($selected_id != '') {
    if ($Mode == MODE_EDIT) {
      $myrow                = Item_Code::get($selected_id);
      $_POST['item_code']   = $myrow["item_code"];
      $_POST['quantity']    = $myrow["quantity"];
      $_POST['description'] = $myrow["description"];
      $_POST['category_id'] = $myrow["category_id"];
    }
    hidden('selected_id', $selected_id);
  }
  else {
    $_POST['quantity']    = 1;
    $_POST['description'] = $dflt_desc;
    $_POST['category_id'] = $dflt_cat;
  }
  echo "<br>";
  Table::start('tablestyle2');
  hidden('code_id', $selected_id);
  text_row(_("UPC/EAN code:"), 'item_code', NULL, 20, 21);
  qty_row(_("Quantity:"), 'quantity', NULL, '', $units, $dec);
  text_row(_("Description:"), 'description', NULL, 50, 200);
  Item_Category::row(_("Category:"), 'category_id', NULL);
  Table::end(1);
  submit_add_or_update_center($selected_id == -1, '', 'both');
  end_form();
  Page::end();


