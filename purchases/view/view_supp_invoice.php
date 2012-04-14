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

  JS::open_window(900, 500);
  Page::start(_($help_context = "View Supplier Invoice"), SA_SUPPTRANSVIEW, TRUE);
  if (isset($_GET["trans_no"])) {
    $trans_no = $_GET["trans_no"];
  }
  elseif (isset($_POST["trans_no"])) {
    $trans_no = $_POST["trans_no"];
  }
  $creditor_trans = new Creditor_Trans();
  $creditor_trans->is_invoice = TRUE;
  Purch_Invoice::get($trans_no, ST_SUPPINVOICE, $creditor_trans);
  $supplier_curr_code = Bank_Currency::for_creditor($creditor_trans->supplier_id);
  Display::heading(_("SUPPLIER INVOICE") . " # " . $trans_no);
  echo "<br>";
  start_table('tablestyle width95');
  start_row();
  label_cells(_("Supplier"), $creditor_trans->supplier_name, "class='tablerowhead'");
  label_cells(_("Reference"), $creditor_trans->reference, "class='tablerowhead'");
  label_cells(_("Supplier's Reference"), $creditor_trans->supp_reference, "class='tablerowhead'");
  end_row();
  start_row();
  label_cells(_("Invoice Date"), $creditor_trans->tran_date, "class='tablerowhead'");
  label_cells(_("Due Date"), $creditor_trans->due_date, "class='tablerowhead'");
  if (!Bank_Currency::is_company($supplier_curr_code)) {
    label_cells(_("Currency"), $supplier_curr_code, "class='tablerowhead'");
  }
  end_row();
  DB_Comments::display_row(ST_SUPPINVOICE, $trans_no);
  end_table(1);
  $total_gl = Purch_GLItem::display_items($creditor_trans, 2);
  $total_grn = Purch_GRN::display_items($creditor_trans, 2);
  $display_sub_tot = Num::format($total_gl + $total_grn, User::price_dec());
  start_table('tablestyle width95');
  label_row(_("Sub Total"), $display_sub_tot, "class='right'", "class='right nowrap width15'");
  $tax_items = GL_Trans::get_tax_details(ST_SUPPINVOICE, $trans_no);
  $tax_total = Creditor_Trans::trans_tax_details($tax_items, 1, $creditor_trans->ov_gst);
  $display_total = Num::format($creditor_trans->ov_amount + $creditor_trans->ov_gst, User::price_dec());
  label_row(_("TOTAL INVOICE"), $display_total, "colspan=1 class='right'", ' class="right nowrap"');
  end_table(1);
  Display::is_voided(ST_SUPPINVOICE, $trans_no, _("This invoice has been voided."));
  if (Input::get('frame')) {
    return;
  }
  Page::end(TRUE);


