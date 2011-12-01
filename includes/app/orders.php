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
	}
