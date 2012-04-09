<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class Purch_Line {

    public $line_no;
    public $po_detail_rec;
    public $stock_id;
    public $description;
    public $quantity;
    public $price;
    public $units;
    public $req_del_date;
    public $qty_inv;
    public $qty_received;
    public $discount;
    public $standard_cost;
    public $receive_qty;
    public $Deleted;
    /**
     * @param $line_no
     * @param $stock_item
     * @param $item_descr
     * @param $qty
     * @param $prc
     * @param $uom
     * @param $req_del_date
     * @param $qty_inv
     * @param $qty_recd
     * @param $discount
     */
    public function __construct($line_no, $stock_item, $item_descr, $qty, $prc, $uom, $req_del_date, $qty_inv, $qty_recd, $discount) {
      /* Constructor function to add a new LineDetail object with passed params */
      $this->line_no = $line_no;
      $this->stock_id = $stock_item;
      $this->description = $item_descr;
      $this->quantity = $qty;
      $this->req_del_date = $req_del_date;
      $this->price = $prc;
      $this->units = $uom;
      $this->qty_received = $qty_recd;
      $this->discount = $discount;
      $this->qty_inv = $qty_inv;
      $this->receive_qty = 0; /*initialise these last two only */
      $this->standard_cost = 0;
      $this->Deleted = FALSE;
    }
    /**
     * @static
     *
     * @param        $creditor_trans_type
     * @param        $creditor_trans_no
     * @param        $stock_id
     * @param        $description
     * @param        $gl_code
     * @param        $unit_price
     * @param        $unit_tax
     * @param        $quantity
     * @param        $grn_item_id
     * @param        $po_detail_item_id
     * @param        $memo_
     * @param string $err_msg
     * @param int    $discount
     * @param        $exp_price
     *
     * @return string
     */
    static public function add_item($creditor_trans_type, $creditor_trans_no, $stock_id, $description,
                                    $gl_code, $unit_price, $unit_tax, $quantity, $grn_item_id, $po_detail_item_id, $memo_,
                                    $err_msg = "", $discount = 0, $exp_price = -1) {
      $unit_price = ($discount == 100) ? 0 : $unit_price / (1 - $discount / 100);
      $sql
        = "INSERT INTO creditor_trans_details (creditor_trans_type, creditor_trans_no, stock_id, description, gl_code, unit_price, unit_tax, quantity,
		 	grn_item_id, po_detail_item_id, memo_, discount, exp_price) ";
      $sql .= "VALUES (" . DB::escape($creditor_trans_type) . ", " . DB::escape($creditor_trans_no) . ", "
        . DB::escape($stock_id) .
        ", " . DB::escape($description) . ", " . DB::escape($gl_code) . ", " . DB::escape($unit_price)
        . ", " . DB::escape($unit_tax) . ", " . DB::escape($quantity) . ",
			" . DB::escape($grn_item_id) . ", " . DB::escape($po_detail_item_id) . ", " . DB::escape($memo_) . ", " . DB::escape($discount) . "," . DB::escape($exp_price) . ")";
      if ($err_msg == "") {
        $err_msg = "Cannot insert a supplier transaction detail record";
      }
      DB::query($sql, $err_msg);
      return DB::insert_id();
    }
    /**
     * @static
     *
     * @param        $creditor_trans_type
     * @param        $creditor_trans_no
     * @param        $gl_code
     * @param        $amount
     * @param        $memo_
     * @param string $err_msg
     *
     * @return string
     */
    static public function add_gl_item($creditor_trans_type, $creditor_trans_no, $gl_code, $amount, $memo_, $err_msg = "") {
      return Purch_Line::add_item($creditor_trans_type, $creditor_trans_no, "", "", $gl_code, $amount,
        0, 0, /*$grn_item_id*/
        0, /*$po_detail_item_id*/
        0, $memo_, $err_msg);
    }
    /**
     * @static
     *
     * @param $creditor_trans_type
     * @param $creditor_trans_no
     *
     * @return null|PDOStatement
     */
    static public function get_for_invoice($creditor_trans_type, $creditor_trans_no) {
      $sql
        = "SELECT *, unit_price AS FullUnitPrice FROM creditor_trans_details
			WHERE creditor_trans_type = " . DB::escape($creditor_trans_type) . "
			AND creditor_trans_no = " . DB::escape($creditor_trans_no) . " ORDER BY id";
      return DB::query($sql, "Cannot retreive supplier transaction detail records");
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     */
    static public function void_for_invoice($type, $type_no) {
      $sql
        = "UPDATE creditor_trans_details SET quantity=0, unit_price=0
			WHERE creditor_trans_type = " . DB::escape($type) . " AND creditor_trans_no=" . DB::escape($type_no);
      DB::query($sql, "could not void supptrans details");
    }
  }


