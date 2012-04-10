<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class GL_PaymentMethod {
    /**
     * @static
     *
     * @param     $payment_method
     * @param     $undeposited
     * @param int $inactive
     */
    static public function add($payment_method, $undeposited, $inactive = 0) {
      DB::insert('payment_methods')
        ->values(array('name' => $payment_method, 'undeposited' => $undeposited, 'inactive' => $inactive))->exec();
    }
    /**
     * @static
     *
     * @param     $id
     * @param     $payment_method
     * @param     $undeposited
     * @param int $inactive
     */
    static public function update($id, $payment_method, $undeposited, $inactive = 0) {
      DB::update('payment_methods')
        ->values(array('name' => $payment_method, 'undeposited' => $undeposited, 'inactive' => $inactive))
        ->where('id=', $id)
        ->exec();
    }
    /**
     * @static
     *
     * @param $id
     */
    static public function delete($id) {
      DB::delete('payment_methods')->where('id=', $id)->exec();
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get($id) {
      $sql = "SELECT * FROM payment_methods WHERE id=" . DB::escape($id);
      $result = DB::query($sql, "could not retreive bank account for $id");
      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     *
     * @return string
     */
    static public function select($name, $selected_id = NULL) {
      $result = DB::select('name')->from('payment_methods')->where('inactive=', 0);
      while ($row = DB::fetch($result)) {
        $payment_methods[] = $row['name'];
      }
      return array_selector($name, $selected_id, $payment_methods);
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    static public function cells($label, $name, $selected_id = NULL) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo GL_PaymentMethod::select($name, $selected_id);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    static public function row($label, $name, $selected_id = NULL) {
      echo "<tr><td class='label'>$label</td>";
      Bank_Account::type_cells(NULL, $name, $selected_id);
      echo "</tr>\n";
    }
  }
