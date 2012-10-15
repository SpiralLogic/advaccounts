<?php
  use ADV\Core\DB\DB;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Reorder Levels"), SA_REORDER, Input::_request('frame'));
  Validation::check(Validation::COST_ITEMS, _("There are no inventory items defined in the system (Purchased or manufactured items)."), STOCK_SERVICE);
  if (isset($_GET['stock_id'])) {
    $_POST['stock_id'] = $_GET['stock_id'];
  }
  if (Forms::isListUpdated('stock_id')) {
    Ajax::_activate('show_heading');
    Ajax::_activate('reorders');
  }
  Forms::start(false, $_SERVER['REQUEST_URI']);
  if (!Input::_post('stock_id')) {
    Session::_setGlobal('stock_id', $_POST['stock_id']);
  }
  if (!Input::_request('frame')) {
    echo "<div class='center'>" . _("Item:") . "&nbsp;";
    echo Item_UI::costable('stock_id', $_POST['stock_id'], false, true);
    echo "<hr></div>";
    Ajax::_start_div('show_heading');
    $stock_id = $_POST['stock_id'];
    if ($stock_id != "") {
      $result = DB::_query("SELECT description, units FROM stock_master WHERE stock_id=" . DB::_escape($stock_id));
      $myrow  = DB::_fetchRow($result);
      echo "<div class='center'><span class='headingtext'>$stock_id - $myrow[0]</span></div>\n";
      $units = $myrow[1];
      echo "<div class='center'><span class='headingtext'>" . _("in units of : ") . $units . "</span></div>\n";
    }
    echo "<br>";
    Ajax::_end_div();
    Session::_setGlobal('stock_id', $_POST['stock_id']);
  }
  Ajax::_start_div('reorders');
  Table::start('padded grid width30');
  $th = array(_("Location"), _("Quantity On Hand"), _("Primary Shelf"), _("Secondary Shelf"), _("Re-Order Level"));
  Table::header($th);
  $j       = 1;
  $k       = 0; //row colour counter
  $result  = Inv_Location::get_details($_POST['stock_id']);
  $updated = false;
  while ($myrow = DB::_fetch($result)) {
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
    Forms::textCells(null, 'shelf_primary' . $myrow["loc_code"], $myrow["shelf_primary"]);
    Forms::textCells(null, 'shelf_secondary' . $myrow["loc_code"], $myrow["shelf_secondary"]);
    Forms::qtyCells(null, $myrow["loc_code"], null, null, null, $dec);
    echo '</tr>';
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
  Ajax::_end_div();
  Forms::submitCenter('UpdateData', _("Update"), true, false, 'default');
  Forms::end();
  if (Input::_request('frame')) {
    Page::end(true);
  } else {
    Page::end();
  }

