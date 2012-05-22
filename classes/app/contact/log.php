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
    static private $dbTable = 'contact_log';
    /**
     * @static
     *
     * @param $contact_id
     * @param $contact_name
     * @param $type
     * @param $message
     *
     * @return bool|string
     */
    static public function add($parent_id, $contact_name, $type, $message) {
      if (!isset($contact_id,$contact_name,$type,$message)) {
        return FALSE;
      }
      $sql = "INSERT INTO " . self::$dbTable . " (parent_id, contact_name, type,
 message) VALUES (" . DB::escape($parent_id) . "," . DB::escape($contact_name) . "," . DB::escape($type) . ",
 " . DB::escape($message) . ")";
      DB::query($sql, "Couldn't insert contact log");
      return DB::insert_id();
    }
    /**
     * @static
     *
     * @param $contact_id
     * @param $type
     *
     * @return array|bool
     */
    static public function read($parent_id, $type) {
      if (!isset($parent_id,$type) || !$parent_id) {
        return FALSE;
      }
      $sql = "SELECT * FROM " . self::$dbTable . " WHERE parent_id=" . $parent_id. " AND type=" . DB::escape($type) . " ORDER BY date DESC";
      $result = DB::query($sql, "Couldn't get contact log entries");
      $results = array();
      while ($row = DB::fetch_assoc($result)) {
        $results[] = $row;
      }
      return $results;
    }
  }
