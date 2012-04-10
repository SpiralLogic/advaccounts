<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class Sales_Point {
    /**
     * @static
     *
     * @param $name
     * @param $location
     * @param $account
     * @param $cash
     * @param $credit
     */
    static public function     add($name, $location, $account, $cash, $credit) {
      $sql = "INSERT INTO sales_pos (pos_name, pos_location, pos_account, cash_sale, credit_sale) VALUES (" . DB::escape($name) . "," . DB::escape($location) . "," . DB::escape($account) . ",$cash,$credit)";
      DB::query($sql, "could not add point of sale");
    }
    /**
     * @static
     *
     * @param $id
     * @param $name
     * @param $location
     * @param $account
     * @param $cash
     * @param $credit
     */
    static public function update($id, $name, $location, $account, $cash, $credit) {
      $sql = "UPDATE sales_pos SET pos_name=" . DB::escape($name) . ",pos_location=" . DB::escape($location) . ",pos_account=" . DB::escape($account) . ",cash_sale =$cash" . ",credit_sale =$credit" . " WHERE id = " . DB::escape($id);
      DB::query($sql, "could not update sales type");
    }
    /**
     * @static
     *
     * @param bool $all
     *
     * @return null|PDOStatement
     */
    static public function get_all($all = FALSE) {
      $sql = "SELECT pos.*, loc.location_name, acc.bank_account_name FROM " . "sales_pos as pos
		LEFT JOIN locations as loc on pos.pos_location=loc.loc_code
		LEFT JOIN bank_accounts as acc on pos.pos_account=acc.id";
      if (!$all) {
        $sql .= " WHERE !pos.inactive";
      }
      return DB::query($sql, "could not get all POS definitions");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get($id) {
      $sql = "SELECT pos.*, loc.location_name, acc.bank_account_name FROM " . "sales_pos as pos
		LEFT JOIN locations as loc on pos.pos_location=loc.loc_code
		LEFT JOIN bank_accounts as acc on pos.pos_account=acc.id
		WHERE pos.id=" . DB::escape($id);
      $result = DB::query($sql, "could not get POS definition");
      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return mixed
     */
    static public function get_name($id) {
      $sql = "SELECT pos_name FROM sales_pos WHERE id=" . DB::escape($id);
      $result = DB::query($sql, "could not get POS name");
      $row = DB::fetch_row($result);
      return $row[0];
    }
    /**
     * @static
     *
     * @param $id
     */
    static public function delete($id) {
      $sql = "DELETE FROM sales_pos WHERE id=" . DB::escape($id);
      DB::query($sql, "The point of sale record could not be deleted");
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $spec_option
     * @param bool $submit_on_change
     */
    static public function row($label, $name, $selected_id = NULL, $spec_option = FALSE, $submit_on_change = FALSE) {
      $sql = "SELECT id, pos_name, inactive FROM sales_pos";
      JS::default_focus($name);
      echo '<tr>';
      if ($label != NULL) {
        echo "<td class='label'>$label</td>\n";
      }
      echo "<td>";
      echo select_box($name, $selected_id, $sql, 'id', 'pos_name', array(
        'select_submit' => $submit_on_change, 'async' => TRUE, 'spec_option' => $spec_option, 'spec_id' => -1, 'order' => array('pos_name')
      ));
      echo "</td></tr>\n";
    }
    /**
     * @static
     * @return bool
     */
    static public function can_process() {
      if (strlen($_POST['name']) == 0) {
        Event::error(_("The POS name cannot be empty."));
        JS::set_focus('pos_name');
        return FALSE;
      }
      if (!check_value('cash') && !check_value('credit')) {
        Event::error(_("You must allow cash or credit sale."));
        JS::set_focus('credit');
        return FALSE;
      }
      return TRUE;
    }
  }

