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
  /**
   * @property Security i
   * @method Security i
   */
  use ArrayAccess;
  use Forms;
  use ADV\Core\DB\DB;
  use User;
  use ADV\Core\Config;

  class Security implements ArrayAccess
  {
    public $areas;
    protected $sections;
    protected $Config;
    /**
     * @param \ADV\Core\Config $config
     */
    public function __construct(\ADV\Core\Config $config = null) {
      $this->Config   = $config ? : Config::i();
      $this->areas    = $this->Config->_get('access_levels.areas');
      $this->sections = $this->Config->_get('access_levels.sections');
    }
    /**
     * @param User $user
     * @param      $page_level
     *
     * @return bool
     */
    public function hasAccess(User $user, $page_level) {

      if ($page_level === SA_OPEN) {
        return true;
      }
      if ($page_level === SA_DENIED || $page_level === '') {
        return false;
      }
      $access = false;
      if (isset($this->areas[$page_level])) {
        $code   = $this->areas[$page_level][0];
        $access = $code && $user->hasRole($code);
      } elseif ($user->hasSectionAccess($page_level)) {
        $access = $user->hasSectionAccess($page_level);
      }
      // only first registered company has site admin privileges
      return $access && ($user->company == 'default' || (isset($code) && ($code & ~0xff) != SS_SADMIN));
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public function get_role($id) {
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
    public static function add_role($name, $description, $sections, $areas) {
      $sql = "INSERT INTO security_roles (role, description, sections, areas) VALUES (" . //
        DB::escape($name) . "," . //
        DB::escape($description) . "," . //
        DB::escape(implode(';', $sections)) . ", " . //
        DB::escape(implode(';', $areas)) . ")";
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
    public static function update_role($id, $name, $description, $sections, $areas) {
      $sql = "UPDATE security_roles SET" . //
        " role=" . DB::escape($name) . "," . //
        " description=" . DB::escape($description) . "," . //
        " sections=" . DB::escape(implode(';', $sections)) . "," . //
        " areas=" . DB::escape(implode(';', $areas)) . //
        " WHERE id=$id";
      DB::query($sql, "could not update role");
    }
    /**
     * @static
     *
     * @param $id
     */
    public static function delete($id) {
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
    public static function check_role_used($id) {
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
    public static function roles($name, $selected_id = null, $new_item = false, $submit_on_change = false, $show_inactive = false) {
      $sql = "SELECT id, role, inactive FROM security_roles";
      return Forms::selectBox($name, $selected_id, $sql, 'id', 'description', array(
                                                                                   'spec_option'                               => $new_item ?
                                                                                     _("New role") : false,
                                                                                   'spec_id'                                   => '',
                                                                                   'select_submit'                             => $submit_on_change,
                                                                                   'show_inactive'                             => $show_inactive
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
    public static function roles_cells($label, $name, $selected_id = null, $new_item = false, $submit_on_change = false, $show_inactive = false) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo static::roles($name, $selected_id, $new_item, $submit_on_change, $show_inactive);
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
    public function roles_row($label, $name, $selected_id = null, $new_item = false, $submit_on_change = false, $show_inactive = false) {
      echo "<tr><td class='label'>$label</td>";
      Security::roles_cells(null, $name, $selected_id, $new_item, $submit_on_change, $show_inactive);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param $value
     *
     * @throws \RuntimeException
     * @return array|string
     */
    public static function htmlentities($value) {
      static $already_cleaned = [];
      // Nothing to escape for non-string scalars, or for already processed values
      if (is_bool($value) or is_int($value) or is_float($value) or in_array($value, $already_cleaned, true)) {
        return $value;
      }
      if (is_string($value)) {
        $value = htmlentities($value, ENT_COMPAT, 'UTF-8', false);
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
        // Throw exception when it wasn't whitelisted and can't be converted to String
        if (!method_exists($value, '__toString')) {
          throw new \RuntimeException('Object class "' . get_class($value) . '" could not be converted to string or ' . 'sanitized as ArrayAcces. Whitelist it in security.whitelisted_classes in app/config/config.php ' . 'to allow it to be passed unchecked.');
        }
        $value = static::htmlentities((string) $value);
      }
      return $value;
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     * </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     *       The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset) {
      // TODO: Implement offsetExists() method.
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     * </p>
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset) {
      switch ($offset) {
        case 'areas':
          return $this->areas;
        case 'sections':
          return $this->sections;
      }
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     * </p>
     * @param mixed $value  <p>
     *                      The value to set.
     * </p>
     *
     * @return void
     */
    public function offsetSet($offset, $value) {
      return;
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     *                      The offset to unset.
     * </p>
     *
     * @return void
     */
    public function offsetUnset($offset) {
      // TODO: Implement offsetUnset() method.
    }
  }
