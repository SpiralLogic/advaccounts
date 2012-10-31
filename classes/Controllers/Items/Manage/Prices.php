<?php
  namespace ADV\Controllers\Items\Manage;

  use ADV\App\Form\Form;
  use ADV\App\Item\Price;
  use Item_Price;
  use ADV\App\UI;
  use ADV\App\Validation;
  use ADV\Core\View;

  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Prices extends \ADV\App\Controller\Manage
  {
    protected $stock_id;
    protected $security = SA_SALESPRICE;
    protected $frame = false;
    protected $pager_type = self::PAGER_EDIT;
    protected $display_form = false;
    protected function before() {
      $this->frame = $this->Input->request('frame');
      $this->stock_id = $this->Input->getPostGlobal('stock_id');
      $this->object = new Price();
      $this->object->stock_id = $this->stock_id;
    }
    protected function beforeTable() {
      if (!$this->frame) {
        echo "<div class='bold center pad10 margin20 font13'>";
        UI::search(
          'stock_id',
          [
          'label' => 'Item:',
          'url' => 'Item',
          'idField' => 'stock_id',
          'name' => 'stock_id', //
          'value' => $this->stock_id,
          'focus' => true,
          ]
        );
        $this->Session->setGlobal('stock_id', $this->stock_id);
        echo "</div>";
      }
    }
    /**
     * @param $pagername
     *
     * @return array
     */
    protected function getTableRows($pagername) {
      return Item_Price::getAll($this->stock_id);
    }
    /**
     * @param \ADV\App\Form\Form $form
     * @param \ADV\Core\View     $view
     *
     * @return mixed
     */
    protected function formContents(Form $form, View $view) {
    }
    /**
     * @param \ADV\App\Pager\Edit $pager
     */
    protected function getEditing(\ADV\App\Pager\Edit $pager) {
      $pager->setObject($this->object);
      $pager->editing->stock_id = $this->stock_id;
    }
    protected function generateTable() {
      $this->Ajax->start_div('table');
      if ($this->stock_id) {
        parent::generateTable();
      }
      if ($this->Input->post('_control') == 'stock_id') {
        $this->Ajax->activate('table');
      }
      $this->Ajax->end_div();
    }
    /**
     * @return array
     */
    protected function generateTableCols() {
      return $this->object->generatePagerColumns();
    }
    protected function runValidation() {
      Validation::check(Validation::STOCK_ITEMS, _("There are no items defined in the system."));
      Validation::check(Validation::SALES_TYPES, _("There are no sales types in the system. Please set up sales types befor entering pricing."));
    }
  }

