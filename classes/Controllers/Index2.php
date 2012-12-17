<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      22/09/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers;

  use ADV\App\Controller\Action;
  use ADV\Core\JS;
  use DB_Company;
  use ADV\App\Item\Reorder;

  /** **/
  class Index2 extends Action
  {
    public $name = "Banking";
    public $help_context = "&Banking";
    /**

     */
    protected function index() {
      ini_set('xdebug.var_display_max_depth', 50);
      $reorders = new Reorder(['loc_code' => 'MEL', 'stockid' => '26382']);
      var_dump($reorders);
      $reorders->loc_code = 'TEST';
      var_dump($reorders);
      $reorders->save();
      var_dump($reorders);
      $reorders = new Reorder(['loc_code' => 'TEST4', 'stockid' => '26382']);
      //      $reorders->shelf_primary = 'TEST5';
      //$reorders->stock_id      = 'test';
      var_dump($reorders);
      //$reorders->save();
      var_dump($reorders);
    }
  }

