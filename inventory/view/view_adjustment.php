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

  Page::start(_($help_context = "View Inventory Adjustment"), SA_ITEMSTRANSVIEW, TRUE);
  if (isset($_GET["trans_no"])) {
    $trans_no = $_GET["trans_no"];
  }
  Display::heading($systypes_array[ST_INVADJUST] . " #$trans_no");
  Display::br(1);
  $adjustment_items = Inv_Adjustment::get($trans_no);
  $k = 0;
  $header_shown = FALSE;
  while ($adjustment = DB::fetch($adjustment_items)) {
    if (!$header_shown) {
      $adjustment_type = Inv_Movement::get_type($adjustment['person_id']);
      start_table('tablestyle2 width90');
      start_row();
      label_cells(_("At Location"), $adjustment['location_name'], "class='tablerowhead'");
      label_cells(_("Reference"), $adjustment['reference'], "class='tablerowhead'", "colspan=6");
      label_cells(_("Date"), Dates::sql2date($adjustment['tran_date']), "class='tablerowhead'");
      label_cells(_("Adjustment Type"), $adjustment_type['name'], "class='tablerowhead'");
      end_row();
      DB_Comments::display_row(ST_INVADJUST, $trans_no);
      end_table();
      $header_shown = TRUE;
      echo "<br>";
      start_table('tablestyle width90');
      $th = array(
        _("Item"), _("Description"), _("Quantity"), _("Units"), _("Unit Cost")
      );
      table_header($th);
    }
    alt_table_row_color($k);
    label_cell($adjustment['stock_id']);
    label_cell($adjustment['description']);
    qty_cell($adjustment['qty'], FALSE, Item::qty_dec($adjustment['stock_id']));
    label_cell($adjustment['units']);
    amount_decimal_cell($adjustment['standard_cost']);
    end_row();
  }
  end_table(1);
  Display::is_voided(ST_INVADJUST, $trans_no, _("This adjustment has been voided."));
  Page::end(TRUE);

