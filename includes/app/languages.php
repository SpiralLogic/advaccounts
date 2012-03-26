<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 3/12/11
   * Time: 1:49 PM
   * To change this template use File | Settings | File Templates.
   */
  class Languages {

    static public function select($name, $selected_id = NULL) {
      $items = array();
      $langs = Config::get('languages.installed');
      foreach ($langs as $lang) {
        $items[$lang['code']] = $lang['name'];
      }
      return array_selector($name, $selected_id, $items);
    }

    static public function cells($label, $name, $selected_id = NULL) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Languages::select($name, $selected_id);
      echo "</td>\n";
    }

    static public function row($label, $name, $selected_id = NULL) {
      echo "<tr><td class='label'>$label</td>";
      Languages::cells(NULL, $name, $selected_id);
      echo "</tr>\n";
    }
  }
