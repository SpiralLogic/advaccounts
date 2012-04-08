<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class GL_UI {
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $skip_bank_accounts
     * @param bool $cells
     * @param bool $all_option
     * @param bool $submit_on_change
     * @param bool $all
     *
     * @return string
     */
    static public function  all($name, $selected_id = NULL, $skip_bank_accounts = FALSE, $cells = FALSE, $all_option = FALSE, $submit_on_change = FALSE, $all = FALSE) {
      if ($skip_bank_accounts) {
        $sql = "SELECT chart.account_code, chart.account_name, type.name, chart.inactive, type.id
				FROM (chart_master chart,chart_types type) LEFT JOIN bank_accounts acc ON chart.account_code=acc.account_code
					WHERE acc.account_code IS NULL
				AND chart.account_type=type.id";
      }
      else {
        $sql = "SELECT chart.account_code, chart.account_name, type.name, chart.inactive, type.id
				FROM chart_master chart,chart_types type
				WHERE chart.account_type=type.id";
      }
      return select_box($name, $selected_id, $sql, 'chart.account_code', 'chart.account_name', array(
        'cache' => TRUE,
        'format' => '_format_account', 'type' => 2, 'spec_option' => $all_option === TRUE ?
          _("Use Item Sales Accounts") :
          $all_option, 'spec_id' => '', 'order' => array('type.id', 'account_code'), 'search_box' => $cells, 'search_submit' => FALSE, 'size' => 12, 'max' => 10, 'cells' => TRUE, 'select_submit' => $submit_on_change, 'async' => FALSE, 'category' => 2, 'show_inactive' => $all
      ));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $skip_bank_accounts
     * @param bool $cells
     * @param bool $all_option
     * @param bool $submit_on_change
     * @param bool $all
     */
    static public function  all_cells($label, $name, $selected_id = NULL, $skip_bank_accounts = FALSE, $cells = FALSE, $all_option = FALSE, $submit_on_change = FALSE, $all = FALSE) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo GL_UI::all($name, $selected_id, $skip_bank_accounts, $cells, $all_option, $submit_on_change, $all);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $skip_bank_accounts
     * @param bool $cells
     * @param bool $all_option
     */
    static public function  all_row($label, $name, $selected_id = NULL, $skip_bank_accounts = FALSE, $cells = FALSE, $all_option = FALSE) {
      echo "<tr><td class='label'>$label</td>";
      GL_UI::all_cells(NULL, $name, $selected_id, $skip_bank_accounts, $cells, $all_option);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param        $type
     * @param        $trans_no
     * @param string $label
     * @param bool   $force
     * @param string $class
     * @param string $id
     *
     * @return string
     */
    static public function  view($type, $trans_no, $label = "", $force = FALSE, $class = '', $id = '') {
      if (!$force && !User::show_gl_info()) {
        return "";
      }
      $icon = FALSE;
      if ($label == "") {
        $label = _("GL");
        $icon = ICON_GL;
      }
      return Display::viewer_link($label, "gl/view/gl_trans_view.php?type_id=$type&trans_no=$trans_no", $class, $id, $icon);
    }
    /**
     * @static
     *
     * @param        $type
     * @param        $trans_no
     * @param string $label
     *
     * @return string
     */
    static public function  view_cell($type, $trans_no, $label = "") {
      $str = GL_UI::view($type, $trans_no, $label);
      if ($str != "") {
        return "<td>$str</td>";
      }
      return $str;
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
    static public function trans_view($type, $trans_no, $label = "", $icon = FALSE, $class = '', $id = '') {
      $view_str = Debtor::trans_view($type, $trans_no, $label, $icon, $class, $id);
      if ($view_str != NULL) {
        return $view_str;
      }
      $view_str = Purch_UI::trans_view($type, $trans_no, $label, $icon, $class, $id);
      if ($view_str != NULL) {
        return $view_str;
      }
      $view_str = Bank_UI::trans_view($type, $trans_no, $label, $icon, $class, $id);
      if ($view_str != NULL) {
        return $view_str;
      }
      $view_str = Item_UI::trans_view($type, $trans_no, $label, $icon, $class, $id);
      if ($view_str != NULL) {
        return $view_str;
      }
      $view_str = WO::trans_view($type, $trans_no, $label, $icon, $class, $id);
      if ($view_str != NULL) {
        return $view_str;
      }
      $view_str = Dimensions::trans_view($type, $trans_no, $label, $icon, $class, $id);
      if ($view_str != NULL) {
        return $view_str;
      }
      $view_str = GL_Journal::view($type, $trans_no, $label, $icon, $class, $id);
      if ($view_str != NULL) {
        return $view_str;
      }
      return NULL;
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
    static public function fiscalyears($name, $selected_id = NULL, $submit_on_change = FALSE) {
      $sql = "SELECT * FROM fiscal_year";
      // default to the company current fiscal year
      return select_box($name, $selected_id, $sql, 'id', '', array(
        'order' => 'begin', 'default' => DB_Company::get_pref('f_year'), 'format' => '_format_fiscalyears', 'select_submit' => $submit_on_change, 'async' => FALSE
      ));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    static public function fiscalyears_cells($label, $name, $selected_id = NULL) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo GL_UI::fiscalyears($name, $selected_id);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    static public function fiscalyears_row($label, $name, $selected_id = NULL) {
      echo "<tr><td class='label'>$label</td>";
      GL_UI::fiscalyears_cells(NULL, $name, $selected_id);
      echo "</tr>\n";
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
    static public function payment_person_type($name, $selected_id = NULL, $submit_on_change = FALSE) {
      global $payment_person_types;
      $items = array();
      foreach ($payment_person_types as $key => $type) {
        if ($key != PT_WORKORDER) {
          $items[$key] = $type;
        }
      }
      return array_selector($name, $selected_id, $items, array('select_submit' => $submit_on_change));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param null $related
     */
    static public function payment_person_type_cells($label, $name, $selected_id = NULL, $related = NULL) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo GL_UI::payment_person_type($name, $selected_id, $related);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param null $related
     */
    static public function payment_person_type_row($label, $name, $selected_id = NULL, $related = NULL) {
      echo "<tr><td class='label'>$label</td>";
      GL_UI::payment_person_type_cells(NULL, $name, $selected_id, $related);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param null $disabled
     *
     * @return string
     */
    static public function payment_terms($name, $selected_id = NULL, $disabled = NULL) {
      if ($disabled === NULL) {
        $disabled = (!User::i()->can_access(SA_CUSTOMER_CREDIT));
      }
      $sql = "SELECT terms_indicator, terms, inactive FROM payment_terms";
      return select_box($name, $selected_id, $sql, 'terms_indicator', 'terms_indicator', array('disabled' => $disabled));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param null $disabled
     */
    static public function payment_terms_cells($label, $name, $selected_id = NULL, $disabled = NULL) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo GL_UI::payment_terms($name, $selected_id, $disabled);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param null $disabled
     */
    static public function payment_terms_row($label, $name, $selected_id = NULL, $disabled = NULL) {
      echo "<tr><td class='label'>$label</td>";
      GL_UI::payment_terms_cells(NULL, $name, $selected_id, $disabled);
      echo "</tr>\n";
    }
  }
