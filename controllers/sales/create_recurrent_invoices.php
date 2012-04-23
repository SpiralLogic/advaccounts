<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  JS::open_window(900, 600);
  Page::start(_($help_context = "Create and Print Recurrent Invoices"), SA_SALESINVOICE);
  if (isset($_GET['recurrent'])) {
    $date = Dates::today();
    if (Dates::is_date_in_fiscalyear($date)) {
      $invs = array();
      $sql = "SELECT * FROM recurrent_invoices WHERE id=" . DB::escape($_GET['recurrent']);
      $result = DB::query($sql, "could not get recurrent invoice");
      $myrow = DB::fetch($result);
      if ($myrow['debtor_no'] == 0) {
        $cust = Sales_Branch::get_from_group($myrow['group_no']);
        while ($row = DB::fetch($cust)) {
          $invs[] = Sales_Invoice::create_recurrent($row['debtor_no'], $row['branch_id'], $myrow['order_no'], $myrow['id']);
        }
      }
      else {
        $invs[] = Sales_Invoice::create_recurrent($myrow['debtor_no'], $myrow['group_no'], $myrow['order_no'], $myrow['id']);
      }
      if (count($invs) > 0) {
        $min = min($invs);
        $max = max($invs);
      }
      else {
        $min = $max = 0;
      }
      Event::success(sprintf(_("%s recurrent invoice(s) created, # $min - # $max."), count($invs)));
      if (count($invs) > 0) {
        $ar = array(
          'PARAM_0' => $min . "-" . ST_SALESINVOICE,
          'PARAM_1' => $max . "-" . ST_SALESINVOICE,
          'PARAM_2' => "",
          'PARAM_3' => 0,
          'PARAM_4' => 0,
          'PARAM_5' => "",
          'PARAM_6' => ST_SALESINVOICE
        );
        Event::warning(Reporting::print_link(_("&Print Recurrent Invoices # $min - # $max"), 107, $ar), 0, 1);
        $ar['PARAM_3'] = 1;
        Event::warning(Reporting::print_link(_("&Email Recurrent Invoices # $min - # $max"), 107, $ar), 0, 1);
      }
    }
    else {
      Event::error(_("The entered date is not in fiscal year."));
    }
  }
  $sql = "SELECT * FROM recurrent_invoices ORDER BY description, group_no, debtor_no";
  $result = DB::query($sql, "could not get recurrent invoices");
  start_table('tablestyle width70');
  $th = array(
    _("Description"), _("Template No"), _("Customer"), _("Branch") . "/" . _("Group"), _("Days"), _("Monthly"), _("Begin"), _("End"), _("Last Created"), ""
  );
  table_header($th);
  $k = 0;
  $today = Dates::add_days(Dates::today(), 1);
  $due = FALSE;
  while ($myrow = DB::fetch($result)) {
    $begin = Dates::sql2date($myrow["begin"]);
    $end = Dates::sql2date($myrow["end"]);
    $last_sent = Dates::sql2date($myrow["last_sent"]);
    if ($myrow['monthly'] > 0) {
      $due_date = Dates::begin_month($last_sent);
    }
    else {
      $due_date = $last_sent;
    }
    $due_date = Dates::add_months($due_date, $myrow['monthly']);
    $due_date = Dates::add_days($due_date, $myrow['days']);
    $overdue = Dates::date1_greater_date2($today, $due_date) && Dates::date1_greater_date2($today, $begin) && Dates::date1_greater_date2($end, $today);
    if ($overdue) {
      start_row("class='overduebg'");
      $due = TRUE;
    }
    else {
      alt_table_row_color($k);
    }
    label_cell($myrow["description"]);
    label_cell(Debtor::trans_view(30, $myrow["order_no"]));
    if ($myrow["debtor_no"] == 0) {
      label_cell("");
      label_cell(Sales_Group::get_name($myrow["group_no"]));
    }
    else {
      label_cell(Debtor::get_name($myrow["debtor_no"]));
      label_cell(Sales_Branch::get_name($myrow['group_no']));
    }
    label_cell($myrow["days"]);
    label_cell($myrow['monthly']);
    label_cell($begin);
    label_cell($end);
    label_cell($last_sent);
    if ($overdue) {
      label_cell("<a href='/sales/create_recurrent_invoices.php?recurrent=" . $myrow["id"] . "'>" . _("Create Invoices") . "</a>");
    }
    else {
      label_cell("");
    }
    end_row();
  }
  end_table();
  if ($due) {
    Event::warning(_("Marked items are due."), 1, 0, "class='overduefg'");
  }
  else {
    Event::warning(_("No recurrent invoices are due."), 1, 0);
  }
  echo '<br>';
  Page::end();
