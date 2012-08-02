<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.core
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.core
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core;
  /**

   */
  class Language
  {
    use Traits\Singleton;

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
    protected $installed_languages;
    public $is_locale_file;
    /**
     * @param        $name
     * @param        $code
     * @param        $encoding
     * @param string $dir
     */
    public function __construct($name = null, $code = null, $encoding = null, $dir = 'ltr') {
      $l              = Arr::searchValue(Config::get('default.language'), Config::get('languages.installed'), 'code');
      $this->name     = $name ? : $l['name'];
      $this->code     = $code ? : $l['code'] ? : 'en_US';
      $this->encoding = $encoding ? : $l['encoding'];
      $this->dir      = isset($l['rtl']) ? 'rtl' : 'ltr';
      $this->setLanguage($this->code);
      if (file_exists(DOCROOT . "lang/" . $this->code . "/locale.php")) {
        /** @noinspection PhpIncludeInspection */
        include(DOCROOT . "lang/" . $this->code . "/locale.php");
      }
    }
    /**
     * @param $code
     */
    public function setLanguage($code) {
      $changed  = $this->code != $code;
      $language = Arr::searchValue($code, Config::get('languages.installed'), 'code');
      if ($language && $changed) {
        // flush cache as we can use several languages in one account
        Files::flushDir(COMPANY_PATH . 'js_cache');
        $this->name           = $language['name'];
        $this->code           = $language['code'];
        $this->encoding       = $language['encoding'];
        $this->dir            = isset($language['rtl']) ? 'rtl' : 'ltr';
        $locale               = DOCROOT . "lang/" . $this->code . "/locale.php";
        $this->is_locale_file = file_exists($locale);
      }
      $session = Session::i();
      //$session['get_text']->setLanguage($this->code, $this->encoding);
      //$session['get_text']->add_domain($this->code, DOCROOT . "lang");
      // Necessary for ajax calls. Due to bug in php 4.3.10 for this
      // version set globally in php.ini
      ini_set('default_charset', $this->encoding);
      if (isset($session['App']) && $changed) {
        \ADVAccounting::refresh();
      } // refresh menu
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
