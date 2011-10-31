<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 1/11/11
 * Time: 7:09 AM
 * To change this template use File | Settings | File Templates.
 */ 

	class Sales_Line
	{
		var $id;
		var $stock_id;
		var $description;
		var $units;
		var $mb_flag;
		var $tax_type;
		var $tax_type_name;
		var $src_no; // number of src doc for this line
		var $src_id;
		var $quantity;
		var $price;
		var $discount_percent;
		var $qty_done; // quantity processed on child documents
		var $qty_dispatched; // quantity selected to process
		var $qty_old = 0; // quantity dispatched before edition
		var $standard_cost;

		function __construct($stock_id, $qty, $prc, $disc_percent, $qty_done, $standard_cost, $description, $id = 0, $src_no = 0)
		{
			/* Constructor function to add a new LineDetail object with passed params */
			$this->id     = $id;
			$this->src_no = $src_no;
			$item_row     = get_item($stock_id);
			if ($item_row == null) {
				Errors::show_db_error("invalid item added to order : $stock_id", "");
			}
			$this->mb_flag = $item_row["mb_flag"];
			$this->units   = $item_row["units"];
			if ($description == null) {
				$this->description = $item_row["long_description"];
			}
			else {
				$this->description = $description;
			}
			//$this->standard_cost = $item_row["material_cost"] + $item_row["labour_cost"] + $item_row["overhead_cost"];
			$this->tax_type         = $item_row["tax_type_id"];
			$this->tax_type_name    = $item_row["tax_type_name"];
			$this->stock_id         = $stock_id;
			$this->quantity         = $qty;
			$this->qty_dispatched   = $qty;
			$this->price            = $prc;
			$this->discount_percent = $disc_percent;
			$this->qty_done         = $qty_done;
			$this->standard_cost    = $standard_cost;
		}

		// get unit price as stated on document
		function line_price()
		{
			return $this->price;
		}
	}