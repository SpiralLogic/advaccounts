<?php
    /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/

    Page::start(_($help_context = "View Bank Transfer"), SA_BANKTRANSVIEW, true);
    if (isset($_GET["trans_no"])) {
        $trans_no = $_GET["trans_no"];
    }
    $result = Bank_Trans::get(ST_BANKTRANSFER, $trans_no);
    if (DB::_numRows($result) != 2) {
        Event::error("Bank transfer does not contain two records");
    }
    $trans1 = DB::_fetch($result);
    $trans2 = DB::_fetch($result);
    if ($trans1["amount"] < 0) {
        $from_trans = $trans1; // from trans is the negative one
        $to_trans   = $trans2;
    } else {
        $from_trans = $trans2;
        $to_trans   = $trans1;
    }
    $company_currency  = Bank_Currency::for_company();
    $show_currencies   = false;
    $show_both_amounts = false;
    if (($from_trans['bank_curr_code'] != $company_currency) || ($to_trans['bank_curr_code'] != $company_currency)) {
        $show_currencies = true;
    }
    if ($from_trans['bank_curr_code'] != $to_trans['bank_curr_code']) {
        $show_currencies   = true;
        $show_both_amounts = true;
    }
    Display::heading($systypes_array[ST_BANKTRANSFER] . " #$trans_no");
    echo "<br>";
    Table::start('tablestyle width90');
    Row::start();
    Cell::labels(_("From Bank Account"), $from_trans['bank_account_name'], "class='tablerowhead'");
    if ($show_currencies) {
        Cell::labels(_("Currency"), $from_trans['bank_curr_code'], "class='tablerowhead'");
    }
    Cell::labels(_("Amount"), Num::_format(-$from_trans['amount'], User::price_dec()), "class='tablerowhead'", "class='alignright'");
    if ($show_currencies) {
        Row::end();
        Row::start();
    }
    Cell::labels(_("To Bank Account"), $to_trans['bank_account_name'], "class='tablerowhead'");
    if ($show_currencies) {
        Cell::labels(_("Currency"), $to_trans['bank_curr_code'], "class='tablerowhead'");
    }
    if ($show_both_amounts) {
        Cell::labels(_("Amount"), Num::_format($to_trans['amount'], User::price_dec()), "class='tablerowhead'", "class='alignright'");
    }
    Row::end();
    Row::start();
    Cell::labels(_("Date"), Dates::_sqlToDate($from_trans['trans_date']), "class='tablerowhead'");
    Cell::labels(_("Transfer Type"), $bank_transfer_types[$from_trans['account_type']], "class='tablerowhead'");
    Cell::labels(_("Reference"), $from_trans['ref'], "class='tablerowhead'");
    Row::end();
    DB_Comments::display_row(ST_BANKTRANSFER, $trans_no);
    Table::end(1);
    Display::is_voided(ST_BANKTRANSFER, $trans_no, _("This transfer has been voided."));
    Page::end(true);
