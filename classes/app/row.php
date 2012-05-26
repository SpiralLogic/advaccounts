<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: advanced
   * Date: 9/05/12
   * Time: 2:42 PM
   * To change this template use File | Settings | File Templates.
   */

  class Row
  {
    /**
     * @param        $label
     * @param        $value
     * @param string $params
     * @param string $params2
     * @param int    $leftfill
     * @param null   $id
     */
    public static function label($label, $value, $params = "", $params2 = "", $leftfill = 0, $id = null)
    {
      echo "<tr>";
      if ($params == "") {
        echo "<td class='label'>$label</td>";
        $label = null;
      } elseif (stristr($params, 'class')) {
        echo "<td $params>$label</td>";
        $label = null;
      }
      Cell::labels($label, $value, $params, $params2, $id);
      if ($leftfill != 0) {
        echo "<td colspan=$leftfill></td>";
      }
      echo "</tr>\n";
    }

    /**
     * @param $k
     */
    public static function alt_table_row_color(&$k)
    {
      if ($k == 1) {
        echo "<tr class='oddrow grid'>\n";
        $k = 0;
      } else {
        echo "<tr class='evenrow grid'>\n";
        $k++;
      }
    }
    /**
     * @param string $param
     */
    public static function start($param = "")
    {
      if ($param != "") {
        echo "<tr $param>\n";
      } else {
        echo "<tr>\n";
      }
    }

    public static function end()
    {
      echo "</tr>\n";
    }
  }
