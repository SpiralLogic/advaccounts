<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class Printer {
    /**
     * @static
     *
     * @param $id
     * @param $name
     * @param $descr
     * @param $queue
     * @param $host
     * @param $port
     * @param $timeout
     *
     * @return null|PDOStatement
     */
    static public function write_def($id, $name, $descr, $queue, $host, $port, $timeout) {
      if ($id > 0) {
        $sql = "UPDATE printers SET description=" . DB::escape($descr) . ",name=" . DB::escape($name) . ",queue=" . DB::escape($queue) . ",host=" . DB::escape($host) . ",port=" . DB::escape($port) . ",timeout=" . DB::escape($timeout) . " WHERE id=" . DB::escape($id);
      }
      else {
        $sql = "INSERT INTO printers (" . "name,description,queue,host,port,timeout) " . "VALUES (" . DB::escape($name) . "," . DB::escape($descr) . "," . DB::escape($queue) . "," . DB::escape($host) . "," . DB::escape($port) . "," . DB::escape($timeout) . ")";
      }
      return DB::query($sql, "could not write printer definition");
    }
    /**
     * @static
     * @return null|PDOStatement
     */
    static public function get_all() {
      $sql = "SELECT * FROM printers";
      return DB::query($sql, "could not get printer definitions");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get($id) {
      $sql = "SELECT * FROM printers WHERE id=" . DB::escape($id);
      $result = DB::query($sql, "could not get printer definition");
      return DB::fetch($result);
    }

    //============================================================================
    // printer profiles static public functions
    //
    /**
     * @static
     *
     * @param $name
     * @param $dest
     *
     * @return bool
     */
    static public function update_profile($name, $dest) {
      foreach ($dest as $rep => $printer) {
        if ($printer != '' || $rep == '') {
          $sql = "REPLACE INTO print_profiles " . "(profile, report, printer) VALUES (" . DB::escape($name) . "," . DB::escape($rep) . "," . DB::escape($printer) . ")";
        }
        else {
          $sql = "DELETE FROM print_profiles WHERE (" . "report=" . DB::escape($rep) . " AND profile=" . DB::escape($name) . ")";
        }
        $result = DB::query($sql, "could not update printing profile");
        if (!$result) {
          return FALSE;
        }
      }
      return TRUE;
    }

    //
    //	Get destination for report defined in given printing profile.
    //
    /**
     * @static
     *
     * @param $profile
     * @param $report
     *
     * @return ADV\Core\DB\Query_Result|Array|bool
     */
    static public function get_report($profile, $report) {
      $sql = "SELECT printer FROM print_profiles WHERE profile=" . DB::quote($profile) . " AND report=" . DB::quote($report);
      $result = DB::query($sql, 'report printer lookup failed');
      if (!$result) {
        return FALSE;
      }
      $ret = DB::fetch($result);
      if ($ret === FALSE) {
        $result = DB::query($sql . "''", 'default report printer lookup failed');
        if (!$result) {
          return FALSE;
        }
        $ret = DB::fetch($result);
        if (!$ret) {
          return FALSE;
        }
      }
      return static::get($ret['printer']);
    }
    /**
     * @static
     *
     * @param $name
     *
     * @return null|PDOStatement
     */
    static public function delete_profile($name) {
      $sql = "DELETE FROM print_profiles WHERE profile=" . DB::escape($name);
      return DB::query($sql, "could not delete printing profile");
    }

    //
    // Get all report destinations for given profile.
    //
    /**
     * @static
     *
     * @param $name
     *
     * @return null|PDOStatement
     */
    static public function get_profile($name) {
      $sql = "SELECT	* FROM print_profiles WHERE profile=" . DB::escape($name);
      return DB::query($sql, "could not get printing profile");
    }
  }
