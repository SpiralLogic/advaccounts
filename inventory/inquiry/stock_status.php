<?php
  /**********************************************************************
  Copyright (C) Advanced Group PTY LTD
  Released under the terms of the GNU General Public License, GPL,
  as published by the Free Software Foundation, either version 3
  of the License, or (at your option) any later version.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
  See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
   ***********************************************************************/
  require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");

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
    $_POST['stock_id'] = Session::i()->global_stock_id;
  }
  echo "<div class='center bold pad10 font13'> ";
  Item::cells(_("Item:"), 'stock_id', $_POST['stock_id'], FALSE, TRUE, FALSE, FALSE);
  echo "</div>";
  Session::i()->global_stock_id = $_POST['stock_id'];
  $mb_flag = WO::get_mb_flag($_POST['stock_id']);
  $kitset_or_service = FALSE;
  Display::div_start('status_tbl');
  if (Input::post('mb_flag') == STOCK_SERVICE) {
    Event::warning(_("This is a service and cannot have a stock holding, only the total quantity on outstanding sales orders is shown."), 0, 1);
    $kitset_or_service = TRUE;
  }
  $loc_details = Inv_Location::get_details($_POST['stock_id']);

  start_table('tablestyle');
  if ($kitset_or_service == TRUE) {
    $th = array(_("Location"), _("Demand"));
  }
  else {
    $th = array(
      _("Location"), _("Quantity On Hand"), _("Re-Order Level"), _("Demand"), _("Available"), _("On Order")
    );
  }
  table_header($th);
  $dec = Item::qty_dec($_POST['stock_id']);
  $j = 1;
  $k = 0; //row colour counter

  while ($myrow = DB::fetch($loc_details)) {
    alt_table_row_color($k);
    $demand_qty = Item::get_demand($_POST['stock_id'], $myrow["loc_code"]);
    $demand_qty += WO::get_demand_asm_qty($_POST['stock_id'], $myrow["loc_code"]);
    $qoh = Item::get_qoh_on_date($_POST['stock_id'], $myrow["loc_code"]);
    if ($kitset_or_service == FALSE) {
      $qoo = WO::get_on_porder_qty($_POST['stock_id'], $myrow["loc_code"]);
      $qoo += WO::get_on_worder_qty($_POST['stock_id'], $myrow["loc_code"]);
      label_cell($myrow["location_name"]);
      qty_cell($qoh, FALSE, $dec);
      qty_cell($myrow["reorder_level"], FALSE, $dec);
      qty_cell($demand_qty, FALSE, $dec);
      qty_cell($qoh - $demand_qty, FALSE, $dec);
      qty_cell($qoo, FALSE, $dec);
      end_row();
    }
    else {
      /* It must be a service or kitset part */
      label_cell($myrow["location_name"]);
      qty_cell($demand_qty, FALSE, $dec);
      end_row();
    }
    $j++;
    If ($j == 12) {
      $j = 1;
      table_header($th);
    }
  }
  end_table();
  Display::div_end();
  end_form();
  Page::end();

?>
