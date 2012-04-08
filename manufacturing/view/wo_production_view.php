<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");

  JS::open_window(900, 500);
  Page::start(_($help_context = "View Work Order Production"), SA_MANUFTRANSVIEW, TRUE);
  if ($_GET['trans_no'] != "") {
    $wo_production = $_GET['trans_no'];
  }
  function display_wo_production($prod_id) {
    $myrow = WO_Produce::get($prod_id);
    Display::br(1);
    start_table('tablestyle');
    $th = array(
      _("Production #"), _("Reference"), _("For Work Order #"), _("Item"), _("Quantity Manufactured"), _("Date")
    );
    table_header($th);
    start_row();
    label_cell($myrow["id"]);
    label_cell($myrow["reference"]);
    label_cell(GL_UI::trans_view(ST_WORKORDER, $myrow["workorder_id"]));
    label_cell($myrow["stock_id"] . " - " . $myrow["StockDescription"]);
    qty_cell($myrow["quantity"], FALSE, Item::qty_dec($myrow["stock_id"]));
    label_cell(Dates::sql2date($myrow["date_"]));
    end_row();
    DB_Comments::display_row(ST_MANURECEIVE, $prod_id);
    end_table(1);
    Display::is_voided(ST_MANURECEIVE, $prod_id, _("This production has been voided."));
  }

  Display::heading($systypes_array[ST_MANURECEIVE] . " # " . $wo_production);
  display_wo_production($wo_production);
  Display::br(2);
  Page::end(TRUE);



