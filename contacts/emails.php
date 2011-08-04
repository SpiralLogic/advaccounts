<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 5/08/11
 * Time: 5:53 AM
 *
 */

   $page_security = 'SA_CUSTOMER';
   $path_to_root = "..";
   include_once("includes/contacts.inc");
   include_once(APP_PATH."reporting/includes/reporting.inc");
   include_once(APP_PATH."reporting/includes/tcpdf.php");
   $_POST = $_GET;
   if (AJAX_REFERRER) {
      if (isset($_GET['type']) && isset($_GET['id'])) {
         if ($_GET['type']==='c') {
            echo Customer::getEmailDialogue($_GET['id']);
         }
      }
      exit();
   }   page(_($help_context = "Customer Transactions"), false, false, "");

   $row = array ('debtor_no'=>5901,'type'=>10,'trans_no'=>2442);
   
   UI::emailDialogue('c', $row['debtor_no'] . '-' . $row['type'] . '-' . $row['trans_no']);
end_page();