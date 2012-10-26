<?php
  namespace ADV\Controllers\Items\Manage;

  use ADV\App\Item\Item;
  use Item_Price;
  use ADV\App\Item\Price;
  use ADV\App\Form\Form;
  use GL_UI;
  use Item_UI;
  use Tax_ItemType;
  use Item_Unit;
  use Item_Category;
  use ADV\Core\MenuUI;
  use ADV\Core\View;
  use ADV\App\UI;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Items extends \ADV\App\Controller\Action
  {
    protected $itemData;
    protected function before() {
      if (REQUEST_AJAX) {
        $this->runPost();
      }
      $this->JS->footerFile("/js/quickitems.js");
    }
    /**
     * @param $id
     *
     * @return string
     */
    protected function getItemData($id) {
      $data['item']        = $item = new Item($id);
      $data['stockLevels'] = $item->getStockLevels();
      return json_encode($data, JSON_NUMERIC_CHECK);
    }
    protected function runPost() {
      $data = [];
      if (isset($_POST['id'])) {
        if (isset($_POST['name'])) {
          $item = new Item($_POST);
          $item->save($_POST);
        } else {
          $item = new Item($_POST['id']);
        }
        $data['item']        = $item;
        $data['stockLevels'] = $item->getStockLevels();
        $data['status']      = $item->getStatus();
      }
      if (isset($_GET['page'])) {
        $data['page'] = $_GET['page'];
      }
      $this->JS->renderJSON($data);
    }
    protected function index() {
      $this->Page->init(_($help_context = "Items"), SA_CUSTOMER, isset($_GET['frame']));
      $view = new View('items/quickitems');
      $menu = new MenuUI('disabled');
      $view->set('menu', $menu);
      $form = new Form();
      $form->group('items');
      $form->hidden('id');
      $form->text('stock_id')->label('Item Code:');
      $form->text('name')->label('Item Name:');
      $form->textarea('long_description', ['rows' => 4])->label('Description:');
      $form->custom(Item_Category::select('category_id'))->label('Category:');
      $form->custom(Item_Unit::select('uom'))->label('Units:');
      $form->custom(Tax_ItemType::select('tax_type_id'))->label('Tax Type:');
      $form->group('accounts');
      $form->custom(Item_UI::type('mb_flag'))->label('Type:');
      $form->custom(GL_UI::all('sales_account'))->label('Sales Account:');
      $form->custom(GL_UI::all('inventory_account'))->label('Inventory Account:');
      $form->custom(GL_UI::all('cogs_account'))->label('Cost of Goods Sold Account:');
      $form->custom(GL_UI::all('adjustment_account'))->label('Adjustment Account:');
      $form->custom(GL_UI::all('assembly_account'))->label('Assembly Account:');
      $form->group('buttons');
      $form->submit('New')->type('primary')->id('btnNew');
      $form->submit(CANCEL)->type('danger')->preIcon(ICON_CANCEL)->id('btnCancel')->hide();
      $form->submit(SAVE)->type('success')->preIcon(ICON_SAVE)->id('btnConfirm')->hide();
      $view->set('form', $form);
      $this->JS->autocomplete('itemSearchId', 'Items.fetch', 'Item');
      if (!isset($_GET['stock_id'])) {
        $searchBox = UI::search(
          'itemSearchId',
          [
          'url'      => 'Item',
          'idField'  => 'stock_id',
          'name'     => 'itemSearchId', //
          'focus'    => true,
          'callback' => 'Items.fetch'
          ],
          true
        );
        $view->set('searchBox', $searchBox);
        $id = 0;
        $this->JS->setFocus('itemSearchId');
      } else {
        $id = Item::getStockId($_GET['stock_id']);
      }
      $data            = $this->getItemData($id);
      $price           = new Price();
      $price->stock_id = $id;
      $price_pager     = new \ADV\App\Pager\Edit('prices', $price->generatePagerColumns());
      $price_pager->setObject($price);
      $price_pager->setData(Item_Price::getAll($_GET['stock_id']));
      $view->set('prices', $price_pager);
      $view->set('firstPage', $this->Input->get('page', null, null));
      $view->render();
      $this->JS->tabs('tabs' . MenuUI::$menuCount, [], 0);
      $this->JS->onload("Items.onload($data);");
      $this->Page->end_page(true);
    }
  }

