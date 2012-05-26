<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  class GL_Currency
  {
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
    public static function update($curr_abrev, $symbol, $currency, $country,
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
    public static function add($curr_abrev, $symbol, $currency, $country,
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
    public static function delete($curr_code)
    {
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
    public static function get($curr_code)
    {
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
    public static function get_all($all = false)
    {
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
    public static function select($name, $selected_id = null, $submit_on_change = false)
    {
      $sql = "SELECT curr_abrev, currency, inactive FROM currencies";
      // default to the company currency
      return select_box($name, $selected_id, $sql, 'curr_abrev', 'currency', array(
        'select_submit' => $submit_on_change, 'default' => Bank_Currency::for_company(), 'async' => false
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
    public static function cells($label, $name, $selected_id = null, $submit_on_change = false)
    {
      if ($label != null) {
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
    public static function row($label, $name, $selected_id = null, $submit_on_change = false)
    {
      echo "<tr><td class='label'>$label</td>";
      GL_Currency::cells(null, $name, $selected_id, $submit_on_change);
      echo "</tr>\n";
    }
  }
