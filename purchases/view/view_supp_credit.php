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
  start_table('tablestyle2');
  start_row();
  label_cells(_("Supplier"), $creditor_trans->supplier_name, "class='tablerowhead'");
  label_cells(_("Reference"), $creditor_trans->reference, "class='tablerowhead'");
  label_cells(_("Supplier's Reference"), $creditor_trans->supp_reference, "class='tablerowhead'");
  end_row();
  start_row();
  label_cells(_("Invoice Date"), $creditor_trans->tran_date, "class='tablerowhead'");
  label_cells(_("Due Date"), $creditor_trans->due_date, "class='tablerowhead'");
  label_cells(_("Currency"), Bank_Currency::for_creditor($creditor_trans->supplier_id), "class='tablerowhead'");
  end_row();
  DB_Comments::display_row(ST_SUPPCREDIT, $trans_no);
  end_table(1);
  $total_gl = Purch_GLItem::display_items($creditor_trans, 3);
  $total_grn = Purch_GRN::display_items($creditor_trans, 2);
  $display_sub_tot = Num::format($total_gl + $total_grn, User::price_dec());
  start_table('tablestyle width95');
  label_row(_("Sub Total"), $display_sub_tot, "class='right'", " class='nowrap right width17' ");
  $tax_items = GL_Trans::get_tax_details(ST_SUPPCREDIT, $trans_no);
  Creditor_Trans::trans_tax_details($tax_items, 1);
  $display_total = Num::format(-($creditor_trans->ov_amount + $creditor_trans->ov_gst), User::price_dec());
  label_row(_("TOTAL CREDIT NOTE"), $display_total, "colspan=1 class='right'", ' class="right nowrap"');
  end_table(1);
  $voided = Display::is_voided(ST_SUPPCREDIT, $trans_no, _("This credit note has been voided."));
  if (!$voided) {
    GL_Allocation::from(PT_SUPPLIER, $creditor_trans->supplier_id, ST_SUPPCREDIT, $trans_no, -($creditor_trans->ov_amount + $creditor_trans->ov_gst));
  }
  Page::end(TRUE);

?>
