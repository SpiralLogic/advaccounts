<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Languages {

    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     *
     * @return string
     */
    public static function select($name, $selected_id = NULL) {
      $items = array();
      $langs = Config::get('languages.installed');
      foreach ($langs as $lang) {
        $items[$lang['code']] = $lang['name'];
      }
      return array_selector($name, $selected_id, $items);
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    public static function cells($label, $name, $selected_id = NULL) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Languages::select($name, $selected_id);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    public static function row($label, $name, $selected_id = NULL) {
      echo "<tr><td class='label'>$label</td>";
      Languages::cells(NULL, $name, $selected_id);
      echo "</tr>\n";
    }
  }
