<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers\Items\Manage;

  use ADV\App\Controller\Manage;
  use Item_Unit;
  use ADV\App\Display;
  use ADV\App\Validation;
  use ADV\App\UI;
  use ADV\App\Item\Purchase;
  use ADV\Core\View;
  use ADV\App\Form\Form;

  /**

   */
  class Purchasing extends Manage {
    protected $stock_id;
    protected $security = SA_PURCHASEPRICING;
    protected $frame = false;
    protected function before() {
      $this->frame    = $this->Input->request('frame');
      $this->stock_id = $this->Input->getPostGlobal('stock_id');
      $this->object   = new Purchase();
      $this->runPost();
      $this->object->stock_id = $this->stock_id;
    }
    protected function beforeTable() {
      if (!$this->frame) {
        echo "<div class='bold center pad10 margin20 font13'>";
        UI::search(
          'stock_id',
          [
          'label'            => 'Item:',
          'url'              => 'Item',
          'idField'          => 'stock_id',
          'name'             => 'stock_id', //
          'value'            => $this->stock_id,
          'focus'            => true,
          ]
        );
        $this->Session->setGlobal('stock_id', $this->stock_id);
        echo "</div>";
      }
    }
    /**
     * @param \ADV\App\Form\Form $form
     * @param \ADV\Core\View     $view
     *
     * @return mixed
     */
    protected function formContents(Form $form, View $view) {

      $view['title'] = 'Item Purchase Prices';
      $form->hidden('id');
      $form->hidden('stockid');
      $form->hidden('stock_id');
      $form->hidden('creditor_id');
      $form->text('supplier', ['class'=> 'nosubmit'])->label('Supplier:');
      $this->JS->autocomplete('supplier', '"creditor_id"', 'Creditor');
      $form->amount('price')->label(_("Price:"));
      $form->custom(Item_Unit::select('suppliers_uom'))->label('Supplier\'s UOM:')->val('ea');
      $form->number('conversion_factor', 6)->label('Conversion Factor:');
      $form->text('supplier_description')->label('Supplier Product Code:');
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
      return [
        ['type'=> 'skip'],
        _("Supplier"),
        _("Code"),
        ['type'=> 'skip'],
        _("Price")            => ['type'=> 'amount'],
        _("Supplier's UOM"),
        _("Conversion Factor")=> ['type'=> 'rate'],
        _("Supplier's Code"),
        _("Updated")          => ['type'=> 'date'],
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatEditBtn']],
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatDeleteBtn']],
      ];
    }
    /**
     * @param $pagername
     *
     * @return mixed
     */
    protected function getTableRows($pagername) {
      return $this->object->getAll($this->stock_id);
    }
    protected function runValidation() {
      Validation::check(Validation::PURCHASE_ITEMS, _("There are no purchasable inventory items defined in the system."), STOCK_PURCHASED);
      Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
    }
  }
