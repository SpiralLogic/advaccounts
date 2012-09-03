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

  use ADV\Core\DB\DB;
  use ADV\Core\Cache\Cache;
  use ADV\Core\Event;
  use ADV\Core\Config;
  use ADV\Core\Auth;

  /**

   */
  class Users
  {
    /**
     * @static
     *
     * @param $user_id
     * @param $real_name
     * @param $phone
     * @param $email
     * @param $role_id
     * @param $language
     * @param $profile
     * @param $rep_popup
     * @param $pos
     * @param $hash
     *
     * @internal param $password
     */
    public static function  add($user_id, $real_name, $phone, $email, $role_id, $language, $profile, $rep_popup, $pos, $hash) {
      $sql
        = "INSERT INTO users (user_id, real_name, phone, email, role_id, language, pos, print_profile, rep_popup,hash)
                VALUES (" . DB::_escape($user_id) . ",
                " . DB::_escape($real_name) . ", " . DB::_escape($phone) . "," . DB::_escape($email) . ", " . DB::_escape($role_id) . ", " . DB::_escape(
        $language
      ) . ", " . DB::_escape($pos) . "," . DB::_escape($profile) . "," . DB::_escape($rep_popup) . "," . DB::_escape($hash) . " )";
      DB::_query($sql, "could not add user for $user_id");
    }
    /**
     * @static
     *
     * @param $id
     * @param $user_id
     * @param $real_name
     * @param $phone
     * @param $email
     * @param $role_id
     * @param $language
     * @param $profile
     * @param $rep_popup
     * @param $pos
     */
    public static function  update($id, $user_id, $real_name, $phone, $email, $role_id, $language, $profile, $rep_popup, $pos) {
      $sql = "UPDATE users SET real_name=" . DB::_escape($real_name) . ", phone=" . DB::_escape($phone) . ",
                email=" . DB::_escape($email) . ",
                role_id=" . DB::_escape($role_id) . ",
                language=" . DB::_escape($language) . ",
                print_profile=" . DB::_escape($profile) . ",
                rep_popup=" . DB::_escape($rep_popup) . ",
                pos=" . DB::_escape($pos) . ",
                user_id = " . DB::_escape($user_id) . " WHERE id=" . DB::_escape($id);
      DB::_query($sql, "could not update user for $user_id");
      session_regenerate_id();
    }
    /**
     * @static
     *
     * @param $id
     * @param $price_dec
     * @param $qty_dec
     * @param $exrate_dec
     * @param $percent_dec
     * @param $show_gl
     * @param $show_codes
     * @param $date_format
     * @param $date_sep
     * @param $tho_sep
     * @param $dec_sep
     * @param $theme
     * @param $page_size
     * @param $show_hints
     * @param $profile
     * @param $rep_popup
     * @param $query_size
     * @param $graphic_links
     * @param $language
     * @param $stickydate
     * @param $startup_tab
     */
    public static function  update_display_prefs(
      $id,
      $price_dec,
      $qty_dec,
      $exrate_dec,
      $percent_dec,
      $show_gl,
      $show_codes,
      $date_format,
      $date_sep,
      $tho_sep,
      $dec_sep,
      $theme,
      $page_size,
      $show_hints,
      $profile,
      $rep_popup,
      $query_size,
      $graphic_links,
      $language,
      $stickydate,
      $startup_tab
    ) {
      $sql
        = "UPDATE users SET
                price_dec=" . DB::_escape($price_dec) . ",
                qty_dec=" . DB::_escape($qty_dec) . ",
                exrate_dec=" . DB::_escape($exrate_dec) . ",
                percent_dec=" . DB::_escape($percent_dec) . ",
                show_gl=" . DB::_escape($show_gl) . ",
                show_codes=" . DB::_escape($show_codes) . ",
                date_format=" . DB::_escape($date_format) . ",
                date_sep=" . DB::_escape($date_sep) . ",
                tho_sep=" . DB::_escape($tho_sep) . ",
                dec_sep=" . DB::_escape($dec_sep) . ",
                theme=" . DB::_escape($theme) . ",
                page_size=" . DB::_escape($page_size) . ",
                show_hints=" . DB::_escape($show_hints) . ",
                print_profile=" . DB::_escape($profile) . ",
                rep_popup=" . DB::_escape($rep_popup) . ",
                query_size=" . DB::_escape($query_size) . ",
                graphic_links=" . DB::_escape($graphic_links) . ",
                language=" . DB::_escape($language) . ",
                sticky_doc_date=" . DB::_escape($stickydate) . ",
                startup_tab=" . DB::_escape($startup_tab) . "
                WHERE id = " . DB::_escape($id);
      DB::_query($sql, "could not update user display prefs for $id");
      session_regenerate_id();
    }
    /**
     * @static
     *
     * @param bool $all
     *
     * @return null|\PDOStatement
     */
    public static function  getAll($all = false) {
      $sql
        = "SELECT u.*, r.role FROM users u, security_roles r
                WHERE u.role_id=r.id";
      if (!$all) {
        $sql .= " AND !u.inactive";
      }

      return DB::_query($sql, "could not get users");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return \ADV\Core\DB\Query\Result
     */
    public static function  get($id) {
      $sql    = "SELECT * FROM users WHERE id=" . DB::_escape($id);
      $result = DB::_query($sql, "could not get user $id");

      return DB::_fetch($result);
    }
    //	This public static function is necessary for admin prefs update after upgrade from 2.1
    //
    /**
     * @static
     *
     * @param $user_id
     *
     * @return \ADV\Core\DB\Query\Result
     */
    public static function  get_by_login($user_id) {
      $sql    = "SELECT * FROM users WHERE user_id=" . DB::_escape($user_id);
      $result = DB::_query($sql, "could not get user $user_id");

      return DB::_fetch($result);
    }
    /**
     * @static
     *
     * @param $id
     */
    public static function  delete($id) {
      $sql = "DELETE FROM users WHERE id=" . DB::_escape($id);
      DB::_query($sql, "could not delete user $id");
    }
    /**
     * @static
     *
     * @param $user_id
     *
     * @internal param $password
     * @return bool|mixed
     */
    static public function  get_for_login($user_id) {
      $auth = new Auth($user_id);
      if ($auth->isBruteForce()) {
        return false;
      }

      return $auth->checkUserPassword($user_id);
    }
    /**
     * @static
     *
     * @param $user_id
     */
    public static function  update_visitdate($user_id) {
      $sql = "UPDATE users SET last_visit_date='" . date("Y-m-d H:i:s") . "'
                WHERE user_id=" . DB::_escape($user_id);
      DB::_query($sql, "could not update last visit date for user $user_id");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return mixed
     */
    public static function  check_activity($id) {
      $sql    = "SELECT COUNT(*) FROM audit_trail WHERE audit_trail.user=" . DB::_escape($id);
      $result = DB::_query($sql, "Cant check user activity");
      $ret    = DB::_fetch($result);

      return $ret[0];
    }
    /**
     * @static
     * @return string
     */
    public static function  show_online() {
      if (!Config::_get('ui_users_showonline') || !isset($_SESSION['get_text'])) {
        return "";
      }

      return _("users online") . ": " . static::get_online();
    }
    /**
     * @static
     * @return mixed
     */
    public static function get_ip() {
      /*
                                                                               This will find out if user is from behind proxy server.
                                                                               In that case, the script would count them all as 1 user.
                                                                               This public static function tryes to get real IP address.
                                                                               */
      if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
      } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
      } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED'];
      } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
      } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
        $ip = $_SERVER['HTTP_FORWARDED'];
      } else {
        $ip = $_SERVER['REMOTE_ADDR'];
      }

      return $ip;
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $value
     */
    public static function themes_row($label, $name, $value = null) {
      $themes = [];
      try {
        $themedir = new \DirectoryIterator(WEBROOT . THEME_PATH);
      } catch (\UnexpectedValueException $e) {
        Event::error($e->getMessage());
      }
      foreach ($themedir as $theme) {

        if (!$theme->isDot() && $theme->isDir()) {
          $themes[$theme->getFilename()] = $theme->getFilename();
        }
      }
      ksort($themes);
      echo "<tr><td class='label'>$label</td>\n<td>";
      echo Forms::arraySelect($name, $value, $themes);
      echo "</td></tr>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $all
     */
    public static function tabs_row($label, $name, $selected_id = null, $all = false) {
      global $installed_extensions;
      $tabs = [];
      foreach (ADVAccounting::i()->applications as $app) {
        $tabs[$app->id] = Display::access_string($app->name, true);
      }
      if ($all) { // add also not active ext. modules
        foreach ($installed_extensions as $ext) {
          if ($ext['type'] == 'module' && !$ext['active']) {
            $tabs[$ext['tab']] = Display::access_string($ext['title'], true);
          }
        }
      }
      echo "<tr>\n";
      echo "<td class='label'>$label</td><td>\n";
      echo Forms::arraySelect($name, $selected_id, $tabs);
      echo "</td></tr>\n";
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $spec_opt
     *
     * @return string
     */
    public static function select($name, $selected_id = null, $spec_opt = false,$inactive=false) {
      $sql = "SELECT id, real_name, inactive FROM users";

      return Forms::selectBox(
        $name,
        $selected_id,
        $sql,
        'id',
        'real_name',
        array(
             'order'       => array('real_name'),
             'spec_option' => $spec_opt,
             'spec_id'     => ALL_NUMERIC,
             'show_inactive'=>$inactive,
        )
      );
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $spec_opt
     */
    public static function cells($label, $name, $selected_id = null, $spec_opt = false) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>\n";
      echo Users::select($name, $selected_id, $spec_opt);
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
    public static function row($label, $name, $selected_id = null, $spec_opt = false) {
      echo "<tr><td class='label'>$label</td>";
      Users::cells(null, $name, $selected_id, $spec_opt);
      echo "</tr>\n";
    }
    /**
     * @static
     * @return int|mixed
     */
    protected static function get_online() {
      $usersonline = Cache::_get('users_online');
      if ($usersonline) {
        return $usersonline;
      }
      $result = DB::_query("SHOW TABLES LIKE 'useronline'");
      if (DB::_numRows($result) == 1) {
        $timeoutseconds = 120;
        $timestamp      = time();
        $timeout        = $timestamp - $timeoutseconds;
        $ip             = static::get_ip();
        // Add user to database
        DB::_insert('useronline')->values(array('timestamp' => $timestamp, 'ip' => $ip, 'file' => $_SERVER['DOCUMENT_URI']))->exec();
        //Remove users that were not online within $timeoutseconds.
        DB::_query("DELETE FROM useronline WHERE timestamp<" . $timeout);
        // Select online users
        $result = DB::_query("SELECT DISTINCT ip FROM useronline");
        $users  = DB::_numRows($result);
      } else {
        $users = 1;
      }
      Cache::_set('users_online', $users, 300);

      return $users;
    }
  }
