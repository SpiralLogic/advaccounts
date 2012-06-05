<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  // STOCK ITEMS
  class Item_UI {

    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     *
     * @return string
     */
    public static function manufactured($name, $selected_id = NULL, $all_option = FALSE, $submit_on_change = FALSE) {
      return Item::select($name, $selected_id, $all_option, $submit_on_change, array('where' => array("mb_flag= '" . STOCK_MANUFACTURE . "'")));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     */
    public static function manufactured_cells($label, $name, $selected_id = NULL, $all_option = FALSE, $submit_on_change = FALSE) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo Item_UI::manufactured($name, $selected_id, $all_option, $submit_on_change, array('cells' => TRUE));
      echo "\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     */
    public static function manufactured_row($label, $name, $selected_id = NULL, $all_option = FALSE, $submit_on_change = FALSE) {
      echo "<tr><td class='label'>$label</td>";
      Item_UI::manufactured_cells(NULL, $name, $selected_id, $all_option, $submit_on_change);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param      $name
     * @param      $parent_stock_id
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     * @param bool $editkey
     *
     * @return string
     */
    public static function component($name, $parent_stock_id, $selected_id = NULL, $all_option = FALSE, $submit_on_change = FALSE, $editkey = FALSE) {
      return Item::select($name, $selected_id, $all_option, $submit_on_change, array('where' => " stock_id != '$parent_stock_id' "));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param      $parent_stock_id
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     * @param bool $editkey
     */
    public static function component_cells($label, $name, $parent_stock_id, $selected_id = NULL, $all_option = FALSE, $submit_on_change = FALSE, $editkey = FALSE) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo Item::select($name, $selected_id, $all_option, $submit_on_change, array(
        'where' => "stock_id != '$parent_stock_id'", 'cells' => TRUE
      ));
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     *
     * @return string
     */
    public static function costable($name, $selected_id = NULL, $all_option = FALSE, $submit_on_change = FALSE) {
      return Item::select($name, $selected_id, $all_option, $submit_on_change, array('where' => "mb_flag!='" . STOCK_SERVICE . "'"));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     */
    public static function costable_cells($label, $name, $selected_id = NULL, $all_option = FALSE, $submit_on_change = FALSE) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo Item::select($name, $selected_id, $all_option, $submit_on_change, array(
        'where' => "mb_flag!='" . STOCK_SERVICE . "'", 'cells' => TRUE, 'description' => ''
      ));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $enabled
     */
    public static function type_row($label, $name, $selected_id = NULL, $enabled = TRUE) {
      global $stock_types;
      echo "<tr>";
      if ($label != NULL) {
        echo "<td class='label'>$label</td>\n";
      }
      echo "<td>";
      echo Form::arraySelect($name, $selected_id, $stock_types, array(
        'select_submit' => TRUE, 'disabled' => !$enabled
      ));
      echo "</td></tr>\n";
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $enabled
     *
     * @return string
     */
    public static function type($name, $selected_id = NULL, $enabled = TRUE) {
      global $stock_types;
      return Form::arraySelect($name, $selected_id, $stock_types, array(
        'select_submit' => TRUE, 'disabled' => !$enabled
      ));
    }
    /**
     * @static
     *
     * @param        $type
     * @param        $trans_no
     * @param string $label
     * @param bool   $icon
     * @param string $class
     * @param string $id
     *
     * @return null|string
     */
    public static function trans_view($type, $trans_no, $label = "", $icon = FALSE, $class = '', $id = '') {
      $viewer = "inventory/view/";
      switch ($type) {
        case ST_INVADJUST:
          $viewer .= "adjustment.php";
          break;
        case ST_LOCTRANSFER:
          $viewer .= "transfer.php";
          break;
        default:
          return NULL;
      }
      $viewer .= "?trans_no=$trans_no";
      if ($label == "") {
        $label = $trans_no;
      }
      return Display::viewer_link($label, $viewer, $class, $id, $icon);
    }
    /**
     * @static
     *
     * @param      $stock_id
     * @param null $description
     * @param bool $echo
     *
     * @return string
     */
    public static function status($stock_id, $description = NULL, $echo = TRUE) {
      if ($description) //Display::link_params_separate( "/inventory/inquiry/stock_status.php", (User::show_codes()?$stock_id . " - ":"") . $description, "stock_id=$stock_id");
      {
        $preview_str = "<a class='openWindow' target='_blank' href='/inventory/inquiry/stock_status.php?stock_id=$stock_id' >" . (User::show_codes()
          ? $stock_id . " - " : "") . $description . "</a>";
      }
      else //Display::link_params_separate( "/inventory/inquiry/stock_status.php", $stock_id, "stock_id=$stock_id");
      {
        $preview_str = "<a class='openWindow' target='_blank' href='/inventory/inquiry/stock_status.php?stock_id=$stock_id' >$stock_id</a>";
      }
      if ($echo) {
        echo $preview_str;
      }
      return $preview_str;
    }
    /**
     * @static
     *
     * @param      $stock_id
     * @param null $description
     */
    public static function status_cell($stock_id, $description = NULL) {
      echo "<td>";
      Item_UI::status($stock_id, $description);
      echo "</td>";
    }
  }
