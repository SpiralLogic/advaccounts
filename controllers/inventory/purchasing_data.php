<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  Page::start(_($help_context = "Supplier Purchasing Data"), SA_PURCHASEPRICING, Input::_request('frame'));
  Validation::check(Validation::PURCHASE_ITEMS, _("There are no purchasable inventory items defined in the system."), STOCK_PURCHASED);
  Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
  list($Mode, $selected_id) = Page::simple_mode(true);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    $input_error = 0;
    if (!Input::_post('stock_id')) {
      $input_error = 1;
      Event::error(_("There is no item selected."));
      JS::_setFocus('stock_id');
    } elseif (!Validation::post_num('price', 0)) {
      $input_error = 1;
      Event::error(_("The price entered was not numeric."));
      JS::_setFocus('price');
    } elseif (!Validation::post_num('conversion_factor')) {
      $input_error = 1;
      Event::error(_("The conversion factor entered was not numeric. The conversion factor is the number by which the price must be divided by to get the unit price in our unit of measure."));
      JS::_setFocus('conversion_factor');
    }
    if ($input_error == 0) {
      if ($Mode == ADD_ITEM) {
        $sql
          = "INSERT INTO purch_data (creditor_id, stock_id, price, suppliers_uom,
             conversion_factor, supplier_description) VALUES (";
        $sql .= DB::_escape($_POST['creditor_id']) . ", " . DB::_escape($_POST['stock_id']) . ", " . Validation::input_num('price', 0) . ", " . DB::_escape($_POST['suppliers_uom']) . ", " . Validation::input_num('conversion_factor') . ", " . DB::_escape($_POST['supplier_description']) . ")";
        DB::_query($sql, "The supplier purchasing details could not be added");
        Event::success(_("This supplier purchasing data has been added."));
      } else {
        $sql = "UPDATE purch_data SET price=" . Validation::input_num('price', 0) . ",
                suppliers_uom=" . DB::_escape($_POST['suppliers_uom']) . ",
                conversion_factor=" . Validation::input_num('conversion_factor') . ",
                supplier_description=" . DB::_escape($_POST['supplier_description']) . "
                WHERE stock_id=" . DB::_escape($_POST['stock_id']) . " AND
                creditor_id=" . DB::_escape($selected_id);
        DB::_query($sql, "The supplier purchasing details could not be updated");
        Event::success(_("Supplier purchasing data has been updated."));
      }
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_DELETE) {
    $sql = "DELETE FROM purch_data WHERE creditor_id=" . DB::_escape($selected_id) . "
        AND stock_id=" . DB::_escape($_POST['stock_id']);
    DB::_query($sql, "could not delete purchasing data");
    Event::notice(_("The purchasing data item has been sucessfully deleted."));
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
  }
  if (isset($_POST['_selected_id_update'])) {
    $selected_id = $_POST['selected_id'];
    Ajax::_activate('_page_body');
  }
  if (Forms::isListUpdated('stock_id')) {
    Ajax::_activate('price_table');
  }
  if (Input::_request('frame')) {
    if (!Input::_post('stock_id') && isset($_GET['stock_id'])) {
      $_POST['stock_id'] = $_GET['stock_id'];
    }
    Forms::start(false, $_SERVER['DOCUMENT_URI'] . '?frame=1');
  } else {
    Forms::start();
  }
  if (!Input::_post('stock_id')) {
    $_POST['stock_id'] = Session::_getGlobal('stock_id');
  }
  if (!Input::_request('frame')) {
    echo "<div class='bold center pad10 font15'><span class='pad10'>" . _("Item:") . '</span>';
    echo Item_Purchase::select('stock_id', $_POST['stock_id'], false, true, false, false);
    echo "<hr></div>";
  } else {
    Forms::hidden('stock_id', null, true);
  }
  Session::_setGlobal('stock_id', $_POST['stock_id']);
  $mb_flag = WO::get_mb_flag($_POST['stock_id']);
  if ($mb_flag == -1) {
    Event::warning(_("Entered item is not defined. Please re-enter."));
    JS::_setFocus('stock_id');
  } else {
    $sql    = "SELECT purch_data.*,suppliers.name," . "suppliers.curr_code
        FROM purch_data INNER JOIN suppliers
        ON purch_data.creditor_id=suppliers.creditor_id
        WHERE stock_id = " . DB::_escape($_POST['stock_id']);
    $result = DB::_query($sql, "The supplier purchasing details for the selected part could not be retrieved");
    Display::div_start('price_table');
    if (DB::_numRows($result) == 0) {
      Event::warning(_("There are no supplier prices set up for the product selected"),false);
    } else {
      if (Input::_request('frame')) {
        Table::start('tablestyle grid width90');
      } else {
        Table::start('tablestyle grid width65');
      }
      $th = array(
        _("Updated"), _("Supplier"), _("Price"), _("Currency"), _("Unit"), _("Conversion Factor"), _("Supplier's Code"), "", ""
      );
      Table::header($th);
      $k = $j = 0; //row colour counter
      while ($myrow = DB::_fetch($result)) {

        Cell::label(Dates::_sqlToDate($myrow['last_update']), "style='white-space:nowrap;'");
        Cell::label($myrow["name"]);
        Cell::amountDecimal($myrow["price"]);
        Cell::label($myrow["curr_code"]);
        Cell::label($myrow["suppliers_uom"]);
        Cell::qty($myrow['conversion_factor'], false, User::exrate_dec());
        Cell::label($myrow["supplier_description"]);
        Forms::buttonEditCell("Edit" . $myrow['creditor_id'], _("Edit"));
        Forms::buttonDeleteCell("Delete" . $myrow['creditor_id'], _("Delete"));
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
    $sql
                                   = "SELECT purch_data.*,suppliers.name FROM purch_data
        INNER JOIN suppliers ON purch_data.creditor_id=suppliers.creditor_id
        WHERE purch_data.creditor_id=" . DB::_escape($selected_id) . "
        AND purch_data.stock_id=" . DB::_escape($_POST['stock_id']);
    $result                        = DB::_query($sql, "The supplier purchasing details for the selected supplier and item could not be retrieved");
    $myrow                         = DB::_fetch($result);
    $name                          = $myrow["name"];
    $_POST['price']                = Num::_priceDecimal($myrow["price"], $dec2);
    $_POST['suppliers_uom']        = $myrow["suppliers_uom"];
    $_POST['supplier_description'] = $myrow["supplier_description"];
    $_POST['conversion_factor']    = Num::_exrateFormat($myrow["conversion_factor"]);
  }
  Display::br();
  Forms::hidden('selected_id', $selected_id);
  Table::start('tableinfo');
  if ($Mode == MODE_EDIT) {
    Forms::hidden('creditor_id');
    Row::label(_("Supplier:"), $name);
  } else {
    Creditor::row(_("Supplier:"), 'creditor_id', null, false, true);
    $_POST['price'] = $_POST['suppliers_uom'] = $_POST['conversion_factor'] = $_POST['supplier_description'] = "";
  }
  Forms::AmountRow(_("Price:"), 'price', null, '', Bank_Currency::for_creditor($selected_id), $dec2);
  Forms::textRow(_("Suppliers Unit of Measure:"), 'suppliers_uom', null, null, 51);
  if (!isset($_POST['conversion_factor']) || $_POST['conversion_factor'] == "") {
    $_POST['conversion_factor'] = Num::_exrateFormat(1);
  }
  Forms::AmountRow(_("Conversion Factor (to our UOM):"), 'conversion_factor', Num::_exrateFormat($_POST['conversion_factor']), null, null, User::exrate_dec());
  Forms::textRow(_("Supplier's Product Code:"), 'supplier_description', null, null, 51);
  Table::end(1);
  Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Forms::end();
  if (Input::_request('frame')) {
    Page::end(true);
  } else {
    Page::end();
  }
