<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 6/11/11
   * Time: 1:37 AM
   * To change this template use File | Settings | File Templates.
   */
  $GLOBALS['ajax_divs'] = array();
  /**

   */
  class Display {

    /**
     * @static
     *
     * @param      $label
     * @param bool $clean
     *
     * @return array|mixed|string
     */
    static public function access_string($label, $clean = FALSE) {

      $access = '';
      $slices = array();
      if (preg_match('/(.*)&([a-zA-Z0-9])(.*)/', $label, $slices)) {
        $label = $clean ? $slices[1] . $slices[2] . $slices[3] : $slices[1] . '<span class="u">' . $slices[2] . '</span>' .
          $slices[3];
        $access = " accesskey='" . strtoupper($slices[2]) . "'";
      }
      $label = str_replace('&&', '&', $label);
      return $clean ? $label : array($label, $access);
    }

    /**
     * @static
     *
     * @param bool   $cond
     * @param string $msg
     */
    static public function backtrace($cond = TRUE, $msg = '') {
      if ($cond) {
        if ($msg) {
          $str = "<div class='center'><span class='headingtext'>$msg</span></div>\n";
        }
        else {
          $str = '';
        }
        $str .= '<table >';
        $trace = debug_backtrace();
        foreach ($trace as $trn => $tr) {
          if (!$trn) {
            continue;
          }
          $str .= '<tr><td>';
          $str .= $tr['file'] . ':' . $tr['line'] . ': ';
          $str .= '</td><td>';
          if (isset($tr['type'])) {
            if ($tr['type'] == '::') {
              $str .= $tr['class'] . '::';
            }
            else {
              if ($tr['type'] == '->') {
                $str .= '(' . $tr['class'] . ' Object)' . '->';
              }
            }
          }
          foreach ($tr['args'] as $n => $a) {
            if (is_object($tr['args'][$n])) {
              $tr['args'][$n] = "(" . get_class($tr['args'][$n]) . " Object)";
            }
            if (is_array($tr['args'][$n])) {
              $tr['args'][$n] = "(Array[" . count($tr['args'][$n]) . "])";
            }
            else {
              $tr['args'][$n] = "'" . $tr['args'][$n] . "'";
            }
          }
          $str .= $tr['function'] . '(' . implode(',', $tr['args']) . ')</td>';
          $str .= '</tr>';
        }
        $str .= '</table>';
        Event::error($str);
      }
    }

    /**
     * @static
     *
     * @param int $num
     */
    static public function br($num = 1) {
      for ($i = 0; $i < $num; $i++) {
        echo "<br>";
      }
    }

    /**
     * @static
     *
     * @param string $id
     * @param null   $trigger
     * @param bool   $non_ajax
     */
    static public function div_start($id = '', $trigger = NULL, $non_ajax = FALSE) {
      global $ajax_divs;
      if ($non_ajax) { // div for non-ajax elements
        array_push($ajax_divs, array($id, NULL));
        echo "<div class='js hidden' " . ($id != '' ? "id='$id'" : '') . ">";
      }
      else { // ajax ready div
        array_push($ajax_divs, array($id, $trigger === NULL ? $id : $trigger));
        echo "<div " . ($id != '' ? "id='$id'" : '') . ">";
        ob_start();
      }
    }

    /**
     * @static
     */
    static public function div_end() {
      global $ajax_divs;
      if (count($ajax_divs)) {
        $div = array_pop($ajax_divs);
        if ($div[1] !== NULL) {
          Ajax::i()->addUpdate($div[1], $div[0], ob_get_flush());
        }
        echo "</div>";
      }
    }

    /**
     * @static
     *
     * @param $msg
     */
    static public function heading($msg) {
      echo "<div class='center'><span class='headingtext'>$msg</span></div>\n";
    }

    /**
     * @static
     *
     * @param $type
     * @param $id
     * @param $label
     *
     * @return bool
     */
    static public function is_voided($type, $id, $label) {
      $void_entry = Voiding::get($type, $id);
      if ($void_entry == NULL) {
        return FALSE;
      }
      start_table('tablestyle width50');
      echo "<tr><td class=center><span class='red'>$label</span><br>";
      echo "<span class='red'>" . _("Date Voided:") . " " . Dates::sql2date($void_entry["date_"]) . "</span><br>";
      if (strlen($void_entry["memo_"]) > 0) {
        echo "<div class='center'><span class='red'>" . _("Memo:") . " " . $void_entry["memo_"] . "</span></div><br>";
      }
      echo "</td></tr>";
      end_table(1);
      return TRUE;
    }

    /**
     * @static
     *
     * @param $stock_id
     */
    static public function item_heading($stock_id) {
      if ($stock_id != "") {
        $result = DB::query("SELECT description, units FROM stock_master WHERE stock_id=" . DB::escape($stock_id));
        $myrow = DB::fetch_row($result);
        static::heading("$stock_id - $myrow[0]");
        $units = $myrow[1];
        static::heading(_("in units of : ") . $units);
      }
    }

    /**
     * @static
     *
     * @param      $url
     * @param      $label
     * @param null $id
     *
     * @return string
     */
    static public function menu_link($url, $label, $id = NULL) {
      $id = JS::default_focus($id);
      $pars = Display::access_string($label);
      return "<a href='$url' class='menu_option' id='$id' $pars[1]>$pars[0]</a>";
    }

    /**
     * @static
     *
     * @param      $url
     * @param      $label
     * @param null $id
     *
     * @return string
     */
    static public function menu_button($url, $label, $id = NULL) {
      $id = JS::default_focus($id);
      $pars = Display::access_string($label);
      return "<a href='$url' class='button' id='$id' $pars[1]>$pars[0]</a>";
    }

    /**
     * @static
     *
     * @param        $forward_to
     * @param string $params
     */
    static public function meta_forward($forward_to, $params = "") {
      echo "<meta http-equiv='Refresh' content='0; url=$forward_to?$params'>\n";
      echo "<div class='center'><br>" . _("You should automatically be forwarded.");
      echo " " . _("If this does not happen") . " <a href='$forward_to?$params'>" . _("click here") . "</a> " . _("to continue") . ".<br><br></div>\n";
      if ($params != '') {
        $params = '?' . $params;
      }
      Ajax::i()->redirect($forward_to . $params);
      exit;
    }

    /**
     * @static
     *
     * @param        $msg
     * @param int    $br
     * @param int    $br2
     * @param string $extra
     */
    static public function note($msg, $br = 0, $br2 = 0, $extra = "") {
      for ($i = 0; $i < $br; $i++) {
        echo "<br>";
      }
      if ($extra != "") {
        echo "<div class='center'><span $extra>$msg</span></div>\n";
      }
      else {
        echo "<div class='center'>$msg</div>\n";
      }
      for ($i = 0; $i < $br2; $i++) {
        echo "<br>";
      }
    }

    /**
     * @static
     *
     * @param bool $center
     * @param bool $no_menu
     */
    static public function link_back($center = TRUE, $no_menu = TRUE) {
      if ($center) {
        echo "<div class='center margin20'>";
      }
      echo "<a class='button' href='javascript:(window.history.length <= 1) ? window.close() : window.history.go(-1);'>" . ($no_menu ?
        _("Close") : _("Back")) . "</a>\n";
      if ($center) {
        echo "</div>";
      }
    }

    /**
     * @static
     *
     * @param      $target
     * @param      $label
     * @param bool $center
     * @param bool $button
     */
    static public function link_no_params($target, $label, $center = TRUE, $button = FALSE) {
      $id = JS::default_focus();
      $pars = Display::access_string($label);
      if ($target == '') {
        $target = $_SERVER['PHP_SELF'];
      }
      if ($center) {
        echo "<br><div class='center'>";
      }
      if ($button) {
        $pars[1] .= " class='button'";
      }
      echo "<a href='$target' id='$id' $pars[1] >$pars[0]</a>\n";
      if ($center) {
        echo "</div>";
      }
    }

    /**
     * @static
     *
     * @param $target
     * @param $label
     */
    static public function link_no_params_td($target, $label) {
      echo "<td>";
      Display::link_no_params($target, $label);
      echo "</td>\n";
    }

    /**
     * @static
     *
     * @param        $target
     * @param        $label
     * @param string $link_params
     * @param bool   $center
     * @param string $params
     */
    static public function link_params($target, $label, $link_params = '', $center = TRUE, $params = '') {
      $id = JS::default_focus();
      $pars = Display::access_string($label);
      if ($target == '') {
        $target = $_SERVER['PHP_SELF'];
      }
      if ($center) {
        echo "<br><div class='center'>";
      }
      echo "<a id='$id' href='$target?$link_params' $params $pars[1] >$pars[0]</a>\n";
      if ($center) {
        echo "</div>";
      }
    }

    /**
     * @static
     *
     * @param        $target
     * @param        $label
     * @param string $link_params
     * @param bool   $center
     * @param string $params
     */
    static public function link_button($target, $label, $link_params = '', $center = TRUE, $params = '') {
      $id = JS::default_focus();
      $pars = Display::access_string($label);
      if ($target == '') {
        $target = $_SERVER['PHP_SELF'];
      }
      if ($center) {
        echo "<br><div class='center'>";
      }
      echo "<a id='$id' class='button' href='$target?$link_params' $params $pars[1] >$pars[0]</a>\n";
      if ($center) {
        echo "</div>";
      }
    }

    /**
     * @static
     *
     * @param        $target
     * @param        $label
     * @param        $link_params
     * @param string $params
     */
    static public function link_params_td($target, $label, $link_params, $params = '') {
      echo "<td>";
      Display::link_params($target, $label, $link_params, FALSE, $params);
      echo "</td>\n";
    }

    /**
     * @static
     *
     * @param      $target
     * @param      $label
     * @param      $params
     * @param bool $center
     * @param bool $nobr
     */
    static public function link_params_separate($target, $label, $params, $center = FALSE, $nobr = FALSE) {
      $id = JS::default_focus();
      $pars = Display::access_string($label);
      if (!$nobr) {
        echo "<br>";
      }
      if ($center) {
        echo "<div class='center'>";
      }
      echo "<a target='_blank' id='$id' href='$target?$params' $pars[1]>$pars[0]</a>\n";
      if ($center) {
        echo "</div>";
      }
    }

    /**
     * @static
     *
     * @param $target
     * @param $label
     * @param $params
     */
    static public function link_params_separate_td($target, $label, $params) {
      echo "<td>";
      Display::link_params_separate($target, $label, $params);
      echo "</td>\n";
    }

    /**
     * Bind editors for various selectors.
     *
     * @param      $type  - type of editor
     * @param      $input - name of related input field
     * @param bool $caller- optional function key code (available values F1-F12: 112-123, true: default)
     *
     * @return array
     */
    static public function set_editor($type, $input, $caller = TRUE) {
      static $Editors = array();
      /* Table editor interfaces. Key is editor type
                     0 => url of editor page
                     1 => hotkey code
                     2 => context help
                   */
      if ($type === FALSE && $input === FALSE) {
        return $Editors;
      }
      $popup_editors = array(
        'customer' => array(
          '/sales/manage/customers.php?debtor_no=', 113, _("Customers")
        ), 'branch' => array(
          '/sales/manage/customer_branches.php?SelectedBranch=', 114, _("Branches")
        ), 'supplier' => array(
          '/purchases/manage/suppliers.php?supplier_id=', 113, _("Suppliers")
        ), 'item' => array(
          '/inventory/manage/items.php?stock_id=', 115, _("Items")
        )
      );
      $key = $caller === TRUE ? $popup_editors[$type][1] : $caller;
      $Editors[$key] = array(PATH_TO_ROOT . $popup_editors[$type][0], $input);
      /*	$help = 'F' . ($key - 111) . ' - ';
                        $help .= $popup_editors[$type][2];
                        $Pagehelp[] = $help;*/
    }

    /**
     * @static
     *
     * @param      $title
     * @param      $url
     * @param null $id
     */
    static public function submenu_option($title, $url, $id = NULL) {
      Display::note(Display::menu_button(PATH_TO_ROOT . $url, $title, $id), 0, 1);
    }

    /**
     * @static
     *
     * @param      $title
     * @param      $url
     * @param null $id*/
    static public function submenu_button($title, $url, $id = NULL) {
      Display::note(Display::menu_button(PATH_TO_ROOT . $url, $title, $id), 0, 1);
    }

    /**
     * @static
     *
     * @param      $title
     * @param      $type
     * @param      $number
     * @param null $id
     */
    static public function submenu_view($title, $type, $number, $id = NULL) {
      Display::note(GL_UI::trans_view($type, $number, $title, FALSE, 'menu_option button', $id), 0, 1, FALSE);
    }

    /**
     * @static
     *
     * @param           $title
     * @param           $type
     * @param           $number
     * @param null      $id
     * @param int|null  $email
     * @param int       $extra
     */
    static public function submenu_print($title, $type, $number, $id = NULL, $email = 0, $extra = 0) {
      Display::note(Reporting::print_doc_link($number, $title, TRUE, $type, FALSE, 'button printlink', $id, $email, $extra), 1, 0);
    }

    /**
     * @static
     *
     * @param        $label
     * @param string $url
     * @param string $class
     * @param string $id
     * @param null   $icon
     *
     * @return string
     */
    static public function viewer_link($label, $url = '', $class = '', $id = '', $icon = NULL) {
      if ($url != '') {
        $class .= " openWindow";
      }
      if ($class != '') {
        $class = " class='$class'";
      }
      if ($id != '') {
        $class = " id='$id'";
      }
      if ($url != "") {
        $pars = Display::access_string($label);
        if (User::graphic_links() && $icon) {
          $pars[0] = set_icon($icon, $pars[0]);
        }
        $preview_str = "<a target='_blank' $class $id href='" . e(PATH_TO_ROOT . "/" . $url) . "' $pars[1]>$pars[0]</a>";
      }
      else {
        $preview_str = $label;
      }
      return $preview_str;
    }
  }
