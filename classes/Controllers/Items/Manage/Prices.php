<?php
  namespace ADV\Controllers\Items\Manage;

  use ADV\App\Form\Form;
  use ADV\App\Item\Price;
  use Item_Code;
  use ADV\Core\Num;
  use GL_Currency;
  use Sales_Type;
  use PDO;
  use Item_Price;
  use ADV\App\Display;
  use ADV\App\UI;
  use ADV\App\Validation;
  use ADV\Core\View;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Prices extends \ADV\App\Controller\Manage {
    protected $stock_id;
    protected $security = SA_SALESPRICE;
    protected $frame = false;
    protected function before() {
      $this->frame    = $this->Input->request('frame');
      $this->stock_id = $this->Input->getPostGlobal('stock_id');
      $this->object   = new Price();
      $this->runPost();
      $this->object->stock_id = $this->stock_id;
    }
    protected function beforeTable() {
      if (!$this->frame) {
        echo "<div class='bold center pad10 margin20 font13'>";
        UI::search(
          'stock',
          [
          'label'            => 'Item:',
          'url'              => 'Item',
          'idField'          => 'stock_id',
          'name'             => 'stock', //
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
      $view['title'] = 'Item Selling Prices';
      $form->hidden('id');
      $form->hidden('item_code_id');
      $form->hidden('stock_id');
      $form->custom(Sales_Type::select('sales_type_id'))->label("Sales Type:");
      $form->custom(GL_Currency::select('curr_abrev'))->label('Currency:');
      if (!$this->Input->hasPost('price')) {
        $_POST['price'] = Num::_priceFormat(Item_Price::get_kit($this->Input->post('stock_id'), $this->Input->post('curr_abrev'), $this->Input->post('sales_type_id')));
      }
      $kit = Item_Code::get_defaults($_POST['stock_id']);
      $form->amount('price')->label(_("Price:"))->append(_('per ') . $kit["units"])->focus();
    }
    protected function generateTable() {
      Display::div_start('table');
      if ($this->stock_id) {
        parent::generateTable();
      }
      if ($this->Input->post('_control') == 'stock_id') {
        $this->Ajax->activate('table');
      }
      Display::div_end();
    }
    /**
     * @return array
     */
    protected function generateTableCols() {
      $cols = [
        'Type',
        ['type'=> 'skip'],
        ['type'=> 'skip'],
        'stock_id',
        ['type'=> 'skip'],
        'Currency',
        'Price'=> ['type'=> 'amount'],
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatEditBtn']],
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatDeleteBtn']],

      ];
      return $cols;
    }
    /**
     * @param $pagername
     *
     * @return array
     */
    protected function getTableRows($pagername) {
      return Item_Price::getAll($this->stock_id)->fetchAll(PDO::FETCH_ASSOC);
    }
    protected function runValidation() {
      Validation::check(Validation::STOCK_ITEMS, _("There are no items defined in the system."));
      Validation::check(Validation::SALES_TYPES, _("There are no sales types in the system. Please set up sales types befor entering pricing."));
    }
  }

