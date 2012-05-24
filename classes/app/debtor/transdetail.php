<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Debtor_TransDetail {

    /**
     * @static
     *
     * @param $debtor_trans_type
     * @param $debtor_trans_no
     *
     * @return null|PDOStatement
     */
    static public function get($debtor_trans_type, $debtor_trans_no) {
      if (!is_array($debtor_trans_no)) {
        $debtor_trans_no = array(0 => $debtor_trans_no);
      }
      $sql
          = "SELECT debtor_trans_details.*,
		debtor_trans_details.unit_price+debtor_trans_details.unit_tax AS FullUnitPrice,
		debtor_trans_details.description As StockDescription,
		stock_master.units
		FROM debtor_trans_details,stock_master
		WHERE (";
      $tr = array();
      foreach ($debtor_trans_no as $trans_no) {
        $tr[] = 'debtor_trans_no=' . $trans_no;
      }
      $sql .= implode(' OR ', $tr);
      $sql .= ") AND debtor_trans_type=" . DB::escape($debtor_trans_type) . "
		AND stock_master.stock_id=debtor_trans_details.stock_id
		ORDER BY id";
      return DB::query($sql, "The debtor transaction detail could not be queried");
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     */
    static public function void($type, $type_no) {
      $sql
        = "UPDATE debtor_trans_details SET quantity=0, unit_price=0,
		unit_tax=0, discount_percent=0, standard_cost=0
		WHERE debtor_trans_no=" . DB::escape($type_no) . "
		AND debtor_trans_type=" . DB::escape($type);
      DB::query($sql, "The debtor transaction details could not be voided");
      // clear the stock move items
      Inv_Movement::void($type, $type_no);
    }
    /**
     * @static
     *
     * @param     $debtor_trans_type
     * @param     $debtor_trans_no
     * @param     $stock_id
     * @param     $description
     * @param     $quantity
     * @param     $unit_price
     * @param     $unit_tax
     * @param     $discount_percent
     * @param     $std_cost
     * @param int $line_id
     */
    static public function add($debtor_trans_type, $debtor_trans_no, $stock_id, $description, $quantity, $unit_price, $unit_tax, $discount_percent, $std_cost, $line_id = 0) {
      if ($line_id != 0) {
        $sql
          = "UPDATE debtor_trans_details SET
			stock_id=" . DB::escape($stock_id) . ",
			description=" . DB::escape($description) . ",
			quantity=$quantity,
			unit_price=$unit_price,
			unit_tax=$unit_tax,
			discount_percent=$discount_percent,
			standard_cost=$std_cost WHERE
			id=" . DB::escape($line_id);
      }
      else {
        $sql
          = "INSERT INTO debtor_trans_details (debtor_trans_no,
				debtor_trans_type, stock_id, description, quantity, unit_price,
				unit_tax, discount_percent, standard_cost)
			VALUES (" . DB::escape($debtor_trans_no) . ", " . DB::escape($debtor_trans_type) . ", " . DB::escape($stock_id) . ", " . DB::escape($description) . ",
				$quantity, $unit_price, $unit_tax, $discount_percent, $std_cost)";
      }
      DB::query($sql, "The debtor transaction detail could not be written");
    }
    // add a debtor-related gl transaction
    // $date_ is display date (non-sql)
    // $amount is in CUSTOMER'S currency
    /**
     * @static
     *
     * @param        $type
     * @param        $type_no
     * @param        $date_
     * @param        $account
     * @param        $dimension
     * @param        $dimension2
     * @param        $amount
     * @param        $customer_id
     * @param string $err_msg
     * @param int    $rate
     *
     * @return float
     */
    static public function add_gl_trans($type, $type_no, $date_, $account, $dimension, $dimension2, $amount, $customer_id, $err_msg = "", $rate = 0) {
      if ($err_msg == "") {
        $err_msg = "The customer GL transaction could not be inserted";
      }
      return GL_Trans::add($type, $type_no, $date_, $account, $dimension, $dimension2, "", $amount, Bank_Currency::for_debtor($customer_id), PT_CUSTOMER, $customer_id, $err_msg, $rate);
    }
  }
