<?php
  use ADV\App\Forms;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Item_Unit
  {
    /**
     * @static
     *
     * @param $selected
     * @param $abbr
     * @param $description
     * @param $decimals
     */
    public static function write($selected, $abbr, $description, $decimals) {
      if ($selected != '') {
        $sql
          = "UPDATE item_units SET
         abbr = " . DB::_escape($abbr) . ",
         name = " . DB::_escape($description) . ",
         decimals = " . DB::_escape($decimals) . "
     WHERE abbr = " . DB::_escape($selected);
      } else {
        $sql
          = "INSERT INTO item_units
            (abbr, name, decimals) VALUES( " . DB::_escape($abbr) . ",
             " . DB::_escape($description) . ", " . DB::_escape($decimals) . ")";
      }
      DB::_query($sql, "an item unit could not be updated");
    }
    /**
     * @static
     *
     * @param $unit
     */
    public static function delete($unit) {
      $sql = "DELETE FROM item_units WHERE abbr=" . DB::_escape($unit);
      DB::_query($sql, "an unit of measure could not be deleted");
    }
    /**
     * @static
     *
     * @param $unit
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public static function get($unit) {
      $sql    = "SELECT * FROM item_units WHERE abbr=" . DB::_escape($unit);
      $result = DB::_query($sql, "an unit of measure could not be retrieved");

      return DB::_fetch($result);
    }
    /**
     * @static
     *
     * @param $unit
     *
     * @return mixed
     */
    public static function desc($unit) {
      $sql    = "SELECT description FROM item_units WHERE abbr=" . DB::_escape($unit);
      $result = DB::_query($sql, "could not unit description");
      $row    = DB::_fetchRow($result);

      return $row[0];
    }
    /**
     * @static
     *
     * @param $unit
     *
     * @return bool
     */
    public static function used($unit) {
      $sql    = "SELECT COUNT(*) FROM stock_master WHERE units=" . DB::_escape($unit);
      $result = DB::_query($sql, "could not query stock master");
      $myrow  = DB::_fetchRow($result);

      return ($myrow[0] > 0);
    }
    /**
     * @static
     *
     * @param bool $all
     *
     * @return null|PDOStatement
     */
    public static function getAll($all = false) {
      $sql = "SELECT * FROM item_units";
      if (!$all) {
        $sql .= " WHERE !inactive";
      }
      $sql .= " ORDER BY name";

      return DB::_query($sql, "could not get stock categories");
    }
    /**
     * @static
     *
     * @param $stock_id
     *
     * @return mixed
     */
    public static function get_decimal($stock_id) {
      $sql
              = "SELECT decimals FROM item_units,	stock_master
        WHERE abbr=units AND stock_id=" . DB::_escape($stock_id) . " LIMIT 1";
      $result = DB::_query($sql, "could not get unit decimals");
      $row    = DB::_fetchRow($result);

      return $row[0];
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $value
     * @param bool $enabled
     */
    public static function row($label, $name, $value = null, $enabled = true) {
      $result = Item_Unit::getAll();
      echo "<tr>";
      if ($label != null) {
        echo "<td class='label'>$label</td>\n";
      }
      echo "<td>";
      while ($unit = DB::_fetch($result)) {
        $units[$unit['abbr']] = $unit['name'];
      }
      echo Forms::arraySelect($name, $value, $units, array('disabled' => !$enabled));
      echo "</td></tr>\n";
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $value
     * @param bool $enabled
     *
     * @return string
     */
    public static function select($name, $value = null, $enabled = true) {
      $result = Item_Unit::getAll();
      $units  = [];
      while ($unit = DB::_fetch($result)) {
        $units[$unit['abbr']] = $unit['name'];
      }

      return Forms::arraySelect($name, $value, $units, array('disabled' => !$enabled));
    }
  }
