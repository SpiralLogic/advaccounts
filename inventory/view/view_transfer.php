<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  //require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");

  Page::start(_($help_context = "View Inventory Transfer"), SA_ITEMSTRANSVIEW, TRUE);
  if (isset($_GET["trans_no"])) {
    $trans_no = $_GET["trans_no"];
  }
  $transfer_items = Inv_Transfer::get($trans_no);
  $from_trans = $transfer_items[0];
  $to_trans = $transfer_items[1];
  Display::heading($systypes_array[ST_LOCTRANSFER] . " #$trans_no");
  echo "<br>";
  start_table('tablestyle2 width90');
  start_row();
  label_cells(_("Item"), $from_trans['stock_id'] . " - " . $from_trans['description'], "class='tablerowhead'");
  label_cells(_("From Location"), $from_trans['location_name'], "class='tablerowhead'");
  label_cells(_("To Location"), $to_trans['location_name'], "class='tablerowhead'");
  end_row();
  start_row();
  label_cells(_("Reference"), $from_trans['reference'], "class='tablerowhead'");
  $adjustment_type = Inv_Movement::get_type($from_trans['person_id']);
  label_cells(_("Adjustment Type"), $adjustment_type['name'], "class='tablerowhead'");
  label_cells(_("Date"), Dates::sql2date($from_trans['tran_date']), "class='tablerowhead'");
  end_row();
  DB_Comments::display_row(ST_LOCTRANSFER, $trans_no);
  end_table(1);
  echo "<br>";
  start_table('tablestyle width90');
  $th = array(_("Item"), _("Description"), _("Quantity"), _("Units"));
  table_header($th);
  $transfer_items = Inv_Movement::get(ST_LOCTRANSFER, $trans_no);
  $k = 0;
  while ($item = DB::fetch($transfer_items)) {
    if ($item['loc_code'] == $to_trans['loc_code']) {
      alt_table_row_color($k);
      label_cell($item['stock_id']);
      label_cell($item['description']);
      qty_cell($item['qty'], FALSE, Item::qty_dec($item['stock_id']));
      label_cell($item['units']);
      end_row();
      ;
    }
  }
  end_table(1);
  Display::is_voided(ST_LOCTRANSFER, $trans_no, _("This transfer has been voided."));
  Page::end(TRUE);

