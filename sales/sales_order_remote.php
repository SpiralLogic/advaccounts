<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: advanced
   * Date: 30/05/11
   * Time: 12:39 PM
   * To change this template use File | Settings | File Templates.
   */
  require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
  if (!isset($_SESSION['remote_order'])) {
    $order->start();
    $order = new Sales_Order(ST_SALESORDER, array(0));
    copy_from_order($order);
  }
  if (isset($_GET['item']) && isset($_GET['new'])) {
    handle_new_remote($order);
  }
  function handle_new_remote($order) {
    $current_count = count($_SESSION['remote_order']->line_items);
    $order->add_line($_GET['item'], $_GET['qty'], 10, 0, $_GET['desc'], TRUE);
    if ($current_count == count($_SESSION['remote_order']->line_items)) {
      $data['message'] = 'No item with this code.';
    }
    else {
      $data['added'] = $_GET['item'] . "<br><br>" . ($current_count + 1) . " items are currently on order.";
    }
    echo $_GET['jsoncallback'] . '(' . json_encode($data) . ')';
  }

  function copy_from_order($order) {
    $order = &$order;
    $_POST['ref'] = $order->reference;
    $_POST['Comments'] = $order->Comments;
    $_POST['OrderDate'] = $order->document_date;
    $_POST['delivery_date'] = $order->due_date;
    $_POST['cust_ref'] = $order->cust_ref;
    $_POST['freight_cost'] = Num::price_format($order->freight_cost);
    $_POST['deliver_to'] = $order->deliver_to;
    $_POST['delivery_address'] = $order->delivery_address;
    $_POST['name'] = $order->name;
    $_POST['phone'] = $order->phone;
    $_POST['location'] = $order->location;
    $_POST['ship_via'] = $order->ship_via;
    $_POST['customer_id'] = $order->customer_id;
    $_POST['branch_id'] = $order->Branch;
    $_POST['sales_type'] = $order->sales_type;
    $_POST['salesman'] = $order->salesman;
    $_POST['order_id'] = $order->order_id;
  }
