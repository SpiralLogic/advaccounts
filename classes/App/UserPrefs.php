<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class UserPrefs
  {
    use \ADV\Core\Traits\SetFromArray;

    /**
     * @var Array|mixed
     */
    public $language;
    /** @var */
    public $qty_dec;
    /**
     * @var int
     */
    public $price_dec = 2;
    /** @var */
    public $exrate_dec = 4;
    /**
     * @var int
     */
    public $percent_dec = 0;
    /** @var */
    public $show_gl;
    /** @var */
    public $show_codes;
    /**
     * @var Array|mixed
     */
    public $date_format = 1;
    /**
     * @var Array|mixed
     */
    public $date_sep = '/';
    /**
     * @var int
     */
    public $tho_sep = 0;
    /**
     * @var int
     */
    public $dec_sep = 0;
    /**
     * @var string
     */
    public $theme = 'default';
    /** @var */
    public $print_profile;
    /** @var */
    public $rep_popup;
    /** @var */
    public $page_size; // for printing
    /** @var */
    public $show_hints;
    /** @var */
    public $query_size; // table pager page length
    /** @var */
    public $graphic_links; // use graphic links
    /**
     * @var int
     */
    public $sticky_doc_date = 0; // save date on subsequent document entry
    /**
     * @var Array|mixed
     */
    public $startup_tab; // default start-up menu tab
    /**
     * @param null $user
     */
    public function __construct($user = null) {
      if ($user == null) {
        // set default values, used before login
        $this->date_sep    = Config::get('date.ui_separator');
        $this->date_format = Config::get('date.ui_format');
        $this->language    = Config::get('default.language');
        $this->startup_tab = Config::get('apps.default');
      } else {
        $this->setFromArray($user);
        $_SESSION['language']->setLanguage($this->language);
      }
    }
    /**
     * @return string
     */
    public function date_display() {
      $date_seps = Config::get('date.separators');
      $sep       = $date_seps[$this->date_sep];
      if ($this->date_format == 0) {
        return "m" . $sep . "d" . $sep . "Y";
      } elseif ($this->date_format == 1) {
        return "d" . $sep . "m" . $sep . "Y";
      } else {
        return "Y" . $sep . "m" . $sep . "d";
      }
    }
    /**
     * @return mixed
     */
    public function tho_sep() {
      $tho_seps = Config::get('separators_thousands');

      return $tho_seps [$this->tho_sep];
    }
    /**
     * @return mixed
     */
    public function dec_sep() {
      $dec_seps = Config::get('separators_decimal');

      return $dec_seps [$this->dec_sep];
    }
  }
