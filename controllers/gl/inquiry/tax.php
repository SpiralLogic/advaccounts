<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  JS::set_focus('account');
  JS::open_window(800, 500);
  Page::start(_($help_context = "Tax Inquiry"), SA_TAXREP);
  // Ajax updates
  //
  if (Form::getPost('Show')) {
    Ajax::i()->activate('trans_tbl');
  }
  if (Form::getPost('TransFromDate') == "" && Form::getPost('TransToDate') == "") {
    $date                   = Dates::today();
    $row                    = DB_Company::get_prefs();
    $edate                  = Dates::add_months($date, -$row['tax_last']);
    $edate                  = Dates::end_month($edate);
    $bdate                  = Dates::begin_month($edate);
    $bdate                  = Dates::add_months($bdate, -$row['tax_prd'] + 1);
    $_POST["TransFromDate"] = $bdate;
    $_POST["TransToDate"]   = $edate;
  }
  tax_inquiry_controls();
  show_results();
  Page::end();
  /**

   */
  function tax_inquiry_controls()
  {
    Form::start();
    //Table::start('tablestyle2');
    Table::start('tablestyle_noborder');
    Row::start();
     Form::dateCells(_("from:"), 'TransFromDate', '', null, -30);
     Form::dateCells(_("to:"), 'TransToDate');
    Form::submitCells('Show', _("Show"), '', '', 'default');
    Row::end();
    Table::end();
    Form::end();
  }

  /**

   */
  function show_results()
  {
    /*Now get the transactions */
    Display::div_start('trans_tbl');
    Table::start('tablestyle grid');
    $th = array(_("Type"), _("Description"), _("Amount"), _("Outputs") . "/" . _("Inputs"));
    Table::header($th);
    $k     = 0;
    $total = 0;
    $bdate = Dates::date2sql($_POST['TransFromDate']);
    $edate = Dates::date2sql($_POST['TransToDate']);
    $taxes = GL_Trans::get_tax_summary($_POST['TransFromDate'], $_POST['TransToDate']);
    while ($tx = DB::fetch($taxes)) {
      $payable     = $tx['payable'];
      $collectible = $tx['collectible'];
      $net         = $collectible + $payable;
      $total += $net;

      Cell::label($tx['name'] . " " . $tx['rate'] . "%");
      Cell::label(_("Charged on sales") . " (" . _("Output Tax") . "):");
      Cell::amount($payable);
      Cell::amount($tx['net_output']);
      Row::end();

      Cell::label($tx['name'] . " " . $tx['rate'] . "%");
      Cell::label(_("Paid on purchases") . " (" . _("Input Tax") . "):");
      Cell::amount($collectible);
      Cell::amount($tx['net_input']);
      Row::end();

      Cell::label("<span class='bold'>" . $tx['name'] . " " . $tx['rate'] . "%</span>");
      Cell::label("<span class='bold'>" . _("Net payable or collectible") . ":</span>");
      Cell::amount($net, true);
      Cell::label("");
      Row::end();
    }

    Cell::label("");
    Cell::label("<span class='bold'>" . _("Total payable or refund") . ":</span>");
    Cell::amount($total, true);
    Cell::label("");
    Row::end();
    Table::end(2);
    Display::div_end();
  }
