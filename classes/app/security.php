<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Security
  {
    /**
     * @static
     *
     * @param $page_security
     */
    public static function check_page($page_security)
    {
      if (!User::i()->can_access_page($page_security)) {
        echo "<div class='center'><br><br><br><span class='bold'>";
        echo _("The security settings on your account do not permit you to access this function");
        echo "</span>";
        echo "<br><br><br><br></div>";
        Page::end();
        exit;
      }
    }
    /*
               Helper function for setting page security level depeding on
               GET start variable and/or some value stored in session variable.
               Before the call $page_security should be set to default page_security value.
             */
    /**
     * @static
     *
     * @param null  $value
     * @param array $trans
     * @param array $gtrans
     *
     * @return mixed
     */
    public static function set_page($value = null, $trans = array(), $gtrans = array())
    {
      // first check is this is not start page call
      foreach ($gtrans as $key => $area) {
        if (isset($_GET[$key])) {
          Page::set_security($area);

          return;
        }
      }
      // then check session value
      if (isset($trans[$value])) {
        Page::set_security($trans[$value]);

        return;
      }
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    public static function get_role($id)
    {
      $sql = "SELECT * FROM security_roles WHERE id='$id'";
      $ret = DB::query($sql, "could not retrieve security roles");
      $row = DB::fetch($ret);
      if ($row != false) {
        $row['areas']    = explode(';', $row['areas']);
        $row['sections'] = explode(';', $row['sections']);
      }

      return $row;
    }
    /**
     * @static
     *
     * @param $name
     * @param $description
     * @param $sections
     * @param $areas
     */
    public static function add_role($name, $description, $sections, $areas)
    {
      $sql = "INSERT INTO security_roles (role, description, sections, areas)
            VALUES (" . DB::escape($name) . "," . DB::escape($description) . "," . DB::escape(implode(';', $sections)) . "," . DB::escape(implode(';', $areas)) . ")";
      DB::query($sql, "could not add new security role");
    }
    /**
     * @static
     *
     * @param $id
     * @param $name
     * @param $description
     * @param $sections
     * @param $areas
     */
    public static function update_role($id, $name, $description, $sections, $areas)
    {
      $sql = "UPDATE security_roles SET role=" . DB::escape($name) . ",description=" . DB::escape($description) . ",sections=" . DB::escape(implode(';', $sections)) . ",areas=" . DB::escape(implode(';', $areas)) . " WHERE id=$id";
      DB::query($sql, "could not update role");
    }
    /**
     * @static
     *
     * @param $id
     */
    public static function get_profile($id)
    {
      $sql = "DELETE FROM security_roles WHERE id=$id";
      DB::query($sql, "could not delete role");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return mixed
     */
    public static function check_role_used($id)
    {
      $sql = "SELECT count(*) FROM users WHERE role_id=$id";
      $ret = DB::query($sql, 'cannot check role usage');
      $row = DB::fetch($ret);

      return $row[0];
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $new_item
     * @param bool $submit_on_change
     * @param bool $show_inactive
     *
     * @return string
     */
    public static function  roles($name, $selected_id = null, $new_item = false, $submit_on_change = false, $show_inactive = false)
    {
      $sql = "SELECT id, role, inactive FROM security_roles";

      return select_box($name, $selected_id, $sql, 'id', 'description', array(
        'spec_option'   => $new_item ? _("New role") : false,
        'spec_id'       => '',
        'select_submit' => $submit_on_change,
        'show_inactive' => $show_inactive
      ));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $new_item
     * @param bool $submit_on_change
     * @param bool $show_inactive
     */
    public static function  roles_cells($label, $name, $selected_id = null, $new_item = false, $submit_on_change = false, $show_inactive = false)
    {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Security::roles($name, $selected_id, $new_item, $submit_on_change, $show_inactive);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $new_item
     * @param bool $submit_on_change
     * @param bool $show_inactive
     */
    public static function  roles_row($label, $name, $selected_id = null, $new_item = false, $submit_on_change = false, $show_inactive = false)
    {
      echo "<tr><td class='label'>$label</td>";
      Security::roles_cells(null, $name, $selected_id, $new_item, $submit_on_change, $show_inactive);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param $value
     *
     * @return array|string
     * @throws RuntimeException
     */
    public static function htmlentities($value)
    {
      static $already_cleaned = array();

      // Nothing to escape for non-string scalars, or for already processed values
      if (is_bool($value) or is_int($value) or is_float($value) or in_array($value, $already_cleaned, true)) {
        return $value;
      }

      if (is_string($value)) {
        $value = htmlentities($value, ENT_COMPAT, $_SESSION['Language']->encoding, false);
      } elseif (is_array($value) or ($value instanceof \Iterator and $value instanceof \ArrayAccess)) {
        // Add to $already_cleaned variable when object
        is_object($value) and $already_cleaned[] = $value;

        foreach ($value as $k => $v) {
          $value[$k] = static::htmlentities($v);
        }
      } elseif ($value instanceof \Iterator or get_class($value) == 'stdClass') {
        // Add to $already_cleaned variable
        $already_cleaned[] = $value;

        foreach ($value as $k => $v) {
          $value->{$k} = static::htmlentities($v);
        }
      } elseif (is_object($value)) {
        /*		// Check if the object is whitelisted and return when that's the case
    foreach (\Config::get('security.whitelisted_classes')[ array()) as $class]
    {
      if (is_a($value, $class)) {
        // Add to $already_cleaned variable
        $already_cleaned[] = $value;

        return $value;
      }
    }*/

        // Throw exception when it wasn't whitelisted and can't be converted to String
        if (!method_exists($value, '__toString')) {
          throw new \RuntimeException('Object class "' . get_class($value) . '" could not be converted to string or ' .
            'sanitized as ArrayAcces. Whitelist it in security.whitelisted_classes in app/config/config.php ' .
            'to allow it to be passed unchecked.');
        }

        $value = static::htmlentities((string) $value);
      }

      return $value;
    }
  }
