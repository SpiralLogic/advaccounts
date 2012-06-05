<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class GL_Type
  {
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
    public static function add($id, $name, $class_id, $parent)
    {
      $sql
        = "INSERT INTO chart_types (id, name, class_id, parent)
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
    public static function update($id, $name, $class_id, $parent)
    {
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
    public static function get_all($all = false, $class_id = false, $parent = false)
    {
      $sql = "SELECT * FROM chart_types";
      if (!$all) {
        $sql .= " WHERE !inactive";
      }
      if ($class_id != false) {
        $sql .= " AND class_id=" . DB::escape($class_id);
      }
      if ($parent == -1) {
        $sql .= " AND parent <= 0";
      } elseif ($parent != false) {
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
    public static function get($id)
    {
      $sql    = "SELECT * FROM chart_types WHERE id = " . DB::escape($id);
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
    public static function get_name($id)
    {
      $sql    = "SELECT name FROM chart_types WHERE id = " . DB::escape($id);
      $result = DB::query($sql, "could not get account type");
      $row    = DB::fetch_row($result);

      return $row[0];
    }
    /**
     * @static
     *
     * @param $id
     */
    public static function delete($id)
    {
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
    public static function  select($name, $selected_id = null, $all_option = false, $all_option_numeric = true)
    {
      $sql = "SELECT id, name FROM chart_types";

      return Form::selectBox($name, $selected_id, $sql, 'id', 'name', array(
                                                                      'order'       => 'id',
                                                                      'spec_option' => $all_option,
                                                                      'spec_id'     => $all_option_numeric ? 0 : ALL_TEXT
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
    public static function  cells($label, $name, $selected_id = null, $all_option = false, $all_option_numeric = false)
    {
      if ($label != null) {
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
    public static function  row($label, $name, $selected_id = null, $all_option = false, $all_option_numeric = false)
    {
      echo "<tr><td class='label'>$label</td>";
      GL_Type::cells(null, $name, $selected_id, $all_option, $all_option_numeric);
      echo "</tr>\n";
    }
  }
