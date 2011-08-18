<?php

  $page_security = 'SA_OPEN';

  include_once("../../includes/session.inc");
  page(_($help_context = "Websales to Accounting"), @$_REQUEST['popup']);
  include('includes/session.inc');
  ini_set('display_errors', 'On');
  error_reporting(E_ALL);

  DB::select()->from('WebOrders');

  $result = DB::fetchAll();
  var_dump($result);

  end_page(true, true);
?>