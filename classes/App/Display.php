<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App;

  use GL_UI;
  use ADV\Core\Table;
  use ADV\Core\Ajax;
  use ADV\Core\JS;

  /**

   */
  class Display {
    /** @var JS */
    static $JS;
    /** @var User */
    static $User;
    /** @var Dates */
    static $Dates;
    static $DB;
    /**
     * @static
     *
     * @param      $string
     * @param bool $clean
     *
     * @internal param $label
     * @return array|mixed|string
     */
    public static function access_string($string, $clean = false) {
      static $used = [];
      $access = '';
      $string = preg_replace_callback(
        '/&([a-zA-Z0-9])/',
        function ($match) use (&$access, $clean, &$used) {
          if ($clean || in_array($match[1], $used)) {
            return $match[1];
          }
          $access = " accesskey='" . strtoupper($match[1]) . "'";
          $used[] = $match[1];
          return '<span class="u">' . $match[1] . '</span>';
        },
        $string
      );

      return $clean ? $string : array($string, $access);
    }
    /**
     * @static
     *
     * @param $msg
     */
    public static function heading($msg) {
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
    public static function is_voided($type, $id, $label) {
      if (!static::$Dates) {
        static::$Dates = Dates::i();
      }
      $void_entry = Voiding::get($type, $id);
      if ($void_entry == null) {
        return false;
      }
      Table::start('padded width50');
      echo "<tr><td class=center><span class='red'>$label</span><br>";
      echo "<span class='red'>" . _("Date Voided:") . " " . static::$Dates->sqlToDate($void_entry["date_"]) . "</span><br>";
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
     * @param      $url
     * @param      $label
     * @param null $id
     *
     * @return string
     */
    public static function menu_link($url, $label, $id = null) {
      if (!static::$JS) {
        static::$JS = JS::i();
      }
      $id   = static::$JS->defaultFocus($id);
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
    public static function menu_button($url, $label, $id = null) {
      if (!static::$JS) {
        static::$JS = JS::i();
      }
      $id   = static::$JS->defaultFocus($id);
      $pars = Display::access_string($label);
      return "<a href='$url' class='button  button-large' id='$id' $pars[1]>$pars[0]</a>";
    }
    /**
     * @static
     *
     * @param        $forward_to
     * @param string $params
     */
    public static function meta_forward($forward_to, $params = "") {
      echo "<meta http-equiv='Refresh' content='0; url=$forward_to?$params'>\n";
      echo "<div class='center'><br>" . _("You should automatically be forwarded.");
      echo " " . _("If this does not happen") . " <a href='$forward_to?$params'>" . _("click here") . "</a> " . _("to continue") . ".<br><br></div>\n";
      if ($params != '') {
        $params = '?' . $params;
      }
      Ajax::_redirect($forward_to . $params);
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
    public static function note($msg, $br = 0, $br2 = 0, $extra = "") {
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
     * @param      $target
     * @param      $label
     * @param bool $center
     * @param bool $button
     */
    public static function link_no_params($target, $label, $center = true, $button = false) {
      if (!static::$JS) {
        static::$JS = JS::i();
      }
      $id   = static::$JS->defaultFocus();
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
    public static function link_no_params_td($target, $label) {
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
    public static function link_params($target, $label, $link_params = '', $center = true, $params = '') {
      if (!static::$JS) {
        static::$JS = JS::i();
      }
      $id   = static::$JS->defaultFocus();
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
    public static function link_button($target, $label, $link_params = '', $center = true, $params = '') {
      if (!static::$JS) {
        static::$JS = JS::i();
      }
      $id   = static::$JS->defaultFocus();
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
    public static function link_params_td($target, $label, $link_params, $params = '') {
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
    public static function link_params_separate($target, $label, $params, $center = false, $nobr = false) {
      if (!static::$JS) {
        static::$JS = JS::i();
      }
      $id   = static::$JS->defaultFocus();
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
    public static function link_params_separate_td($target, $label, $params) {
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
    public static function submenu_option($title, $url, $id = null) {
      Display::note(Display::menu_button(ROOT_URL . ltrim($url, '/'), $title, $id), 1, 0);
    }
    /**
     * @static
     *
     * @param      $title
     * @param      $url
     * @param null $id*/
    public static function submenu_button($title, $url, $id = null) {
      Display::note(Display::menu_button(ROOT_URL . ltrim($url, '/'), $title, $id), 0, 1);
    }
    /**
     * @static
     *
     * @param      $title
     * @param      $type
     * @param      $number
     * @param null $id
     */
    public static function submenu_view($title, $type, $number, $id = null) {
      Display::note(GL_UI::viewTrans($type, $number, $title, false, 'menu_option button', $id), 0, 1, false);
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
    public static function submenu_print($title, $type, $number, $id = null, $email = 0, $extra = 0) {
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
    public static function viewer_link($label, $url = '', $class = '', $id = '', $icon = null) {
      if (!static::$User) {
        static::$User = User::i();
      }
      if ($url) {
        $class .= " openWindow";
      }
      if ($class) {
        $class = " class='$class'";
      }
      if ($id) {
        $class = " id='$id'";
      }
      if ($url) {
        $pars = Display::access_string($label);
        if (static::$User->_graphic_links() && $icon) {
          $pars[0] = Forms::setIcon($icon, $pars[0]);
        }
        $preview_str = "<a target='_blank' $class $id href='/" . e(ltrim($url, '/')) . "' $pars[1]>$pars[0]</a>";
      } else {
        $preview_str = $label;
      }
      return $preview_str;
    }
    private static function i() {
    }
  }
