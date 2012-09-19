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

  use ADV\App\Form\Form;
  use ADV\App\Item\Category;
  use ADV\Core\View;
  use ADV\Core\DB\DB;

  /**
   * @property Category $object
   */
  class Categoryies extends \ADV\App\Controller\Manage {
    protected $tableWidth = '80';
    protected function before() {
      $this->object = new Category();
      $this->runPost();
    }
    protected function index() {
      Page::start(_($help_context = "Item Categorys"), SA_ITEMCATEGORY);
      $this->generateTable();
      echo '<br>';
      $this->generateForm();
      Page::end(true);
    }
    /**
     * @param $form
     * @param $view
     *
     * @return mixed|void
     */
    protected function formContents(Form $form, View $view) {
      $view['title'] = 'Item Category';
      $form->text('description')->label("Category Name:")->focus();
      $form->checkbox('dflt_no_sale')->label("Exclude from sales:");
      $form->arraySelect('dflt_mb_flag', [STOCK_SERVICE=> 'Service', STOCK_MANUFACTURE=> 'Manufacture', STOCK_PURCHASED=> 'Purchased', STOCK_INFO=> 'Info'])->label('Type:');
      $form->custom(Item_Unit::select('dflt_units', null))->label(_("Units of Measure:"));
      $form->custom(Tax_ItemType::select('dflt_tax_type', '', null))->label(_("Tax Type:"));
      $form->custom(GL_UI::all('dflt_sales_act'))->label(_("Sales Account:"));
      $form->custom(GL_UI::all('dflt_inventory_act'))->label(_("Inventory Account:"));
      $form->custom(GL_UI::all('dflt_cogs_act'))->label(_("C.O.G.S. Account:"));
      $form->custom(GL_UI::all('dflt_adjustment_act'))->label(_("Inventory Adjustment Account:"));
      $form->custom(GL_UI::all('dflt_assembly_act'))->label(_("Assembly Cost Account:"));
      if ($this->object->dflt_mb_flag == STOCK_SERVICE || $this->object->dflt_mb_flag == STOCK_INFO) {
        $form->hide('dflt_cogs_act');
        $form->hide('dflt_inventory_act');
        $form->hide('dflt_adjustment_act');
        $form->hide('dflt_assembly_act');
      }
      if ($this->object->dflt_mb_flag == STOCK_PURCHASED) {
        $form->hide('dflt_assembly_act');
      }
      if ($this->object->dflt_mb_flag == STOCK_INFO) {
        $form->hide('dflt_sales_act');
      }
    }
    /**
     * @return array
     */
    protected function generateTableCols() {
      $cols = [
        ['type'=> 'skip'],
        'Name',
        'inactive'   => ['type'=> 'active'],
        ['type'=> 'skip'],
        'Units',
        ['type'=> 'skip'],
        'Sales'      => ['fun'=> [$this, 'formatAccounts'], 'useName'=> true],
        'COGS'       => ['fun'=> [$this, 'formatAccounts'], 'useName'=> true],
        'Inventory'  => ['fun'=> [$this, 'formatAccounts'], 'useName'=> true],
        'Adjustments'=> ['fun'=> [$this, 'formatAccounts'], 'useName'=> true],
        'Assemnbly'  => ['fun'=> [$this, 'formatAccounts'], 'useName'=> true],
        ['type'=> 'skip'],
        ['type'=> 'skip'],
        ['type'=> 'skip'],
        'Tax',
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatEditBtn']],
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatDeleteBtn']],
      ];

      return $cols;
    }
    /**
     * @param $row
     * @param $cellname
     *
     * @return string
     */
    public function formatAccounts($row, $cellname) {
      $unsed = [];
      if ($row['dflt_mb_flag'] == STOCK_SERVICE || $row['dflt_mb_flag'] == STOCK_INFO) {
        $unsed = [
          'dflt_cogs_act',
          'dflt_inventory_act',
          'dflt_adjustment_act',
          'dflt_assembly_act',
        ];
      } elseif ($row['dflt_mb_flag'] == STOCK_PURCHASED) {
        $unsed = ['dflt_assembly_act'];
      }
      if ($row['dflt_mb_flag'] == STOCK_INFO) {
        $unsed += ['dflt_sales_act'];
      }
      if (in_array($cellname, $unsed)) {
        return '-';
      }

      return $row[$cellname];
    }
  }


