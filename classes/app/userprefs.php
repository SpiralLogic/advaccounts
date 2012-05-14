<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class userPrefs {

  use \ADV\Core\Traits\SetFromArray;

    /**
     * @var Array|mixed
     */
    public $language;
    /**
     * @var
     */
    public $qty_dec;
    /**
     * @var int
     */
    public $price_dec = 2;
    /**
     * @var
     */
    public $exrate_dec = 4;
    /**
     * @var int
     */
    public $percent_dec = 0;
    /**
     * @var
     */
    public $show_gl;
    /**
     * @var
     */
    public $show_codes;
    /**
     * @var Array|mixed
     */
    public $date_format;
    /**
     * @var Array|mixed
     */
    public $date_sep;
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
    /**
     * @var
     */
    public $print_profile;
    /**
     * @var
     */
    public $rep_popup;
    /**
     * @var
     */
    public $page_size; // for printing
    /**
     * @var
     */
    public $show_hints;
    /**
     * @var
     */
    public $query_size; // table pager page length
    /**
     * @var
     */
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
    function __construct($user = NULL) {
      if ($user == NULL) {
        // set default values, used before login
        $this->date_sep = Config::get('date.ui_separator');
        $this->date_format = Config::get('date.ui_format');
        $this->language = Config::get('default.lang');
        $this->startup_tab = Config::get('apps.default');
      }
      else {
        $this->setFromArray($user);
        $_SESSION['Language']->set_language($this->language);
      }
    }
    /**
     * @return Array|mixed
     */
    function language() {
      return $this->language;
    }
    function qty_dec() {
      return $this->qty_dec;
    }
    /**
     * @return int
     */
    function price_dec() {
      return $this->price_dec;
    }
    function exrate_dec() {
      return $this->exrate_dec;
    }
    /**
     * @return int
     */
    function percent_dec() {
      return $this->percent_dec;
    }
    function show_gl() {
      return $this->show_gl;
    }
    function show_codes() {
      return $this->show_codes;
    }
    /**
     * @return Array|mixed
     */
    function date_format() {
      return $this->date_format;
    }
    /**
     * @return Array|mixed
     */
    function date_sep() {
      return $this->date_sep;
    }
    /**
     * @return string
     */
    function date_display() {
      $date_seps = Config::get('date.separators');
      $sep = $date_seps[$this->date_sep];
      if ($this->date_format == 0) {
        return "m" . $sep . "d" . $sep . "Y";
      }
      elseif ($this->date_format == 1) {
        return "d" . $sep . "m" . $sep . "Y";
      }
      else {
        return "Y" . $sep . "m" . $sep . "d";
      }
    }
    /**
     * @return mixed
     */
    function tho_sep() {
      $tho_seps = Config::get('separators_thousands');
      return $tho_seps [$this->tho_sep];
    }
    /**
     * @return mixed
     */
    function dec_sep() {
      $dec_seps = Config::get('separators_decimal');
      return $dec_seps [$this->dec_sep];
    }
    /**
     * @return string
     */
    function get_theme() {
      return $this->theme;
    }
    function get_pagesize() {
      return $this->page_size;
    }
    function show_hints() {
      return $this->show_hints;
    }
    function print_profile() {
      return $this->print_profile;
    }
    function rep_popup() {
      return $this->rep_popup;
    }
    function query_size() {
      return $this->query_size;
    }
    function graphic_links() {
      return $this->graphic_links;
    }
    /**
     * @return int
     */
    function sticky_doc_date() {
      return $this->sticky_doc_date;
    }
    /**
     * @return Array|mixed
     */
    function start_up_tab() {
      return $this->startup_tab;
    }

  }
