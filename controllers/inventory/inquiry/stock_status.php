<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/


  if (isset($_GET['stock_id'])) {
    $_POST['stock_id'] = $_GET['stock_id'];
    Page::start(_($help_context = "Inventory Item Status"), SA_ITEMSSTATVIEW, TRUE);
  }
  else {
    Page::start(_($help_context = "Inventory Item Status"));
  }
  if (Input::post('stock_id')) {
    Ajax::i()->activate('status_tbl');
  }
  Validation::check(Validation::STOCK_ITEMS, _("There are no items defined in the system."));
  start_form();
  if (!Input::post('stock_id')) {
    Session::i()->setGlobal('stock_id',$_POST['stock_id']);
  }
  echo "<div class='center bold pad10 font13'> ";
  Item::cells(_("Item:"), 'stock_id', $_POST['stock_id'], FALSE, TRUE, FALSE, FALSE);
  echo "</div>";
  Session::i()->setGlobal('stock_id',$_POST['stock_id']);
  $mb_flag = WO::get_mb_flag($_POST['stock_id']);
  $kitset_or_service = FALSE;
  Display::div_start('status_tbl');
  if (Input::post('mb_flag') == STOCK_SERVICE) {
    Event::warning(_("This is a service and cannot have a stock holding, only the total quantity on outstanding sales orders is shown."), 0, 1);
    $kitset_or_service = TRUE;
  }
  $loc_details = Inv_Location::get_details($_POST['stock_id']);

  Table::start('tablestyle grid');
  if ($kitset_or_service == TRUE) {
    $th = array(_("Location"), _("Demand"));
  }
  else {
    $th = array(
      _("Location"), _("Quantity On Hand"), _("Re-Order Level"), _("Demand"), _("Available"), _("On Order")
    );
  }
  Table::header($th);
  $dec = Item::qty_dec($_POST['stock_id']);
  $j = 1;
  $k = 0; //row colour counter

  while ($myrow = DB::fetch($loc_details)) {

    $demand_qty = Item::get_demand($_POST['stock_id'], $myrow["loc_code"]);
    $demand_qty += WO::get_demand_asm_qty($_POST['stock_id'], $myrow["loc_code"]);
    $qoh = Item::get_qoh_on_date($_POST['stock_id'], $myrow["loc_code"]);
    if ($kitset_or_service == FALSE) {
      $qoo = WO::get_on_porder_qty($_POST['stock_id'], $myrow["loc_code"]);
      $qoo += WO::get_on_worder_qty($_POST['stock_id'], $myrow["loc_code"]);
      Cell::label($myrow["location_name"]);
      Cell::qty($qoh, FALSE, $dec);
      Cell::qty($myrow["reorder_level"], FALSE, $dec);
      Cell::qty($demand_qty, FALSE, $dec);
      Cell::qty($qoh - $demand_qty, FALSE, $dec);
      Cell::qty($qoo, FALSE, $dec);
      Row::end();
    }
    else {
      /* It must be a service or kitset part */
      Cell::label($myrow["location_name"]);
      Cell::qty($demand_qty, FALSE, $dec);
      Row::end();
    }
    $j++;
    If ($j == 12) {
      $j = 1;
      Table::header($th);
    }
  }
  Table::end();
  Display::div_end();
  end_form();
  Page::end();


