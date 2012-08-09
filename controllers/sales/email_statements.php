<?php
    /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
    Page::start(_("Email Statements"), SA_OPEN);
    $sql = "SELECT DISTINCT db.*, b.email , b.phone FROM debtor_balances db, branches b WHERE db.debtor_id = b.debtor_id AND LENGTH(b.email)>0 AND b.branch_ref = 'Accounts' AND Balance>0";
    DB::query($sql, "The customer details could not be retrieved");
    $rows    = DB::fetchAll();
    $balance = $due = $overdue1 = $overdue2 = 0;
    foreach ($rows as $row) {
        $balance += $row['Balance'];
        $due += $row['Due'];
        $overdue1 += $row['Overdue1'];
        $overdue2 += $row['Overdue2'];
    }
    $view = new View('sales/email_statements');

    $view->set('totals', ['balance'=> $balance, 'due'=> $due, 'overdue1'=> $overdue1, 'overdue2'=> $overdue2]);
    $view->set('rows', $rows);
    $view->render();
    Page::end();
