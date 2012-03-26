<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: advanced
   * Date: 31/10/11
   * Time: 8:06 PM
   * To change this template use File | Settings | File Templates.
   */
  class Report {

    public $id;
    public $name;
    public $ar_params;
    public $controls;
    const DATEBEGIN = 'DATEBEGIN';
    const DATEENDM = 'DATEENDM';
    const CUSTOMERS_NO_FILTER = 'CUSTOMERS_NO_FILTER';
    const CURRENCY = 'CURRENCY';
    const YES_NO = 'YES_NO';
    const DATE = 'DATE';
    const AREAS = 'AREAS';
    const TEXT = 'TEXT';
    const CATEGORIES = 'CATEGORIES';
    const LOCATIONS = 'LOCATIONS';
    const ITEMS = 'ITEMS';
    const DIMENSION = 'DIMENSION';
    const BANK_ACCOUNTS = 'BANK_ACCOUNTS';
    const SALESTYPES = 'SALESTYPES';
    const PO = 'PO';
    const REMITTANCE = 'REMITTANCE';
    const DATEBEGINM = 'DATEBEGINM';
    const SYS_TYPES_ALL = 'SYS_TYPES_ALL';
    const DATEENDTAX = 'DATEENDTAX';
    const DATEBEGINTAX = 'DATEBEGINTAX';
    const TRANS_YEARS = 'TRANS_YEARS';
    const DIMENSIONS1 = 'DIMENSIONS1';
    const COMPARE = 'COMPARE';
    const GL_ACCOUNTS = 'GL_ACCOUNTS';
    const DIMENSIONS2 = 'DIMENSIONS2';
    const ORDERS = 'ORDERS';
    const QUOTATIONS = 'QUOTATIONS';
    const WORKORDER = 'WORKORDER';
    const SYS_TYPES = 'SYS_TYPES';
    const DATEMONTH = 'DATEMONTH';
    const PAYMENT_LINK = 'PAYMENT_LINK';
    const INVOICE = 'INVOICE';
    const DELIVERY = 'DELIVERY';
    const RECEIPT = 'RECEIPT';
    const USERS = 'USERS';
    const SALESMEN = 'SALESMEN';
    const TEXTBOX = 'TEXTBOX';
    const DESTINATION = 'DESTINATION';
    const SUPPLIERS_NO_FILTER = 'SUPPLIERS_NO_FILTER';
    const GRAPHIC = 'GRAPHIC';
    /**
     * @param      $id
     * @param      $name
     * @param null $ar_params
     */
    public function __construct($id, $name, $ar_params = NULL) {
      $this->id = $id;
      $this->name = $name;
      if ($ar_params) {
        $this->set_controls($ar_params);
      }
    }
    /**
     * @param $ar_params
     */
    protected function set_controls($ar_params) {
      $this->controls = $ar_params;
    }
    public function get_controls() {
      return $this->controls;
    }
    public function add_custom_reports() {
      global $installed_extensions;
      // include reports installed inside extension modules
      if (count($installed_extensions) > 0) {
        $extensions = $installed_extensions;
        foreach ($extensions as $ext) {
          if (($ext['active'] && $ext['type'] == 'module')) {
            $file = PATH_TO_ROOT . '/' . $ext['path'] . "/reporting/reports_custom.php";
            if (file_exists($file)) {
              /** @noinspection PhpIncludeInspection */
              include_once($file);
            }
          }
        }
      }
      $file = COMPANY_PATH . "reporting/reports_custom.php";
      if (file_exists($file)) {
        /** @noinspection PhpIncludeInspection */
        include_once($file);
      }
    }
  }
