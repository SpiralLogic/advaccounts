<?php

  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class Sales_Branch {
    /**
     * @static
     *
     * @param $branch_id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get($branch_id) {
      $sql
        = "SELECT branches.*,salesman.salesman_name
		FROM branches, salesman
		WHERE branches.salesman=salesman.salesman_code
		AND branch_id=" . DB::escape($branch_id);
      $result = DB::query($sql, "Cannot retreive a customer branch");
      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $branch_id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get_accounts($branch_id) {
      $sql
        = "SELECT receivables_account,sales_account, sales_discount_account, payment_discount_account
		FROM branches WHERE branch_id=" . DB::escape($branch_id);
      $result = DB::query($sql, "Cannot retreive a customer branch");
      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $branch_id
     *
     * @return mixed
     */
    static public function get_name($branch_id) {
      $sql
        = "SELECT br_name FROM branches
		WHERE branch_id = " . DB::escape($branch_id);
      $result = DB::query($sql, "could not retreive name for branch" . $branch_id);
      $myrow = DB::fetch_row($result);
      return $myrow[0];
    }
    /**
     * @static
     *
     * @param $group_no
     *
     * @return null|PDOStatement
     */
    static public function get_from_group($group_no) {
      $sql
        = "SELECT branch_id, debtor_no FROM branches
		WHERE group_no = " . DB::escape($group_no);
      return DB::query($sql, "could not retreive branches for group " . $group_no);
    }
    /**
     * @static
     *
     * @param $customer_no
     *
     * @return mixed
     */
    static public function get_main($customer_no) {
      $sql
        = "SELECT *
 FROM branches
 WHERE debtor_no={$customer_no}
 ORDER BY branch_id ";
      $result = DB::query($sql, "Could not retrieve any branches");
      $myrow = DB::fetch_assoc($result);
      return $myrow;
    }
  }