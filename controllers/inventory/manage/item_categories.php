<?php
  use ADV\App\Form\Form;
  use ADV\Core\View;
  use ADV\Core\DB\DB;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class ItemCategory extends \ADV\App\Controller\Manage
  {

    protected function before() {
     $this->object = new \ADV\App\Item\Category();
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
      $form->hidden('category_id');
      $form->text('description')->label("Category Name:")->focus();
      $form->text('dflt_mb_flag')->label("Category Name:");
      $form->custom(Tax_ItemType::select('dflt_tax_type', '', null))->label(_("Item Tax Type:"));
      $form->custom(Item_Unit::select('dflt_units', null))->label(_("Units of Measure:"));
      $form->checkbox('dflt_no_sale')->label("Exclude from sales:");
      $form->custom(GL_UI::all('dflt_sales_act'))->label(_("Sales Account:"));
      $form->custom(GL_UI::all('dflt_cogs_act'))->label(_("C.O.G.S. Account:"));
      $form->hidden('dflt_inventory_act');
      $form->hidden('dflt_assembly_act');
      $form->custom(GL_UI::all('dflt_inventory_act'))->label(_("Inventory Account:"));
      $form->custom(GL_UI::all('dflt_cogs_act'))->label(_("C.O.G.S. Account:"));
      $form->custom(GL_UI::all('dflt_adjustment_act'))->label(_("Inventory Adjustments Account:"));
      $form->custom(GL_UI::all('dflt_assembly_act'))->label(_("Item Assembly Costs Account:"));
    }
    /**
     * @return array
     */
    protected function generateTableCols() {
      $cols = [
        ['type'=> 'skip'],
        'description',
        'inactive'=> ['type'=> 'active'],
        'dflt_tax_type',
        'dflt_units',
        'dflt_mb_flag',
        'dflt_sales_act',
        'dflt_cogs_act',
        'dflt_inventory_act',
        'dflt_adjustment_act',
        'dflt_assembly_act',
        'dflt_dim1',
        'dflt_dim2',
        'dflt_no_sale',
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatEditBtn']],
        ['type'=> 'insert', "align"=> "center", 'fun'=> [$this, 'formatDeleteBtn']],
      ];
      return $cols;
    }
  }

  new ItemCategory();

