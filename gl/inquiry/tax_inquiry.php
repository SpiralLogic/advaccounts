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
  JS::set_focus('account');
  JS::open_window(800, 500);
  Page::start(_($help_context = "Tax Inquiry"), SA_TAXREP);
  // Ajax updates
  //
  if (get_post('Show')) {
    Ajax::i()->activate('trans_tbl');
  }
  if (get_post('TransFromDate') == "" && get_post('TransToDate') == "") {
    $date = Dates::today();
    $row = DB_Company::get_prefs();
    $edate = Dates::add_months($date, -$row['tax_last']);
    $edate = Dates::end_month($edate);
    $bdate = Dates::begin_month($edate);
    $bdate = Dates::add_months($bdate, -$row['tax_prd'] + 1);
    $_POST["TransFromDate"] = $bdate;
    $_POST["TransToDate"] = $edate;
  }
  tax_inquiry_controls();
  show_results();
  Page::end();
  /**

   */
  function tax_inquiry_controls() {
    start_form();
    //start_table('tablestyle2');
    start_table('tablestyle_noborder');
    start_row();
    date_cells(_("from:"), 'TransFromDate', '', NULL, -30);
    date_cells(_("to:"), 'TransToDate');
    submit_cells('Show', _("Show"), '', '', 'default');
    end_row();
    end_table();
    end_form();
  }

  /**

   */
  function show_results() {
    /*Now get the transactions */
    Display::div_start('trans_tbl');
    start_table('tablestyle');
    $th = array(_("Type"), _("Description"), _("Amount"), _("Outputs") . "/" . _("Inputs"));
    table_header($th);
    $k = 0;
    $total = 0;
    $bdate = Dates::date2sql($_POST['TransFromDate']);
    $edate = Dates::date2sql($_POST['TransToDate']);
    $taxes = GL_Trans::get_tax_summary($_POST['TransFromDate'], $_POST['TransToDate']);
    while ($tx = DB::fetch($taxes)) {
      $payable = $tx['payable'];
      $collectible = $tx['collectible'];
      $net = $collectible + $payable;
      $total += $net;
      alt_table_row_color($k);
      label_cell($tx['name'] . " " . $tx['rate'] . "%");
      label_cell(_("Charged on sales") . " (" . _("Output Tax") . "):");
      amount_cell($payable);
      amount_cell($tx['net_output']);
      end_row();
      alt_table_row_color($k);
      label_cell($tx['name'] . " " . $tx['rate'] . "%");
      label_cell(_("Paid on purchases") . " (" . _("Input Tax") . "):");
      amount_cell($collectible);
      amount_cell($tx['net_input']);
      end_row();
      alt_table_row_color($k);
      label_cell("<span class='bold'>" . $tx['name'] . " " . $tx['rate'] . "%</span>");
      label_cell("<span class='bold'>" . _("Net payable or collectible") . ":</span>");
      amount_cell($net, TRUE);
      label_cell("");
      end_row();
    }
    alt_table_row_color($k);
    label_cell("");
    label_cell("<span class='bold'>" . _("Total payable or refund") . ":</span>");
    amount_cell($total, TRUE);
    label_cell("");
    end_row();
    end_table(2);
    Display::div_end();
  }
