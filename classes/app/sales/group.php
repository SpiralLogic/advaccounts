<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      8/04/12
   * @link      http://www.advancedgroup.com.au
   **/
  class Sales_Group
  {
    /**
     * @static
     *
     * @param $group_no
     *
     * @return mixed
     */
    public static function get_name($group_no)
    {
      $sql    = "SELECT description FROM groups WHERE id = " . DB::escape($group_no);
      $result = DB::query($sql, "could not get group");
      $row    = DB::fetch($result);

      return $row[0];
    }
  }
