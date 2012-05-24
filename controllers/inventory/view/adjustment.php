<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  Page::start(_($help_context = "View Inventory Adjustment"), SA_ITEMSTRANSVIEW, TRUE);
  if (isset($_GET["trans_no"])) {
    $trans_no = $_GET["trans_no"];
  }
  Display::heading($systypes_array[ST_INVADJUST] . " #$trans_no");
  Display::br(1);
  $adjustment_items = Inv_Adjustment::get($trans_no);
  $k                = 0;
  $header_shown     = FALSE;
  while ($adjustment = DB::fetch($adjustment_items)) {
    if (!$header_shown) {
      $adjustment_type = Inv_Movement::get_type($adjustment['person_id']);
      Table::start('tablestyle2 width90');
      Row::start();
      Cell::labels(_("At Location"), $adjustment['location_name'], "class='tablerowhead'");
      Cell::labels(_("Reference"), $adjustment['reference'], "class='tablerowhead'", "colspan=6");
      Cell::labels(_("Date"), Dates::sql2date($adjustment['tran_date']), "class='tablerowhead'");
      Cell::labels(_("Adjustment Type"), $adjustment_type['name'], "class='tablerowhead'");
      Row::end();
      DB_Comments::display_row(ST_INVADJUST, $trans_no);
      Table::end();
      $header_shown = TRUE;
      echo "<br>";
      Table::start('tablestyle grid width90');
      $th = array(
        _("Item"), _("Description"), _("Quantity"), _("Units"), _("Unit Cost")
      );
      Table::header($th);
    }

    Cell::label($adjustment['stock_id']);
    Cell::label($adjustment['description']);
    Cell::qty($adjustment['qty'], FALSE, Item::qty_dec($adjustment['stock_id']));
    Cell::label($adjustment['units']);
    Cell::amountDecimal($adjustment['standard_cost']);
    Row::end();
  }
  Table::end(1);
  Display::is_voided(ST_INVADJUST, $trans_no, _("This adjustment has been voided."));
  Page::end(TRUE);

