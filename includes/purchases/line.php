<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 1/11/11
	 * Time: 7:04 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class Purchase_Line
	{
		Var $line_no;
		Var $po_detail_rec;
		Var $stock_id;
		Var $description;
		Var $quantity;
		Var $price;
		Var $units;
		Var $req_del_date;
		Var $qty_inv;
		Var $qty_received;
		public $discount;
		Var $standard_cost;
		Var $receive_qty;
		Var $Deleted;

		function __construct($line_no, $stock_item, $item_descr, $qty, $prc, $uom, $req_del_date, $qty_inv, $qty_recd, $discount)
		{
			/* Constructor function to add a new LineDetail object with passed params */
			$this->line_no      = $line_no;
			$this->stock_id     = $stock_item;
			$this->description  = $item_descr;
			$this->quantity     = $qty;
			$this->req_del_date = $req_del_date;
			$this->price        = $prc;
			$this->units        = $uom;
			$this->qty_received = $qty_recd;
			$this->discount     = $discount;
			$this->qty_inv      = $qty_inv;
			$this->receive_qty  = 0; /*initialise these last two only */
			$this->standard_cost = 0;
			$this->Deleted       = false;
		}
	}


