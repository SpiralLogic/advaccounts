<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 8/01/11
	 * Time: 2:43 AM
	 * To change this template use File | Settings | File Templates.
	 */
	abstract class Orders extends DB_abstract {
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
		 * @param string $post_id
		 *
		 * @internal param $id
		 * @return Sales_Order|Purch_Order
		 */
		public static function session_get($id = null) {
			if (is_null($id)) {
				if (!isset($_POST['order_id']))
					return false;
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
