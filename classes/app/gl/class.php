<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class GL_Class {

    /**
     * @static
     *
     * @param $id
     * @param $name
     * @param $ctype
     *
     * @return null|PDOStatement
     */
    static public function add($id, $name, $ctype) {
      $sql = "INSERT INTO chart_class (cid, class_name, ctype)
		VALUES (" . DB::escape($id) . ", " . DB::escape($name) . ", " . DB::escape($ctype) . ")";
      return DB::query($sql);
    }
    /**
     * @static
     *
     * @param $id
     * @param $name
     * @param $ctype
     *
     * @return null|PDOStatement
     */
    static public function update($id, $name, $ctype) {
      $sql = "UPDATE chart_class SET class_name=" . DB::escape($name) . ",
		ctype=" . DB::escape($ctype) . " WHERE cid = " . DB::escape($id);
      return DB::query($sql);
    }
    /**
     * @static
     *
     * @param bool $all
     * @param      $balance
     *
     * @return null|PDOStatement
     */
    static public function get_all($all = FALSE, $balance = -1) {
      $sql = "SELECT * FROM chart_class";
      if (!$all) {
        $sql .= " WHERE !inactive";
      }
      if ($balance == 0) {
        $sql .= " AND ctype>" . CL_EQUITY . " OR ctype=0";
      }
      elseif ($balance == 1) {
        $sql .= " AND ctype>0 AND ctype<" . CL_INCOME;
      }
      $sql .= " ORDER BY cid";
      return DB::query($sql, "could not get account classes");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get($id) {
      $sql    = "SELECT * FROM chart_class WHERE cid = " . DB::escape($id);
      $result = DB::query($sql, "could not get account type");
      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return mixed
     */
    static public function get_name($id) {
      $sql    = "SELECT class_name FROM chart_class WHERE cid =" . DB::escape($id);
      $result = DB::query($sql, "could not get account type");
      $row    = DB::fetch_row($result);
      return $row[0];
    }
    /**
     * @static
     *
     * @param $id
     */
    static public function delete($id) {
      $sql = "DELETE FROM chart_class WHERE cid = " . DB::escape($id);
      DB::query($sql, "could not delete account type");
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     *
     * @return string
     */
    static public function  select($name, $selected_id = NULL, $submit_on_change = FALSE) {
      $sql = "SELECT cid, class_name FROM chart_class";
      return select_box($name, $selected_id, $sql, 'cid', 'class_name', array(
        'select_submit' => $submit_on_change, 'async' => FALSE
      ));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     */
    static public function  cells($label, $name, $selected_id = NULL, $submit_on_change = FALSE) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo GL_Class::select($name, $selected_id, $submit_on_change);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     */
    static public function  row($label, $name, $selected_id = NULL, $submit_on_change = FALSE) {
      echo "<tr><td class='label'>$label</td>";
      GL_Class::cells(NULL, $name, $selected_id, $submit_on_change);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     */
    static public function  types_row($label, $name, $selected_id = NULL, $submit_on_change = FALSE) {
      global $class_types;
      echo "<tr><td class='label'>$label</td><td>";
      echo array_selector($name, $selected_id, $class_types, array('select_submit' => $submit_on_change));
      echo "</td></tr>\n";
    }
  }
