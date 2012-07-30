<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Extensions
  {
    /**
     * @static
     *
     * @param $id
     *
     * @return array
     */
    public static function get_access($id)
    {
      global $installed_extensions;
      $ext               = $installed_extensions[$id];
      $security_sections = $security_areas = [];
      if (isset($ext['acc_file'])) {
        /** @noinspection PhpIncludeInspection */
        include(DOCROOT . ($ext['type'] == 'plugin' ? 'classes/Modules/' : DS) . $ext['path'] . DS . $ext['acc_file']);
      }

      return array($security_areas, $security_sections);
    }
    /**
     * @static

     */
    public static function add_access(User $user)
    {
      $installed_extensions = Config::get('extensions.installed');
      /** @noinspection PhpUnusedLocalVariableInspection */
      foreach ($installed_extensions as $extid => $ext) {
        $scode       = 100;
        $acode       = 100;
        $accext      = static::get_access($extid);
        $extsections = $accext[1];
        $extareas    = $accext[0];
        $extcode     = $extid << 16;
        $trans       = [];
        foreach ($extsections as $code => $name) {
          $trans[$code] = $scode << 8;
          // reassign section codes
          $user->Security['sections'][$trans[$code] | $extcode] = $name;
          $scode++;
        }
        foreach ($extareas as $code => $area) {
          $section = $area[0] & 0xff00;
          // extension modules:
          // if area belongs to nonstandard section
          // use translated section codes and
          // preserve lower part of area code
          if (isset($trans[$section])) {
            $section = $trans[$section];
          }
          // otherwise assign next available
          // area code >99
          $area[0]                        = $extcode | $section | ($acode++);
          $user->Security['areas'][$code] = $area;
        }
      }
    }
    /**
     * List of sets of active extensions
     *
     * @param      $name
     * @param null $value
     * @param bool $submit_on_change
     *
     * @return string
     */
    public static function view($name, $value = null, $submit_on_change = false)
    {
      $items = [];
      foreach (Config::getAll('db') as $comp) {
        $items[] = sprintf(_("Activated for '%s'"), $comp['name']);
      }

      return Forms::arraySelect($name, $value, $items, array(
                                                            'spec_option'   => _("Installed on system"),
                                                            'spec_id'       => -1,
                                                            'select_submit' => $submit_on_change,
                                                            'async'         => true
                                                       ));
    }
  }
