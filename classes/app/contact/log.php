<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Contact_Log {

    /**
     * @var string
     */
    static private $_table = 'contact_log';
    /**
     * @static
     *
     * @param $parent_id
     * @param $contact_name
     * @param $type
     * @param $message
     *
     * @internal param $contact_id
     * @return bool|string
     */
    public static function add($parent_id, $contact_name, $type, $message) {
      if (!isset($contact_id, $contact_name, $type, $message)) {
        return false;
      }
      $sql = "INSERT INTO " . self::$_table . " (parent_id, contact_name, parent_type,
 message) VALUES (" . DB::escape($parent_id) . "," . DB::escape($contact_name) . "," . DB::escape($type) . ",
 " . DB::escape($message) . ")";
      DB::query($sql, "Couldn't insert contact log");
      return DB::insert_id();
    }
    /**
     * @static
     *
     * @param $parent_id
     * @param $type
     *
     * @internal param $contact_id
     * @return array|bool
     */
    public static function read($parent_id, $type) {
      if (!isset($parent_id, $type) || !$parent_id) {
        return false;
      }
      $sql     = "SELECT * FROM " . self::$_table . " WHERE parent_id=" . $parent_id . " AND parent_type=" . DB::escape($type) . " ORDER BY date DESC";
      $result  = DB::query($sql, "Couldn't get contact log entries");
      $results = array();
      while ($row = DB::fetch_assoc($result)) {
        $results[] = $row;
      }
      return $results;
    }
  }
