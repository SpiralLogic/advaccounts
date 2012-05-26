<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Reorder Levels"), SA_REORDER, Input::request('frame'));
  Validation::check(Validation::COST_ITEMS, _("There are no inventory items defined in the system (Purchased or manufactured items)."), STOCK_SERVICE);
  if (isset($_GET['stock_id'])) {
    $_POST['stock_id'] = $_GET['stock_id'];
  }
  if (list_updated('stock_id')) {
    Ajax::i()->activate('show_heading');
    Ajax::i()->activate('reorders');
  }
  start_form(false, $_SERVER['REQUEST_URI']);
  if (!Input::post('stock_id')) {
    Session::i()->setGlobal('stock_id', $_POST['stock_id']);
  }
  if (!Input::request('frame')) {
    echo "<div class='center'>" . _("Item:") . "&nbsp;";
    echo Item_UI::costable('stock_id', $_POST['stock_id'], false, true);
    echo "<hr></div>";
    Display::div_start('show_heading');
    Display::item_heading($_POST['stock_id']);
    Display::br();
    Display::div_end();
    Session::i()->setGlobal('stock_id', $_POST['stock_id']);
  }
  Display::div_start('reorders');
  Table::start('tablestyle grid width30');
  $th = array(_("Location"), _("Quantity On Hand"), _("Primary Shelf"), _("Secondary Shelf"), _("Re-Order Level"));
  Table::header($th);
  $j       = 1;
  $k       = 0; //row colour counter
  $result  = Inv_Location::get_details($_POST['stock_id']);
  $updated = false;
  while ($myrow = DB::fetch($result)) {
    if (isset($_POST['UpdateData']) && Validation::post_num($myrow["loc_code"])) {
      $myrow["reorder_level"] = Validation::input_num($myrow["loc_code"]);
      Inv_Location::set_reorder($_POST['stock_id'], $myrow["loc_code"], Validation::input_num($myrow["loc_code"]));
      Inv_Location::set_shelves($_POST['stock_id'], $myrow["loc_code"], $_POST['shelf_primary' . $myrow["loc_code"]], $_POST["shelf_secondary" . $myrow["loc_code"]]);
      $updated = true;
    }
    $qoh = Item::get_qoh_on_date($_POST['stock_id'], $myrow["loc_code"]);
    Cell::label($myrow["location_name"]);
    $_POST[$myrow["loc_code"]] = Item::qty_format($myrow["reorder_level"], $_POST['stock_id'], $dec);
    Cell::qty($qoh, false, $dec);
    text_cells(null, 'shelf_primary' . $myrow["loc_code"], $myrow["shelf_primary"]);
    text_cells(null, 'shelf_secondary' . $myrow["loc_code"], $myrow["shelf_secondary"]);
    qty_cells(null, $myrow["loc_code"], null, null, null, $dec);
    Row::end();
    $j++;
    If ($j == 12) {
      $j = 1;
      Table::header($th);
    }
  }
  if ($updated) {
    Event::success(_("Reorder levels have been updated."));
  }
  Table::end(1);
  Display::div_end();
  submit_center('UpdateData', _("Update"), true, false, 'default');
  end_form();
  if (Input::request('frame')) {
    Page::end(true);
  } else {
    Page::end();
  }

