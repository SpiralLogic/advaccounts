<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  abstract class Orders extends DB_abstract {

    const NEW_ORDER       = 'NewOrder';
    const MODIFY_ORDER    = 'ModifyOrder';
    const NEW_QUOTE       = 'NewQuote';
    const QUOTE_TO_ORDER  = 'QuoteToOrder';
    const MODIFY_QUOTE    = 'ModifyQuote';
    const NEW_DELIVERY    = 'NewDelivery';
    const MODIFY_DELIVERY = 'ModifyDelivery';
    const NEW_INVOICE     = 'NewInvoice';
    const MODIFY_INVOICE  = 'ModifyInvoice';
    const CLONE_ORDER     = 'CloneOrder';
    const BATCH_INVOICE   = 'BatchInvoice';
    const VIEW_INVOICE    = 'ViewInvoice';
    const MODIFY_CREDIT   = 'ModifyCredit';
    const NEW_CREDIT      = 'NewCredit';
    const PROCESS_ORDER   = 'ProcessOrder';
    const CANCEL          = 'CancelOrder';
    const CANCEL_CHANGES  = 'CancelChanges';
    const DELETE_ORDER    = 'DeleteOrder';
    const ADD_ITEM        = 'AddItem';
    const UPDATE_ITEM     = 'UpdateItem';
    const ADD             = 'add';
    const UPDATE          = 'update';
    const TYPE            = 'type';

    /**
     * @var
     */

    public $order_no;
    /**
     * @var
     */
    public $version;
    /**
     * @var
     */
    public $comments;
    /**
     * @var
     */
    public $ord_date;
    /**
     * @var
     */
    public $reference;
    /**
     * @var
     */
    public $delivery_address;
    /**
     * @var
     */
    public $salesman;
    /**
     * @var
     */
    public $freight; // $freight_cost for orders
    /**
     * @static
     *
     * @param $type
     *
     * @return void
     */
    protected static function setup($type) {
      if (!isset($_SESSION['orders'])) {
        $_SESSION['orders'] = array();
      }
      if (!isset($_SESSION['orders'][$type])) {
        $_SESSION['orders'][$type] = array();
      }
    }
    /**
     * @static
     *
     * @param null $id
     *
     * @internal param string $post_id
     * @internal param $id
     * @return \Purch_Order|\Sales_Order
     */
    public static function session_get($id = NULL) {
      if (is_null($id)) {
        if (!isset($_POST['order_id'])) {
          return FALSE;
        }
        $id = $_POST['order_id'];
      }

      list($type, $id) = explode('.', $id);
      static::setup($type);
      if (isset($_SESSION['orders'][$type][$id])) {
        return $_SESSION['orders'][$type][$id];
      }
      return FALSE;
    }

    /**
     * @static
     *
     * @param $order
     *
     * @return Sales_Order|Purch_Order
     */
    public static function session_set($order) {
      list($type, $id) = explode('.', $order->order_id);
      static::setup($type);
      $_SESSION['orders'][$type][$id] = $order;
      return $order;
    }

    /**
     * @static
     *
     * @param $order
     *
     * @return void
     */
    public static function session_start($order) {
    }

    /**
     * @static
     *
     * @param $order
     *
     * @return bool
     */
    public static function session_exists($order) {
      list($type, $id) = explode('.', $order->order_id);
      static::setup($type);
      return isset($_SESSION['orders'][$type][$id]);
    }

    /**
     * @static
     *
     * @param Purch_Order|Sales_Order|int $id Can be object or order_id number
     */
    public static function session_delete($id) {
      if (is_object($id)) {
        $id = $id->order_id;
      }
      list($type, $id) = explode('.', $id);
      static::setup($type);
      if (isset($_SESSION['orders'][$type][$id])) {
        unset($_SESSION['orders'][$type][$id]);
      }
    }
  }
