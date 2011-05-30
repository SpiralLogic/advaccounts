<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 30/05/11
 * Time: 12:39 PM
 * To change this template use File | Settings | File Templates.
 */
	$path_to_root = "..";
	$page_security = 'SA_SALESORDER';
	include_once("$path_to_root/sales/includes/cart_class.inc");
	include_once("$path_to_root/includes/session.inc");
	include_once("$path_to_root/sales/includes/sales_ui.inc");
	include_once("$path_to_root/sales/includes/ui/sales_order_ui.inc");
	include_once("$path_to_root/sales/includes/sales_db.inc");
	include_once("$path_to_root/sales/includes/db/sales_types_db.inc");
	include_once("$path_to_root/reporting/includes/reporting.inc");
	if (!isset($_SESSION['remote_order'])) {
		global $Refs;
		processing_start();
		$_SESSION['remote_order'] = new Cart(ST_SALESORDER, array(0));
		copy_from_cart();
	}
	if (isset($_GET['item'])) {
		handle_new_item();
	}
	function handle_new_item() {

		add_to_order($_SESSION['remote_order'], $_GET['item'], 1, 10, 0, 'test',true);
	echo  $_GET['item'];
	}

	function copy_from_cart() {
		$cart = &$_SESSION['remote_order'];
		$_POST['ref'] = $cart->reference;
		$_POST['Comments'] = $cart->Comments;
		$_POST['OrderDate'] = $cart->document_date;
		$_POST['delivery_date'] = $cart->due_date;
		$_POST['cust_ref'] = $cart->cust_ref;
		$_POST['freight_cost'] = price_format($cart->freight_cost);
		$_POST['deliver_to'] = $cart->deliver_to;
		$_POST['delivery_address'] = $cart->delivery_address;
		$_POST['name'] = $cart->name;
		$_POST['phone'] = $cart->phone;
		$_POST['Location'] = $cart->Location;
		$_POST['ship_via'] = $cart->ship_via;
		$_POST['customer_id'] = $cart->customer_id;
		$_POST['branch_id'] = $cart->Branch;
		$_POST['sales_type'] = $cart->sales_type;
		$_POST['salesman'] = $cart->salesman;
		$_POST['cart_id'] = $cart->cart_id;
	}