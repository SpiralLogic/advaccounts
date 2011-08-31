<?php
 $page_security = 'SA_ITEM';
   $path_to_root = "..";
   include_once("items/includes/items.inc");
   if (AJAX_REFERRER) {

      if (isset($_GET['term'])) {
         $data = Item::searchOrder2($_GET['term'], $_GET['id']);
      } elseif (isset($_POST['id'])) {
         if (isset($_POST['name'])) {
            $item = new Item($_POST);
            $item->save($_POST);
         } else {
            $item = new Item($_POST['id']);
         }
         $data['item'] = $item;
      }
      if (isset($_GET['page'])) {
         $data['page'] = $_GET['page'];
      }

      echo json_encode($data, JSON_NUMERIC_CHECK);
      exit();
   }

	//include('includes/session.inc');
	page(_($help_context = "Items"), true);

	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
	echo "<pre>";

	echo UI::searchLine('stock_id', '#',array('selected' => '', 'description' => '',  'cell' => true, 'sale' => true));

	end_page(true, true);