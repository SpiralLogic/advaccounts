<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  $GLOBALS['ajax_divs'] = array();
  /**

   */
  class Display
  {
    /**
     * @static
     *
     * @param      $label
     * @param bool $clean
     *
     * @return array|mixed|string
     */
    public static function access_string($label, $clean = false)
    {
      $access = '';
      $slices = array();
      if (preg_match('/(.*)&([a-zA-Z0-9])(.*)/', $label, $slices)) {
        $label  = $clean ? $slices[1] . $slices[2] . $slices[3] :
          $slices[1] . '<span class="u">' . $slices[2] . '</span>' . $slices[3];
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
    public static function backtrace($cond = true, $msg = '')
    {
      if ($cond) {
        if ($msg) {
          $str = "<div class='center'><span class='headingtext'>$msg</span></div>\n";
        } else {
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
            } else {
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
            } else {
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
    public static function br($num = 1)
    {
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
    public static function div_start($id = '', $trigger = null, $non_ajax = false)
    {
      global $ajax_divs;
      if ($non_ajax) { // div for non-ajax elements
        array_push($ajax_divs, array($id, null));
        echo "<div class='js hidden' " . ($id != '' ? "id='$id'" : '') . ">";
      } else { // ajax ready div
        array_push($ajax_divs, array($id, $trigger === null ? $id : $trigger));
        echo "<div " . ($id != '' ? "id='$id'" : '') . ">";
        ob_start();
      }
    }
    /**
     * @static
     */
    public static function div_end()
    {
      global $ajax_divs;
      if (count($ajax_divs)) {
        $div = array_pop($ajax_divs);
        if ($div[1] !== null) {
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
    public static function heading($msg)
    {
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
    public static function is_voided($type, $id, $label)
    {
      $void_entry = Voiding::get($type, $id);
      if ($void_entry == null) {
        return false;
      }
      Table::start('tablestyle width50');
      echo "<tr><td class=center><span class='red'>$label</span><br>";
      echo "<span class='red'>" . _("Date Voided:") . " " . Dates::sql2date($void_entry["date_"]) . "</span><br>";
      if (strlen($void_entry["memo_"]) > 0) {
        echo "<div class='center'><span class='red'>" . _("Memo:") . " " . $void_entry["memo_"] . "</span></div><br>";
      }
      echo "</td></tr>";
      Table::end(1);

      return true;
    }
    /**
     * @static
     *
     * @param $stock_id
     */
    public static function item_heading($stock_id)
    {
      if ($stock_id != "") {
        $result = DB::query("SELECT description, units FROM stock_master WHERE stock_id=" . DB::escape($stock_id));
        $myrow  = DB::fetch_row($result);
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
    public static function menu_link($url, $label, $id = null)
    {
      $id   = JS::default_focus($id);
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
    public static function menu_button($url, $label, $id = null)
    {
      $id   = JS::default_focus($id);
      $pars = Display::access_string($label);

      return "<a href='$url' class='button  button-large' id='$id' $pars[1]>$pars[0]</a>";
    }
    /**
     * @static
     *
     * @param        $forward_to
     * @param string $params
     */
    public static function meta_forward($forward_to, $params = "")
    {
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
    public static function note($msg, $br = 0, $br2 = 0, $extra = "")
    {
      for ($i = 0; $i < $br; $i++) {
        echo "<br>";
      }
      if ($extra != "") {
        echo "<div class='center'><span $extra>$msg</span></div>\n";
      } else {
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
    public static function link_back($center = true, $no_menu = true)
    {
      if ($center) {
        echo "<div class='center margin20'>";
      }
      echo "<a class='button' href='javascript:(window.history.length === 1) ? window.close() : window.history.go(-1);'>" . ($no_menu ?
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
    public static function link_no_params($target, $label, $center = true, $button = false)
    {
      $id   = JS::default_focus();
      $pars = Display::access_string($label);
      if ($target == '') {
        $target = $_SERVER['DOCUMENT_URI'];
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
    public static function link_no_params_td($target, $label)
    {
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
    public static function link_params($target, $label, $link_params = '', $center = true, $params = '')
    {
      $id   = JS::default_focus();
      $pars = Display::access_string($label);
      if ($target == '') {
        $target = $_SERVER['DOCUMENT_URI'];
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
    public static function link_button($target, $label, $link_params = '', $center = true, $params = '')
    {
      $id   = JS::default_focus();
      $pars = Display::access_string($label);
      if ($target == '') {
        $target = $_SERVER['DOCUMENT_URI'];
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
    public static function link_params_td($target, $label, $link_params, $params = '')
    {
      echo "<td>";
      Display::link_params($target, $label, $link_params, false, $params);
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
    public static function link_params_separate($target, $label, $params, $center = false, $nobr = false)
    {
      $id   = JS::default_focus();
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
    public static function link_params_separate_td($target, $label, $params)
    {
      echo "<td>";
      Display::link_params_separate($target, $label, $params);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $title
     * @param      $url
     * @param null $id
     */
    public static function submenu_option($title, $url, $id = null)
    {
      Display::note(Display::menu_button(BASE_URL . ltrim($url, '/'), $title, $id), 1, 0);
    }
    /**
     * @static
     *
     * @param      $title
     * @param      $url
     * @param null $id*/
    public static function submenu_button($title, $url, $id = null)
    {
      Display::note(Display::menu_button(BASE_URL . ltrim($url, '/'), $title, $id), 0, 1);
    }
    /**
     * @static
     *
     * @param      $title
     * @param      $type
     * @param      $number
     * @param null $id
     */
    public static function submenu_view($title, $type, $number, $id = null)
    {
      Display::note(GL_UI::trans_view($type, $number, $title, false, 'menu_option button', $id), 0, 1, false);
    }
    /**
     * @static
     *
     * @param          $title
     * @param          $type
     * @param          $number
     * @param null     $id
     * @param int|null $email
     * @param int      $extra
     */
    public static function submenu_print($title, $type, $number, $id = null, $email = 0, $extra = 0)
    {
      Display::note(Reporting::print_doc_link($number, $title, true, $type, false, 'button printlink', $id, $email, $extra), 1, 0);
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
    public static function viewer_link($label, $url = '', $class = '', $id = '', $icon = null)
    {
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
          $pars[0] = Form::setIcon($icon, $pars[0]);
        }
        $preview_str = "<a target='_blank' $class $id href='/" . e(ltrim($url, '/')) . "' $pars[1]>$pars[0]</a>";
      } else {
        $preview_str = $label;
      }

      return $preview_str;
    }
  }
