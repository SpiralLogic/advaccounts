<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class Sales_UI {
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $spec_opt
     *
     * @return string
     */
    static public function  persons($name, $selected_id = NULL, $spec_opt = FALSE) {
      $sql = "SELECT salesman_code, salesman_name, inactive FROM salesman";
      return select_box($name, $selected_id, $sql, 'salesman_code', 'salesman_name', array(
        'order' => array('salesman_name'), 'spec_option' => $spec_opt, 'spec_id' => ALL_NUMERIC
      ));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $spec_opt
     */
    static public function  persons_cells($label, $name, $selected_id = NULL, $spec_opt = FALSE) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>\n";
      echo Sales_UI::persons($name, $selected_id, $spec_opt);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $spec_opt
     */
    static public function  persons_row($label, $name, $selected_id = NULL, $spec_opt = FALSE) {
      echo "<tr><td class='label'>$label</td>";
      Sales_UI::persons_cells(NULL, $name, $selected_id, $spec_opt);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     *
     * @return string
     */
    static public function  areas($name, $selected_id = NULL) {
      $sql = "SELECT area_code, description, inactive FROM areas";
      return select_box($name, $selected_id, $sql, 'area_code', 'description', array());
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    static public function  areas_cells($label, $name, $selected_id = NULL) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Sales_UI::areas($name, $selected_id);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    static public function  areas_row($label, $name, $selected_id = NULL) {
      echo "<tr><td class='label'>$label</td>";
      Sales_UI::areas_cells(NULL, $name, $selected_id);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $special_option
     *
     * @return string
     */
    static public function  groups($name, $selected_id = NULL, $special_option = FALSE) {
      $sql = "SELECT id, description, inactive FROM groups";
      return select_box($name, $selected_id, $sql, 'id', 'description', array(
        'spec_option' => $special_option === TRUE ? ' ' :
          $special_option, 'order' => 'description', 'spec_id' => 0,
      ));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $special_option
     */
    static public function  groups_cells($label, $name, $selected_id = NULL, $special_option = FALSE) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Sales_UI::groups($name, $selected_id, $special_option);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $special_option
     */
    static public function  groups_row($label, $name, $selected_id = NULL, $special_option = FALSE) {
      echo "<tr><td class='label'>$label</td>";
      Sales_UI::groups_cells(NULL, $name, $selected_id, $special_option);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     *
     * @return string
     */
    static public function  shippers($name, $selected_id = NULL) {
      $sql = "SELECT shipper_id, shipper_name, inactive FROM shippers";
      return select_box($name, $selected_id, $sql, 'shipper_id', 'shipper_name', array('order' => array('shipper_name')));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    static public function  shippers_cells($label, $name, $selected_id = NULL) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Sales_UI::shippers($name, $selected_id);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    static public function  shippers_row($label, $name, $selected_id = NULL) {
      echo "<tr><td class='label'>$label</td>";
      Sales_UI::shippers_cells(NULL, $name, $selected_id);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected
     */
    static public function  policy_cells($label, $name, $selected = NULL) {
      if ($label != NULL) {
        label_cell($label);
      }
      echo "<td>\n";
      echo array_selector($name, $selected, array(
        '' => _("Automatically put balance on back order"), 'CAN' => _("Cancel any quantites not delivered")
      ));
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected
     */
    static public function  policy_row($label, $name, $selected = NULL) {
      echo "<tr><td class='label'>$label</td>";
      Sales_UI::policy_cells(NULL, $name, $selected);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $special_option
     *
     * @return string
     */
    static public function templates($name, $selected_id = NULL, $special_option = FALSE) {
      $sql = "SELECT sorder.order_no,	Sum(line.unit_price*line.quantity*(1-line.discount_percent)) AS OrderValue
				FROM sales_orders as sorder, sales_order_details as line
				WHERE sorder.order_no = line.order_no AND sorder.type = 1 GROUP BY line.order_no";
      return select_box($name, $selected_id, $sql, 'order_no', 'OrderValue', array(
        'format' => '_format_template_items', 'spec_option' => $special_option === TRUE ?
          ' ' : $special_option, 'order' => 'order_no', 'spec_id' => 0,
      ));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $special_option
     */
    static public function templates_cells($label, $name, $selected_id = NULL, $special_option = FALSE) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Sales_UI::templates($name, $selected_id, $special_option);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $special_option
     */
    static public function templates_row($label, $name, $selected_id = NULL, $special_option = FALSE) {
      echo "<tr><td class='label'>$label</td>";
      Sales_UI::templates_cells(NULL, $name, $selected_id, $special_option);
      echo "</tr>\n";
    }

    /**
     *  Select item via foreign code.
     *
     * @param        $name
     * @param null   $selected_id
     * @param bool   $all_option
     * @param bool   $submit_on_change
     * @param string $type
     * @param array  $opts 'description' => false,<br>
    'disabled' => false,<br>
    'editable' => true,<br>
    'selected' => '',<br>
    'label' => false,<br>
    'cells' => false,<br>
    'inactive' => false,<br>
    'purchase' => false,<br>
    'sale' => false,<br>
    'js' => '',<br>
    'selectjs' => '',<br>
    'submitonselect' => '',<br>
    'sales_type' => 1,<br>
    'no_sale' => false,<br>
    'select' => false,<br>
    'type' => 'local',<br>
    'kits'=>true,<br>
    'where' => '',<br>
    'size'=>'20px'<br>'
     * @param bool   $legacy
     *
     * @return string|void
     */
    static public function items($name, $selected_id = NULL, $all_option = FALSE, $submit_on_change = FALSE, $type = '', $opts = array(), $legacy = FALSE) {
      // all sales codes
      if (!$legacy) {
        return Item::addSearchBox($name, array_merge(array('selected' => $selected_id, 'type' => $type, 'cells' => TRUE, 'sale' => TRUE), $opts));
      }
      $where = ($type == 'local') ? " AND !i.is_foreign" : ' ';
      if ($type == 'kits') {
        $where .= " AND !i.is_foreign AND i.item_code!=i.stock_id ";
      }
      $sql = "SELECT i.item_code, i.description, c.description, count(*)>1 as kit,
					 i.inactive, if(count(*)>1, '0', s.editable) as editable, s.long_description
					FROM stock_master s, item_codes i LEFT JOIN stock_category c ON i.category_id=c.category_id
					WHERE i.stock_id=s.stock_id $where AND !i.inactive AND !s.inactive AND !s.no_sale GROUP BY i.item_code";
      return select_box($name, $selected_id, $sql, 'i.item_code', 'c.description', array_merge(array(
          'format' => '_format_stock_items', 'spec_option' => $all_option === TRUE ?
            _("All Items") :
            $all_option,
          'spec_id' => ALL_TEXT,
          'search_box' => TRUE,
          'search' => array("i.item_code", "c.description", "i.description"),
          'search_submit' => DB_Company::get_pref('no_item_list') != 0,
          'size' => 15,
          'select_submit' => $submit_on_change,
          'category' => 2,
          'order' => array('c.description', 'i.item_code'),
          'editable' => 30,
          'max' => 50
        ),
        $opts));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     * @param      $opts
     */
    static public function items_cells($label, $name, $selected_id = NULL, $all_option = FALSE, $submit_on_change = FALSE, $opts) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo Sales_UI::items($name, $selected_id, $all_option, $submit_on_change, '', array_merge(array(
          'cells' => TRUE,
          'description' => ''
        ),
        $opts));
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     *
     * @return string|void
     */
    static public function kits($name, $selected_id = NULL, $all_option = FALSE, $submit_on_change = FALSE) {
      return Sales_UI::items($name, $selected_id, $all_option, $submit_on_change, 'kits', array(
        'cells' => FALSE,
        'sale' => FALSE, 'kits' => FALSE
      ), TRUE);
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     * @param bool $legacy
     */
    static public function local_items_row($label, $name, $selected_id = NULL, $all_option = FALSE, $submit_on_change = FALSE, $legacy = TRUE) {
      echo "<tr>";
      if ($label != NULL) {
        echo "<td class='label'>$label</td>\n<td>";
      }
      echo Sales_UI::items($name, $selected_id, $all_option, $submit_on_change, 'local', array('cells' => FALSE), $legacy);
      echo "</td></tr>";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     */
    static public function payment_cells($label, $name, $selected_id = NULL, $submit_on_change = FALSE) {
      if ($label != NULL) {
        echo "<td class='label'>$label</td>\n";
      }
      echo "<td>";
      echo yesno_list($name, $selected_id, _('Cash'), _('Delayed'), $submit_on_change);
      echo "</td>\n";
    }
  }
