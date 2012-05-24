<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  Page::start(_($help_context = "Supplier Purchasing Data"), SA_PURCHASEPRICING, Input::request('frame'));
  Validation::check(Validation::PURCHASE_ITEMS, _("There are no purchasable inventory items defined in the system."), STOCK_PURCHASED);
  Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
  list($Mode, $selected_id) = Page::simple_mode(TRUE);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    $input_error = 0;
    if (!Input::post('stock_id')) {
      $input_error = 1;
      Event::error(_("There is no item selected."));
      JS::set_focus('stock_id');
    }
    elseif (!Validation::post_num('price', 0)) {
      $input_error = 1;
      Event::error(_("The price entered was not numeric."));
      JS::set_focus('price');
    }
    elseif (!Validation::post_num('conversion_factor')) {
      $input_error = 1;
      Event::error(_("The conversion factor entered was not numeric. The conversion factor is the number by which the price must be divided by to get the unit price in our unit of measure."));
      JS::set_focus('conversion_factor');
    }
    if ($input_error == 0) {
      if ($Mode == ADD_ITEM) {
        $sql = "INSERT INTO purch_data (supplier_id, stock_id, price, suppliers_uom,
 			conversion_factor, supplier_description) VALUES (";
        $sql .= DB::escape($_POST['supplier_id']) . ", " . DB::escape($_POST['stock_id']) . ", " . Validation::input_num('price', 0) . ", " . DB::escape($_POST['suppliers_uom']) . ", " . Validation::input_num('conversion_factor') . ", " . DB::escape($_POST['supplier_description']) . ")";
        DB::query($sql, "The supplier purchasing details could not be added");
        Event::success(_("This supplier purchasing data has been added."));
      }
      else {
        $sql = "UPDATE purch_data SET price=" . Validation::input_num('price', 0) . ",
				suppliers_uom=" . DB::escape($_POST['suppliers_uom']) . ",
				conversion_factor=" . Validation::input_num('conversion_factor') . ",
				supplier_description=" . DB::escape($_POST['supplier_description']) . "
				WHERE stock_id=" . DB::escape($_POST['stock_id']) . " AND
				supplier_id=" . DB::escape($selected_id);
        DB::query($sql, "The supplier purchasing details could not be updated");
        Event::success(_("Supplier purchasing data has been updated."));
      }
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_DELETE) {
    $sql = "DELETE FROM purch_data WHERE supplier_id=" . DB::escape($selected_id) . "
		AND stock_id=" . DB::escape($_POST['stock_id']);
    DB::query($sql, "could not delete purchasing data");
    Event::notice(_("The purchasing data item has been sucessfully deleted."));
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
  }
  if (isset($_POST['_selected_id_update'])) {
    $selected_id = $_POST['selected_id'];
    Ajax::i()->activate('_page_body');
  }
  if (list_updated('stock_id')) {
    Ajax::i()->activate('price_table');
  }
  if (Input::request('frame')) {
    if (!Input::post('stock_id') && isset($_GET['stock_id'])) {
      $_POST['stock_id'] = $_GET['stock_id'];
    }
    start_form(FALSE, $_SERVER['DOCUMENT_URI'] . '?frame=1');
  }
  else {
    start_form();
  }
  if (!Input::post('stock_id')) {
    $_POST['stock_id'] = Session::i()->getGlobal('stock_id');
  }
  if (!Input::request('frame')) {
    echo "<div class='bold center pad10 font15'><span class='pad10'>" . _("Item:") . '</span>';
    echo Item_Purchase::select('stock_id', $_POST['stock_id'], FALSE, TRUE, FALSE, FALSE);
    echo "<hr></div>";
  }
  else {
    hidden('stock_id', NULL, TRUE);
  }
  Session::i()->setGlobal('stock_id', $_POST['stock_id']);
  $mb_flag = WO::get_mb_flag($_POST['stock_id']);
  if ($mb_flag == -1) {
    Event::warning(_("Entered item is not defined. Please re-enter."));
    JS::set_focus('stock_id');
  }
  else {
    $sql    = "SELECT purch_data.*,suppliers.name," . "suppliers.curr_code
		FROM purch_data INNER JOIN suppliers
		ON purch_data.supplier_id=suppliers.supplier_id
		WHERE stock_id = " . DB::escape($_POST['stock_id']);
    $result = DB::query($sql, "The supplier purchasing details for the selected part could not be retrieved");
    Display::div_start('price_table');
    if (DB::num_rows($result) == 0) {
      Event::warning(_("There is no supplier prices set up for the product selected"));
    }
    else {
      if (Input::request('frame')) {
        Table::start('tablestyle grid width90');
      }
      else {
        Table::start('tablestyle grid width65');
      }
      $th = array(
        _("Updated"), _("Supplier"), _("Price"), _("Currency"), _("Unit"), _("Conversion Factor"), _("Supplier's Code"), "", ""
      );
      Table::header($th);
      $k = $j = 0; //row colour counter
      while ($myrow = DB::fetch($result)) {

        Cell::label(Dates::sql2date($myrow['last_update']), "style='white-space:nowrap;'");
        Cell::label($myrow["name"]);
        Cell::amountDecimal($myrow["price"]);
        Cell::label($myrow["curr_code"]);
        Cell::label($myrow["suppliers_uom"]);
        Cell::qty($myrow['conversion_factor'], FALSE, User::exrate_dec());
        Cell::label($myrow["supplier_description"]);
        edit_button_cell("Edit" . $myrow['supplier_id'], _("Edit"));
        delete_button_cell("Delete" . $myrow['supplier_id'], _("Delete"));
        Row::end();
        $j++;
        If ($j == 12) {
          $j = 1;
          Table::header($th);
        } //end of page full new headings
      } //end of while loop
      Table::end();
    }
    Display::div_end();
  }
  $dec2 = 6;
  if ($Mode == MODE_EDIT) {
    $sql                           = "SELECT purch_data.*,suppliers.name FROM purch_data
		INNER JOIN suppliers ON purch_data.supplier_id=suppliers.supplier_id
		WHERE purch_data.supplier_id=" . DB::escape($selected_id) . "
		AND purch_data.stock_id=" . DB::escape($_POST['stock_id']);
    $result                        = DB::query($sql, "The supplier purchasing details for the selected supplier and item could not be retrieved");
    $myrow                         = DB::fetch($result);
    $name                          = $myrow["name"];
    $_POST['price']                = Num::price_decimal($myrow["price"], $dec2);
    $_POST['suppliers_uom']        = $myrow["suppliers_uom"];
    $_POST['supplier_description'] = $myrow["supplier_description"];
    $_POST['conversion_factor']    = Num::exrate_format($myrow["conversion_factor"]);
  }
  Display::br();
  hidden('selected_id', $selected_id);
  Table::start('tableinfo');
  if ($Mode == MODE_EDIT) {
    hidden('supplier_id');
    Row::label(_("Supplier:"), $name);
  }
  else {
    Creditor::row(_("Supplier:"), 'supplier_id', NULL, FALSE, TRUE);
    $_POST['price'] = $_POST['suppliers_uom'] = $_POST['conversion_factor'] = $_POST['supplier_description'] = "";
  }
  amount_row(_("Price:"), 'price', NULL, '', Bank_Currency::for_creditor($selected_id), $dec2);
  text_row(_("Suppliers Unit of Measure:"), 'suppliers_uom', NULL, FALSE, 51);
  if (!isset($_POST['conversion_factor']) || $_POST['conversion_factor'] == "") {
    $_POST['conversion_factor'] = Num::exrate_format(1);
  }
  amount_row(_("Conversion Factor (to our UOM):"), 'conversion_factor', Num::exrate_format($_POST['conversion_factor']), NULL, NULL, User::exrate_dec());
  text_row(_("Supplier's Product Code:"), 'supplier_description', NULL, 50, 51);
  Table::end(1);
  submit_add_or_update_center($selected_id == -1, '', 'both');
  end_form();
  if (Input::request('frame')) {
    Page::end(TRUE);
  }
  else {
    Page::end();
  }
