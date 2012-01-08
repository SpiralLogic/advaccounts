<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 8/01/11
	 * Time: 2:43 AM
	 * To change this template use File | Settings | File Templates.
	 */
	abstract class Orders extends DB_abstract
	{
		const NEW_ORDER = 'NewOrder';
		const MODIFY_ORDER = 'ModifyOrder';
		const NEW_QUOTE = 'NewQuote';
		const QUOTE_TO_ORDER = 'QuoteToOrder';
		const MODIFY_QUOTE = 'ModifyQuote';
		const NEW_DELIVERY = 'NewDelivery';
		const MODIFY_DELIVERY = 'ModifyDelivery';
		const NEW_INVOICE = 'NewInvoice';
		const MODIFY_INVOICE = 'ModifyInvoice';
		const CLONE_ORDER = 'CloneOrder';
		const BATCH_INVOICE = 'BatchInvoice';
		const VIEW_INVOICE = 'ViewInvoice';
		const MODIFY_CREDIT = 'ModifyCredit';
		const NEW_CREDIT = 'NewCredit';

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
		 */
		static protected function setup($type) {
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
		 * @param string $post_id
		 *
		 * @internal param $id
		 * @return Sales_Order|Purch_Order
		 */
		static public function session_get($id = null) {
			if (is_null($id)) {
				if (!isset($_POST['order_id'])){
					return false;
				}
				$id = $_POST['order_id'];
			}

			list($type, $id) = explode('.', $id);
			static::setup($type);
			if (isset($_SESSION['orders'][$type][$id])) {
				return $_SESSION['orders'][$type][$id];
			}
			return false;
		}

		/**
		 * @static
		 *
		 * @param $order
		 *
		 * @return Sales_Order|Purch_Order
		 */
		static public function session_set($order) {
			list($type, $id) = explode('.', $order->order_id);
			static::setup($type);
			$_SESSION['orders'][$type][$id] = $order;
			return $order;
		}
		/**
		 * @static
		 *
		 * @param $order
		 */
		static public function session_start($order) {
		}
		/**
		 * @static
		 *
		 * @param $order
		 *
		 * @return bool
		 */
		static public function session_exists($order) {
			list($type, $id) = explode('.', $order->order_id);
			static::setup($type);
			return isset($_SESSION['orders'][$type][$id]);
		}
		/**
		 * @static
		 *
		 * @param Purch_Order|Sales_Order|int $id Can be object or order_id number
		 */
		static public function session_delete($id) {
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
