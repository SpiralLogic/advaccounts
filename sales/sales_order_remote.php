<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 30/05/11
	 * Time: 12:39 PM
	 * To change this template use File | Settings | File Templates.
	 */
	$page_security = 'SA_SALESORDER';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	if (!isset($_SESSION['remote_order'])) {
		Sales_Order::start();
		$_SESSION['remote_order'] = new Sales_Order(ST_SALESORDER, array(0));
		copy_from_cart($_SESSION['remote_order']);
	}
	if (isset($_GET['item']) && isset($_GET['new'])) {
		handle_new_remote();
	}
	function handle_new_remote()
		{
			$current_count = count($_SESSION['remote_order']->line_items);
			Sales_Order::add_line($_SESSION['remote_order'], $_GET['item'], $_GET['qty'], 10, 0, $_GET['desc'], true);
			if ($current_count == count($_SESSION['remote_order']->line_items)) {
				$data['message'] = 'No item with this code.';
			} else {
				$data['added'] = $_GET['item'] . "<br><br>" . ($current_count + 1) . " items are currently on order.";
			}
			echo $_GET['jsoncallback'] . '(' . json_encode($data) . ')';
		}

	function copy_from_cart($order)
		{
			$cart = &$order;
			$_POST['ref'] = $cart->reference;
			$_POST['Comments'] = $cart->Comments;
			$_POST['OrderDate'] = $cart->document_date;
			$_POST['delivery_date'] = $cart->due_date;
			$_POST['cust_ref'] = $cart->cust_ref;
			$_POST['freight_cost'] = Num::price_format($cart->freight_cost);
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
