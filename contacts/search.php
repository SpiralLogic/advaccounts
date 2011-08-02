<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 15/11/10
 * Time: 9:50 PM
 * To change this template use File | Settings | File Templates.
 */
   $page_security = 'SA_CUSTOMER';
   $path_to_root = "..";
   include_once("includes/contacts.inc");

   if (AJAX_REFERRER) {
      if (isset($_GET['postcode']) && isset($_GET['term'])) {
         $data = Postcode::searchByPostcode($_GET['term']);
      } elseif (isset($_GET['city']) && isset($_GET['term'])) {
         $data = Postcode::searchByCity($_GET['term']);
      }

      echo json_encode($data, JSON_NUMERIC_CHECK);
      exit();
   }

   page(_($help_context = "Items"), @$_REQUEST['popup']);
   Customer::addSearchBox('customer_id', array('cell' => false, 'description' => ''));

   end_page();
