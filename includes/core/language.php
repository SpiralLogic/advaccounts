<?php
  /**********************************************************************
  Copyright (C) Advanced Group PTY LTD
  Released under the terms of the GNU General Public License, GPL,
  as published by the Free Software Foundation, either version 3
  of the License, or (at your option) any later version.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
  See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
   ***********************************************************************/
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core;

  class Language {

    /**
     * @var
     */
    public $name;
    /**
     * @var string
     * ar_EG, en_GB
     */
    public $code;
    /**
     * @var
     * eg. UTF-8, CP1256, ISO8859-1
     */
    public $encoding;
    /**
     * @var string
     * Currently support for Left-to-Right (ltr) and Right-To-Left (rtl)
     */
    public $dir;
    /**
     * @var
     */
    protected $installed_languages;
    /**
     * @var Language
     */
    static protected $i = NULL;
    /**
     * @var
     */
    public $is_locale_file;
    /**
     * @param        $name
     * @param        $code
     * @param        $encoding
     * @param string $dir
     */
    public function __construct($name, $code, $encoding, $dir = 'ltr') {
      $this->name = $name;
      $this->code = $code ? $code : 'en_US';
      $this->encoding = $encoding;
      $this->dir = $dir;
    }
    /**
     * @static
     * @return Language|null
     */
    static public function i() {
      return static::$i;
    }
    /**
     * @param $code
     */
    public function set_language($code) {
      $changed = $this->code != $code;
      $lang = Arr::search_value($code, Config::get('languages.installed'), 'code');
      if ($lang && $changed) {
        // flush cache as we can use several languages in one account
        Files::flush_dir(COMPANY_PATH . 'js_cache');
        $this->name = $lang['name'];
        $this->code = $lang['code'];
        $this->encoding = $lang['encoding'];
        $this->dir = isset($lang['rtl']) ? 'rtl' : 'ltr';
        $locale = DOCROOT . "lang/" . $this->code . "/locale.php";
        $this->is_locale_file = file_exists($locale);
      }
      $_SESSION['get_text']->set_language($this->code, $this->encoding);
      $_SESSION['get_text']->add_domain($this->code, DOCROOT . "lang");
      // Necessary for ajax calls. Due to bug in php 4.3.10 for this
      // version set globally in php.ini
      ini_set('default_charset', $this->encoding);
      if (isset($_SESSION["App"]) && $changed) {
        Session::i()->App->init();
      } // refresh menu
    }
    /**

     */
    static public function set() {
      if (!isset($_SESSION['Language']) || !method_exists($_SESSION['Language'], 'set_language')) {
        $l = Arr::search_value(Config::get('default.lang'), Config::get('languages.installed'), 'code');
        static::$i = new Language($l['name'], $l['code'], $l['encoding'], isset($l['rtl']) ? 'rtl' : 'ltr');
        static::$i->set_language(static::$i->code);
        if (file_exists(DOCROOT . "lang/" . static::$i->code . "/locale.php")) {
          /** @noinspection PhpIncludeInspection */
          include(DOCROOT . "lang/" . static::$i->code . "/locale.php");
        }
        $_SESSION['Language'] = static::$i;
      }
      else {
        static::$i = $_SESSION['Language'];
      }
    }
  }

  if (!function_exists("_")) {
    /**
     * @param $text
     *
     * @return mixed
     */
    function _($text) {
      $retVal = $_SESSION['get_text']->gettext($text);
      if ($retVal == "") {
        return $text;
      }
      return $retVal;
    }
  }
