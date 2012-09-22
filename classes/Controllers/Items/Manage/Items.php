<?php
  namespace ADV\Controllers\Items\Manage;

  use ADV\App\Item\Item;
  use GL_UI;
  use Item_UI;
  use Tax_ItemType;
  use Item_Unit;
  use Item_Category;
  use ADV\Core\MenuUI;
  use ADV\Core\View;
  use ADV\App\Page;
  use ADV\App\UI;
  use ADV\App\ADVAccounting;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Items extends \ADV\App\Controller\Base {
    protected $itemData;
    protected function before() {
      ADVAccounting::i()->set_selected('items');
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
      if (isset($_GET['term'])) {
        $data = Item::search($_GET['term']);
      } elseif (isset($_POST['id'])) {
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
      Page::start(_($help_context = "Items"), SA_CUSTOMER, isset($_GET['frame']));
      $view = new View('items/quickitems');
      $menu = new MenuUI();
      $view->set('menu', $menu);
      $view->set('stock_cats', Item_Category::select('category_id'));
      $view->set('units', Item_Unit::select('uom'));
      $view->set('tax_itemtype', Tax_ItemType::select('tax_type_id'));
      $view->set('stock_type', Item_UI::type('mb_flag'));
      $view->set('sales_account', GL_UI::all('sales_account'));
      $view->set('inventory_account', GL_UI::all('inventory_account'));
      $view->set('cogs_account', GL_UI::all('cogs_account'));
      $view->set('adjustment_account', GL_UI::all('adjustment_account'));
      $view->set('assembly_account', GL_UI::all('assembly_account'));
      $this->JS->autocomplete('itemSearchId', 'Items.fetch', 'Item');
      if (!isset($_GET['stock_id'])) {
        $searchBox = UI::search(
          'itemSearchId',
          [
          'url'              => 'Item',
          'idField'          => 'stock_id',
          'name'             => 'itemSearchId', //
          'focus'            => true,
          'callback'         => 'Items.fetch'
          ],
          true
        );
        $view->set('searchBox', $searchBox);
        $id = 0;
        $this->JS->setFocus('itemSearchId');
      } else {
        $id = Item::getStockId($_GET['stock_id']);
      }
      $data = $this->getItemData($id);
      $view->set('firstPage', $this->Input->get('page', null, null));
      $view->render();
      $this->JS->tabs('tabs' . MenuUI::$menuCount, [], 0);
      $this->JS->onload("Items.onload($data);");
      Page::end(true);
    }
  }

