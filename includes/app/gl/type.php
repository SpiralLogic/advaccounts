<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class GL_Type {
    /**
     * @static
     *
     * @param $id
     * @param $name
     * @param $class_id
     * @param $parent
     *
     * @return null|PDOStatement
     */
    static public function add($id, $name, $class_id, $parent) {
      $sql = "INSERT INTO chart_types (id, name, class_id, parent)
		VALUES ($id, " . DB::escape($name) . ", " . DB::escape($class_id) . ", " . DB::escape($parent) . ")";
      return DB::query($sql);
    }
    /**
     * @static
     *
     * @param $id
     * @param $name
     * @param $class_id
     * @param $parent
     *
     * @return null|PDOStatement
     */
    static public function update($id, $name, $class_id, $parent) {
      $sql = "UPDATE chart_types SET name=" . DB::escape($name) . ",
		class_id=" . DB::escape($class_id) . ", parent=" . DB::escape($parent) . " WHERE id = " . DB::escape($id);
      return DB::query($sql, "could not update account type");
    }
    /**
     * @static
     *
     * @param bool $all
     * @param bool $class_id
     * @param bool $parent
     *
     * @return null|PDOStatement
     */
    static public function get_all($all = FALSE, $class_id = FALSE, $parent = FALSE) {
      $sql = "SELECT * FROM chart_types";
      if (!$all) {
        $sql .= " WHERE !inactive";
      }
      if ($class_id != FALSE) {
        $sql .= " AND class_id=" . DB::escape($class_id);
      }
      if ($parent == -1) {
        $sql .= " AND parent <= 0";
      }
      elseif ($parent != FALSE) {
        $sql .= " AND parent=" . DB::escape($parent);
      }
      $sql .= " ORDER BY class_id, id";
      return DB::query($sql, "could not get account types");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get($id) {
      $sql = "SELECT * FROM chart_types WHERE id = " . DB::escape($id);
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
      $sql = "SELECT name FROM chart_types WHERE id = " . DB::escape($id);
      $result = DB::query($sql, "could not get account type");
      $row = DB::fetch_row($result);
      return $row[0];
    }
    /**
     * @static
     *
     * @param $id
     */
    static public function delete($id) {
      $sql = "DELETE FROM chart_types WHERE id = " . DB::escape($id);
      DB::query($sql, "could not delete account type");
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $all_option_numeric
     *
     * @return string
     */
    static public function  select($name, $selected_id = NULL, $all_option = FALSE, $all_option_numeric = TRUE) {
      $sql = "SELECT id, name FROM chart_types";
      return select_box($name, $selected_id, $sql, 'id', 'name', array(
        'order' => 'id', 'spec_option' => $all_option, 'spec_id' => $all_option_numeric ?
          0 : ALL_TEXT
      ));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $all_option_numeric
     */
    static public function  cells($label, $name, $selected_id = NULL, $all_option = FALSE, $all_option_numeric = FALSE) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo GL_Type::select($name, $selected_id, $all_option, $all_option_numeric);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $all_option_numeric
     */
    static public function  row($label, $name, $selected_id = NULL, $all_option = FALSE, $all_option_numeric = FALSE) {
      echo "<tr><td class='label'>$label</td>";
      GL_Type::cells(NULL, $name, $selected_id, $all_option, $all_option_numeric);
      echo "</tr>\n";
    }
  }
