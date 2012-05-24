<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  class GL_Currency {

    /**
     * @static
     *
     * @param $curr_abrev
     * @param $symbol
     * @param $currency
     * @param $country
     * @param $hundreds_name
     * @param $auto_update
     */
    static public function update($curr_abrev, $symbol, $currency, $country,
                                  $hundreds_name, $auto_update) {
      $sql = "UPDATE currencies SET currency=" . DB::escape($currency)
        . ", curr_symbol=" . DB::escape($symbol) . ",	country=" . DB::escape($country)
        . ", hundreds_name=" . DB::escape($hundreds_name)
        . ",auto_update = " . DB::escape($auto_update)
        . " WHERE curr_abrev = " . DB::escape($curr_abrev);

      DB::query($sql, "could not update currency for $curr_abrev");
    }
    /**
     * @static
     *
     * @param $curr_abrev
     * @param $symbol
     * @param $currency
     * @param $country
     * @param $hundreds_name
     * @param $auto_update
     */
    static public function add($curr_abrev, $symbol, $currency, $country,
                               $hundreds_name, $auto_update) {
      $sql = "INSERT INTO currencies (curr_abrev, curr_symbol, currency,
			country, hundreds_name, auto_update)
		VALUES (" . DB::escape($curr_abrev) . ", " . DB::escape($symbol) . ", "
        . DB::escape($currency) . ", " . DB::escape($country) . ", "
        . DB::escape($hundreds_name) . "," . DB::escape($auto_update) . ")";

      DB::query($sql, "could not add currency for $curr_abrev");
    }
    /**
     * @static
     *
     * @param $curr_code
     */
    static public function delete($curr_code) {
      $sql = "DELETE FROM currencies WHERE curr_abrev=" . DB::escape($curr_code);
      DB::query($sql, "could not delete currency	$curr_code");

      $sql = "DELETE FROM exchange_rates WHERE curr_code='$curr_code'";
      DB::query($sql, "could not delete exchange rates for currency $curr_code");
    }
    /**
     * @static
     *
     * @param $curr_code
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get($curr_code) {
      $sql    = "SELECT * FROM currencies WHERE curr_abrev=" . DB::escape($curr_code);
      $result = DB::query($sql, "could not get currency $curr_code");

      $row = DB::fetch($result);
      return $row;
    }
    /**
     * @static
     *
     * @param bool $all
     *
     * @return null|PDOStatement
     */
    static public function get_all($all = FALSE) {
      $sql = "SELECT * FROM currencies";
      if (!$all) {
        $sql .= " WHERE !inactive";
      }
      return DB::query($sql, "could not get currencies");
    }

    // CURRENCIES
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     *
     * @return string
     */
    static public function select($name, $selected_id = NULL, $submit_on_change = FALSE) {
      $sql = "SELECT curr_abrev, currency, inactive FROM currencies";
      // default to the company currency
      return select_box($name, $selected_id, $sql, 'curr_abrev', 'currency', array(
        'select_submit' => $submit_on_change, 'default' => Bank_Currency::for_company(), 'async' => FALSE
      ));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     */
    static public function cells($label, $name, $selected_id = NULL, $submit_on_change = FALSE) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo GL_Currency::select($name, $selected_id, $submit_on_change);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     */
    static public function row($label, $name, $selected_id = NULL, $submit_on_change = FALSE) {
      echo "<tr><td class='label'>$label</td>";
      GL_Currency::cells(NULL, $name, $selected_id, $submit_on_change);
      echo "</tr>\n";
    }
  }
