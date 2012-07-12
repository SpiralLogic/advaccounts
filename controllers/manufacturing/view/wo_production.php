<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  JS::openWindow(900, 500);
  Page::start(_($help_context = "View Work Order Production"), SA_MANUFTRANSVIEW, true);
  if ($_GET['trans_no'] != "") {
    $wo_production = $_GET['trans_no'];
  }
  /**
   * @param $prod_id
   */
  function display_wo_production($prod_id)
  {
    $myrow = WO_Produce::get($prod_id);
    Display::br(1);
    Table::start('tablestyle');
    $th = array(
      _("Production #"), _("Reference"), _("For Work Order #"), _("Item"), _("Quantity Manufactured"), _("Date")
    );
    Table::header($th);
    Row::start();
    Cell::label($myrow["id"]);
    Cell::label($myrow["reference"]);
    Cell::label(GL_UI::viewTrans(ST_WORKORDER, $myrow["workorder_id"]));
    Cell::label($myrow["stock_id"] . " - " . $myrow["StockDescription"]);
    Cell::qty($myrow["quantity"], false, Item::qty_dec($myrow["stock_id"]));
    Cell::label(Dates::sqlToDate($myrow["date_"]));
    Row::end();
    DB_Comments::display_row(ST_MANURECEIVE, $prod_id);
    Table::end(1);
    Display::is_voided(ST_MANURECEIVE, $prod_id, _("This production has been voided."));
  }

  Display::heading($systypes_array[ST_MANURECEIVE] . " # " . $wo_production);
  display_wo_production($wo_production);
  Display::br(2);
  Page::end(true);



