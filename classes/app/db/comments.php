<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class DB_Comments {

    /**
     * @static
     *
     * @param $type
     * @param $type_no
     * @param $date_
     * @param $memo_
     */
    static public function add($type, $type_no, $date_, $memo_) {
      if ($memo_ != NULL && $memo_ != "") {
        $date = Dates::date2sql($date_);
        $sql  = "INSERT INTO comments (type, id, date_, memo_)
		 		VALUES (" . DB::escape($type) . ", " . DB::escape($type_no)
          . ", '$date', " . DB::escape($memo_) . ")";
        DB::query($sql, "could not add comments transaction entry");
      }
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     */
    static public function delete($type, $type_no) {
      $sql = "DELETE FROM comments WHERE type=" . DB::escape($type)
        . " AND id=" . DB::escape($type_no);
      DB::query($sql, "could not delete from comments transaction table");
    }
    /**
     * @static
     *
     * @param $type
     * @param $id
     */
    static function display_row($type, $id) {
      $comments = DB_Comments::get($type, $id);
      if ($comments and DB::num_rows($comments)) {
        echo "<tr><td class='label'>Comments</td><td colspan=15>";
        while ($comment = DB::fetch($comments)) {
          echo $comment["memo_"] . "<br>";
        }
        echo "</td></tr>";
      }
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     *
     * @return null|PDOStatement
     */
    static public function get($type, $type_no) {
      $sql = "SELECT * FROM comments WHERE type="
        . DB::escape($type) . " AND id=" . DB::escape($type_no);
      return DB::query($sql, "could not query comments transaction table");
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     *
     * @return string
     */
    static function get_string($type, $type_no) {
      $str_return = "";
      $result     = DB_Comments::get($type, $type_no);
      while ($comment = DB::fetch($result)) {
        if (strlen($str_return)) {
          $str_return = $str_return . " \n";
        }
        $str_return = $str_return . $comment["memo_"];
      }
      return $str_return;
    }
    /**
     * @static
     *
     * @param $type
     * @param $id
     * @param $date_
     * @param $memo_
     */
    static public function update($type, $id, $date_, $memo_) {
      if ($date_ == NULL) {
        DB_Comments::delete($type, $id);
        DB_Comments::add($type, $id, Dates::today(), $memo_);
      }
      else {
        $date = Dates::date2sql($date_);
        $sql  = "UPDATE comments SET memo_=" . DB::escape($memo_)
          . " WHERE type=" . DB::escape($type) . " AND id=" . DB::escape($id)
          . " AND date_='$date'";
        DB::query($sql, "could not update comments");
      }
    }
  }
