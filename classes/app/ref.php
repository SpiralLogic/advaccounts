<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Ref {

    /**
     * @static
     *
     * @param $type
     * @param $id
     * @param $reference
     */
    static public function add($type, $id, $reference) {
      $sql = "INSERT INTO refs (type, id, reference)
			VALUES (" . DB::escape($type) . ", " . DB::escape($id) . ", " . DB::escape(trim($reference)) . ")";
      DB::query($sql, "could not add reference entry");
      if ($reference != 'auto') {
        static::save_last($type);
      }
    }
    /**
     * @static
     *
     * @param $type
     * @param $reference
     *
     * @return bool
     */
    static public function find($type, $reference) {
      $sql    = "SELECT id FROM refs WHERE type=" . DB::escape($type) . " AND reference=" . DB::escape($reference);
      $result = DB::query($sql, "could not query reference table");
      return (DB::num_rows($result) > 0);
    }
    /**
     * @static
     *
     * @param $type
     * @param $reference
     */
    static public function save($type, $reference) {
      $sql = "UPDATE sys_types SET next_reference= REPLACE(" . DB::escape(trim($reference)) . ",prefix,'') WHERE type_id = " . DB::escape($type);
      DB::query($sql, "The next transaction ref for $type could not be updated");
    }
    /**
     * @static
     *
     * @param $type
     *
     * @return string
     */
    static public function get_next($type) {
      $sql    = "SELECT CONCAT(prefix,next_reference) FROM sys_types WHERE type_id = " . DB::escape($type);
      $result = DB::query($sql, "The last transaction ref for $type could not be retreived");
      $row    = DB::fetch_row($result);
      $ref    = $row[0];
      if (!static::is_valid($ref)) {
        $db_info = SysTypes::get_db_info($type);
        $db_name = $db_info[0];
        $db_type = $db_info[1];
        $db_ref  = $db_info[3];
        if ($db_ref != NULL) {
          $sql = "SELECT $db_ref FROM $db_name ";
          if ($db_type != NULL) {
            $sql .= " AND $db_type=$type";
          }
          $sql .= " ORDER BY $db_ref DESC LIMIT 1";
          $result = DB::query($sql, "The last transaction ref for $type could not be retreived");
          $result = DB::fetch($result);
          $ref    = $result[0];
        }
      }
      $oldref = 'auto';
      while (!static::is_new($ref, $type) && ($oldref != $ref)) {
        $oldref = $ref;
        $ref    = static::increment($ref);
      }
      return $ref;
    }
    /**
     * @static
     *
     * @param $type
     * @param $id
     *
     * @return mixed
     */
    static public function get($type, $id) {
      $sql    = "SELECT * FROM refs WHERE type=" . DB::escape($type) . " AND id=" . DB::escape($id);
      $result = DB::query($sql, "could not query reference table");
      $row    = DB::fetch($result);
      return $row['reference'];
    }
    /**
     * @static
     *
     * @param $type
     * @param $id
     *
     * @return null|PDOStatement
     */
    static public function delete($type, $id) {
      $sql = "DELETE FROM refs WHERE type=$type AND id=" . DB::escape($id);
      return DB::query($sql, "could not delete from reference table");
    }
    /**
     * @static
     *
     * @param $type
     * @param $id
     * @param $reference
     */
    static public function update($type, $id, $reference) {
      $sql = "UPDATE refs SET reference=" . DB::escape($reference) . " WHERE type=" . DB::escape($type) . " AND id=" . DB::escape($id);
      DB::query($sql, "could not update reference entry");
      if ($reference != 'auto') {
        static::save_last($type);
      }
    }
    /**
     * @static
     *
     * @param $type
     * @param $reference
     *
     * @return bool
     */
    static public function exists($type, $reference) {
      return (static::find($type, $reference) != NULL);
    }
    /**
     * @static
     *
     * @param $type
     */
    static public function save_last($type) {
      $next = static::increment(static::get_next($type));
      static::save($type, $next);
    }
    /**
     * @static
     *
     * @param $reference
     *
     * @return bool
     */
    static public function is_valid($reference) {
      return strlen(trim($reference)) > 0;
    }
    /**
     * @static
     *
     * @param $reference
     *
     * @return string
     */
    static public function increment($reference) {
      // New method done by Pete. So f.i. WA036 will increment to WA037 and so on.
      // If $reference contains at least one group of digits,
      // extract first didgits group and add 1, then put all together.
      // NB. preg_match returns 1 if the regex matches completely
      // also $result[0] holds entire string, 1 the first captured, 2 the 2nd etc.
      //
      if (preg_match('/^(\D*?)(\d+)(.*)/', $reference, $result) == 1) {
        list($all, $prefix, $number, $postfix) = $result;
        $dig_count = strlen($number); // How many digits? eg. 0003 = 4
        $fmt       = '%0' . $dig_count . 'd'; // Make a format string - leading zeroes
        $nextval   = sprintf($fmt, intval($number + 1)); // Add one on, and put prefix back on
        return $prefix . $nextval . $postfix;
      }
      else {
        return $reference;
      }
    }
    /**
     * @static
     *
     * @param $ref
     * @param $type
     *
     * @return bool
     */
    static public function is_new($ref, $type) {
      $db_info = SysTypes::get_db_info($type);
      $db_name = $db_info[0];
      $db_type = $db_info[1];
      $db_ref  = $db_info[3];
      if ($db_ref != NULL) {
        $sql = "SELECT $db_ref FROM $db_name WHERE $db_ref='$ref'";
        if ($db_type != NULL) {
          $sql .= " AND $db_type=$type";
        }
        $result = DB::query($sql, "could not test for unique reference");
        return (DB::num_rows($result) == 0);
      }
      // it's a type that doesn't use references - shouldn't be calling here, but say yes anyways
      return TRUE;
    }
  }


