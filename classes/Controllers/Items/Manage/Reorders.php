<?php

  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers\Items\Manage;

  use ADV\Core\View;
  use ADV\App\Form\Form;

  class Reorders extends \ADV\App\Controller\InlinePager
  {
    protected $stock_id = null;
    protected $security = SA_REORDER;
    protected $frame = false;
    protected $tableWidth = '90';
    protected function before() {
      $this->stock_id = $this->Input->getPostGlobal('stock_id');
      $this->object = new \ADV\App\Item\Reorder();
      $this->runPost();
      if ($this->stock_id) {
        $this->object->stock_id = $this->stock_id;
        $this->object->stockid = \ADV\App\Item\Item::getStockID($this->stock_id);
      }
    }
    protected function index() {
      $this->Page->init($this->title, $this->security);
      $this->beforeTable();
      $this->generateTable();
      $btn = $this->formatBtn(SAVE, SAVE, ICON_SAVE);
      $btn['class'] = 'btn btn-primary margin20';
      echo '<div class="center">' . $btn . '</div>';
      $this->Page->end_page(true);
    }
    /**
     * @param $pagername
     *
     * @return mixed
     */
    protected function getTableRows($pagername) {
      return $this->object->getAll($this->stock_id);
    }
    protected function generateTableCols() {
      return $this->object->generateTableCols();
    }
    protected function runValidation() {
      Validation::check(Validation::COST_ITEMS, _("There are no inventory items defined in the system (Purchased or manufactured items)."), STOCK_SERVICE);
    }
  }
