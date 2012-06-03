<?php
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
    public static function write($selected, $abbr, $description, $decimals)
    {
      if ($selected != '') {
        $sql
          = "UPDATE item_units SET
         abbr = " . DB::escape($abbr) . ",
         name = " . DB::escape($description) . ",
         decimals = " . DB::escape($decimals) . "
     WHERE abbr = " . DB::escape($selected);
      } else {
        $sql
          = "INSERT INTO item_units
            (abbr, name, decimals) VALUES( " . DB::escape($abbr) . ",
             " . DB::escape($description) . ", " . DB::escape($decimals) . ")";
      }
      DB::query($sql, "an item unit could not be updated");
    }
    /**
     * @static
     *
     * @param $unit
     */
    public static function delete($unit)
    {
      $sql = "DELETE FROM item_units WHERE abbr=" . DB::escape($unit);
      DB::query($sql, "an unit of measure could not be deleted");
    }
    /**
     * @static
     *
     * @param $unit
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    public static function get($unit)
    {
      $sql    = "SELECT * FROM item_units WHERE abbr=" . DB::escape($unit);
      $result = DB::query($sql, "an unit of measure could not be retrieved");

      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $unit
     *
     * @return mixed
     */
    public static function desc($unit)
    {
      $sql    = "SELECT description FROM item_units WHERE abbr=" . DB::escape($unit);
      $result = DB::query($sql, "could not unit description");
      $row    = DB::fetch_row($result);

      return $row[0];
    }
    /**
     * @static
     *
     * @param $unit
     *
     * @return bool
     */
    public static function used($unit)
    {
      $sql    = "SELECT COUNT(*) FROM stock_master WHERE units=" . DB::escape($unit);
      $result = DB::query($sql, "could not query stock master");
      $myrow  = DB::fetch_row($result);

      return ($myrow[0] > 0);
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
      $sql = "SELECT * FROM item_units";
      if (!$all) {
        $sql .= " WHERE !inactive";
      }
      $sql .= " ORDER BY name";

      return DB::query($sql, "could not get stock categories");
    }
    /**
     * @static
     *
     * @param $stock_id
     *
     * @return mixed
     */
    public static function get_decimal($stock_id)
    {
      $sql
              = "SELECT decimals FROM item_units,	stock_master
        WHERE abbr=units AND stock_id=" . DB::escape($stock_id) . " LIMIT 1";
      $result = DB::query($sql, "could not get unit decimals");
      $row    = DB::fetch_row($result);

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
    public static function row($label, $name, $value = null, $enabled = true)
    {
      $result = Item_Unit::get_all();
      echo "<tr>";
      if ($label != null) {
        echo "<td class='label'>$label</td>\n";
      }
      echo "<td>";
      while ($unit = DB::fetch($result)) {
        $units[$unit['abbr']] = $unit['name'];
      }
      echo Form::arraySelect($name, $value, $units, array('disabled' => !$enabled));
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
    public static function select($name, $value = null, $enabled = true)
    {
      $result = Item_Unit::get_all();
      $units  = array();
      while ($unit = DB::fetch($result)) {
        $units[$unit['abbr']] = $unit['name'];
      }

      return Form::arraySelect($name, $value, $units, array('disabled' => !$enabled));
    }
  }
