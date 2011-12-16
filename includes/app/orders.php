<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 8/01/11
	 * Time: 2:43 AM
	 * To change this template use File | Settings | File Templates.
	 */

	abstract class Orders extends DB_abstract {
		public $order_no;
		public $version;
		public $comments;
		public $ord_date;
		public $reference;
		public $delivery_address;
		public $salesman;
		public $freight; // $freight_cost for orders

		protected static function setup($type) {
			if (!isset($_SESSION['orders'])) {
				$_SESSION['orders'] = array();
			}
			if (!isset($_SESSION['orders'][$type])) {
				$_SESSION['orders'][$type] = array();
			}
		}

		public static function session_get($id) {
			list($type, $id) = explode('.', $id);
			static::setup($type);
			if (isset($_SESSION['orders'][$type][$id])) {
				return $_SESSION['orders'][$type][$id];
			}
			return false;
		}

		public static function session_set($order) {
			list($type, $id) = explode('.', $order->order_id);
			static::setup($type);
			$_SESSION['orders'][$type][$id] = $order;
			return $order;
		}

				public static function session_start($order) {
				}

		public static function session_exists($order) {
			list($type, $id) = explode('.', $order->order_id);
			static::setup($type);
			return isset($_SESSION['orders'][$type][$id]);
		}

		public static function session_delete($id) {
			if (is_object($id)) $id=$id->order_id;
			list($type, $id) = explode('.', $id);
			static::setup($type);
			if (isset($_SESSION['orders'][$type][$id])) unset($_SESSION['orders'][$type][$id]);
		}
	}
