<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/


  JS::open_window(900, 500);
  Page::start(_($help_context = "View Supplier Credit Note"), SA_SUPPTRANSVIEW, TRUE);
  if (isset($_GET["trans_no"])) {
    $trans_no = $_GET["trans_no"];
  }
  elseif (isset($_POST["trans_no"])) {
    $trans_no = $_POST["trans_no"];
  }
  $creditor_trans = new Creditor_Trans();
  $creditor_trans->is_invoice = FALSE;
  Purch_Invoice::get($trans_no, ST_SUPPCREDIT, $creditor_trans);
  Display::heading(_("SUPPLIER CREDIT NOTE") . " # " . $trans_no);
  echo "<br>";
  Table::start('tablestyle2');
  Row::start();
  Cell::labels(_("Supplier"), $creditor_trans->supplier_name, "class='tablerowhead'");
  Cell::labels(_("Reference"), $creditor_trans->reference, "class='tablerowhead'");
  Cell::labels(_("Supplier's Reference"), $creditor_trans->supp_reference, "class='tablerowhead'");
  Row::end();
  Row::start();
  Cell::labels(_("Invoice Date"), $creditor_trans->tran_date, "class='tablerowhead'");
  Cell::labels(_("Due Date"), $creditor_trans->due_date, "class='tablerowhead'");
  Cell::labels(_("Currency"), Bank_Currency::for_creditor($creditor_trans->supplier_id), "class='tablerowhead'");
  Row::end();
  DB_Comments::display_row(ST_SUPPCREDIT, $trans_no);
  Table::end(1);
  $total_gl = Purch_GLItem::display_items($creditor_trans, 3);
  $total_grn = Purch_GRN::display_items($creditor_trans, 2);
  $display_sub_tot = Num::format($total_gl + $total_grn, User::price_dec());
  Table::start('tablestyle width95');
  Row::label(_("Sub Total"), $display_sub_tot, "class='right'", " class='nowrap right width17' ");
  $tax_items = GL_Trans::get_tax_details(ST_SUPPCREDIT, $trans_no);
  Creditor_Trans::trans_tax_details($tax_items, 1);
  $display_total = Num::format(-($creditor_trans->ov_amount + $creditor_trans->ov_gst), User::price_dec());
  Row::label(_("TOTAL CREDIT NOTE"), $display_total, "colspan=1 class='right'", ' class="right nowrap"');
  Table::end(1);
  $voided = Display::is_voided(ST_SUPPCREDIT, $trans_no, _("This credit note has been voided."));
  if (!$voided) {
    GL_Allocation::from(PT_SUPPLIER, $creditor_trans->supplier_id, ST_SUPPCREDIT, $trans_no, -($creditor_trans->ov_amount + $creditor_trans->ov_gst));
  }
  Page::end(TRUE);


