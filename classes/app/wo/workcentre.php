<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class WO_WorkCentre
  {
    /**
     * @static
     *
     * @param $name
     * @param $description
     */
    public static function add($name, $description)
    {
      $sql
        = "INSERT INTO workcentres (name, description)
        VALUES (" . DB::escape($name) . "," . DB::escape($description) . ")";
      DB::query($sql, "could not add work centre");
    }
    /**
     * @static
     *
     * @param $type_id
     * @param $name
     * @param $description
     */
    public static function update($type_id, $name, $description)
    {
      $sql = "UPDATE workcentres SET name=" . DB::escape($name) . ", description=" . DB::escape($description) . "
        WHERE id=" . DB::escape($type_id);
      DB::query($sql, "could not update work centre");
    }
    /**
     * @static
     *
     * @param bool $all
     *
     * @return null|PDOStatement
     */
    public static function getAll($all = false)
    {
      $sql = "SELECT * FROM workcentres";
      if (!$all) {
        $sql .= " WHERE !inactive";
      }

      return DB::query($sql, "could not get all work centres");
    }
    /**
     * @static
     *
     * @param $type_id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    public static function get($type_id)
    {
      $sql    = "SELECT * FROM workcentres WHERE id=" . DB::escape($type_id);
      $result = DB::query($sql, "could not get work centre");

      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $type_id
     */
    public static function delete($type_id)
    {
      $sql = "DELETE FROM workcentres WHERE id=" . DB::escape($type_id);
      DB::query($sql, "could not delete work centre");
    }
  }

