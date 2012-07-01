<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  CLass Item_Code
  {
    /*
      item_codes table is used to store both multiply foreign codes and
      sale kits definition.
    */
    /**
     * @static
     *
     * @param     $id
     * @param     $item_code
     * @param     $stock_id
     * @param     $description
     * @param     $category
     * @param     $qty
     * @param int $foreign
     */
    public static function  update($id, $item_code, $stock_id, $description, $category, $qty, $foreign = 0)
    {
      $sql
        = "UPDATE item_codes SET
                 item_code = " . DB::escape($item_code) . ",
                 stock_id = " . DB::escape($stock_id) . ",
                 description = " . DB::escape($description) . ",
                 category_id = " . DB::escape($category) . ",
                 quantity = " . DB::escape($qty) . ",
                 is_foreign = " . DB::escape($foreign) . "
             WHERE ";
      if ($id == -1) // update with unknown $id i.e. from items table editor
      {
        $sql .= "item_code = " . DB::escape($item_code) . " AND stock_id = " . DB::escape($stock_id);
      } else {
        $sql .= "id = " . DB::escape($id);
      }
      DB::query($sql, "an item code could not be updated");
    }
    /**
     * @static
     *
     * @param     $item_code
     * @param     $stock_id
     * @param     $description
     * @param     $category
     * @param     $qty
     * @param int $foreign
     */
    public static function  add($item_code, $stock_id, $description, $category, $qty, $foreign = 0)
    {
      $id = DB::select('id')->from('item_codes')->where('item_code=', $item_code)->fetch()->one();
      $sql
          = "INSERT INTO item_codes (";
      if (isset($id['id'])) {
        $sql .= "id, ";
      }
      $sql
        .= "item_code, stock_id, description, category_id, quantity, is_foreign)
                    VALUES( ";
      if (isset($id['id'])) {
        $sql .= $id['id'] . ", ";
      }
      $sql .= DB::escape($item_code) . "," . DB::escape($stock_id) . ",
                     " . DB::escape($description) . "," . DB::escape($category) . "," . DB::escape($qty) . "," . DB::escape($foreign) . ")";
      DB::query($sql, "an item code could not be added");
    }
    /**
     * @static
     *
     * @param $id
     */
    public static function  delete($id)
    {
      $sql = "DELETE FROM item_codes WHERE id=" . DB::escape($id);
      DB::query($sql, "an item code could not be deleted");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    public static function  get($id)
    {
      $sql    = "SELECT * FROM item_codes WHERE id=" . DB::escape($id);
      $result = DB::query($sql, "item code could not be retrieved");

      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param     $stock_id
     * @param int $foreign
     *
     * @return null|PDOStatement
     */
    public static function  getAll($stock_id, $foreign = 1)
    {
      $sql    = "SELECT i.*, c.description as cat_name FROM " . "item_codes as i," . "stock_category as c
                WHERE stock_id=" . DB::escape($stock_id) . "
                AND i.category_id=c.category_id
                AND i.is_foreign=" . DB::escape($foreign);
      $result = DB::query($sql, "all item codes could not be retrieved");

      return $result;
    }
    /**
     * @static
     *
     * @param $item_code
     */
    public static function  delete_kit($item_code)
    {
      $sql = "DELETE FROM item_codes WHERE item_code=" . DB::escape($item_code);
      DB::query($sql, "an item kit could not be deleted");
    }
    /**
     * @static
     *
     * @param $item_code
     *
     * @return null|PDOStatement
     */
    public static function  get_kit($item_code)
    {
      $sql
              = "SELECT DISTINCT kit.*, item.units, comp.description as comp_name
            FROM " . "item_codes kit," . "item_codes comp
            LEFT JOIN " . "stock_master item
            ON
                item.stock_id=comp.item_code
            WHERE
                kit.stock_id=comp.item_code
                AND kit.item_code=" . DB::escape($item_code);
      $result = DB::query($sql, "item kit could not be retrieved");

      return $result;
    }
    /**
     * @static
     *
     * @param $item_code
     *
     * @return null|PDOStatement
     */
    public static function  is_kit($item_code)
    {
      $sql = "SELECT * FROM item_codes WHERE item_code=" . DB::escape($item_code);

      return DB::query($sql, "Could not do shit for some reason");
    }
    //
    //	Check if kit contains given item, optionally recursive.
    //
    /**
     * @static
     *
     * @param      $old_id
     * @param      $kit_code
     * @param      $item_code
     * @param bool $recurse
     *
     * @return int
     */
    public static function  is_item_in_kit($old_id, $kit_code, $item_code, $recurse = false)
    {
      $result = static::get_kit($kit_code);
      if ($result != 0) {
        while ($myrow = DB::fetch($result)) {
          if ($myrow['id'] == $old_id) {
            continue;
          }
          if ($myrow['stock_id'] == $item_code) {
            return 1;
          }
          if ($recurse && $myrow['item_code'] != $myrow['stock_id'] && static::is_item_in_kit($old_id, $item_code, $myrow['stock_id'], true)
          ) {
            return 1;
          }
        }
      }

      return 0;
    }
    /**
     * @static
     *
     * @param $kit_code
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    public static function  get_kit_props($kit_code)
    {
      $sql = "SELECT description, category_id FROM item_codes " . " WHERE item_code=" . DB::escape($kit_code);
      $res = DB::query($sql, "kit name query failed");

      return DB::fetch($res);
    }
    /**
     * @static
     *
     * @param $kit_code
     * @param $name
     * @param $category
     */
    public static function  update_kit_props($kit_code, $name, $category)
    {
      $sql = "UPDATE item_codes SET description=" . DB::escape($name) . ",category_id=" . DB::escape($category) . " WHERE item_code=" . DB::escape($kit_code);
      DB::query($sql, "kit name update failed");
    }
    /**
     * @static
     *
     * @param $stock_id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    public static function  get_defaults($stock_id)
    {
      $sql
              = "SELECT units, decimals, description, category_id
            FROM stock_master,item_units
            WHERE stock_id=" . DB::escape($stock_id);
      $result = DB::query($sql, "item code defaults could not be retrieved");

      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $item_code
     *
     * @return null|PDOStatement
     */
    public static function  get_where_used($item_code)
    {
      $sql = "SELECT item_code, description FROM " . "item_codes " . " WHERE stock_id=" . DB::escape($item_code) . "
                AND item_code!=" . DB::escape($item_code);

      return DB::query($sql, "where used query failed");
    }
    /**
     * @static
     *
     * @param $item_code
     *
     * @return bool
     */
    public static function get_id($item_code)
    {
      $result = DB::select('id')->from('item_codes')->where('item_code=', $item_code)->fetch()->one();

      return isset($result['id']) ? $result['id'] : false;
    }
  }

