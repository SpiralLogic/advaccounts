<?php
  namespace ADV\Controllers\Items\Manage;

  use ADV\App\Item\Item;
  use ADV\App\Item\Reorder;
  use ADV\App\Item\Purchase;
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
    protected $stock_id = 0;
    protected $stockid = 0;
    /** @var \ADV\App\Item\Item */
    protected $item;
    protected $formid;
    protected $security = SA_CUSTOMER;
    protected function before() {
      $this->formid   = $this->Input->getPostGlobal('_form_id');
      $this->stock_id = & $this->Input->getPostGlobal('stock_id');
      $this->stockid  = & $this->Input->getPostGlobal('stockid');
      if (!$this->stockid) {
        $this->stockid = Item::getStockID($this->stock_id);
      }
      $this->item     = new Item($this->stockid);
      $this->stock_id = $this->item->stock_id;
      $this->runPost();
      $this->JS->footerFile("/js/quickitems.js");
      $this->setTitle("Items");
    }
    /**
     * @internal param $id
     * @return string
     */
    protected function getItemData() {
      $data['item']          = $this->item;
      $data['stockLevels']   = $this->item->getStockLevels();
      $data['sellprices']    = $this->embed('Items\\Manage\\Prices');
      $data['buyprices']     = $this->embed('Items\\Manage\\Purchasing');
      $data['reorderlevels'] = $this->embed('Items\\Manage\\Reorders');
      return $data;
    }
    protected function runPost() {
      $data = [];
      if (REQUEST_POST && $this->formid == 'item_form') {
        switch ($this->action) {
          case SAVE:
            $this->item->save($_POST);
            $data['status'] = $this->item->getStatus();
            break;
        }
        if (isset($_GET['page'])) {
          $data['page'] = $_GET['page'];
        }
      }
      if (REQUEST_POST) {
        $this->JS->renderJSON($this->getItemData());
      }
    }
    protected function index() {
      $view = new View('items/quickitems');
      $menu = new MenuUI('disabled');
      $view->set('menu', $menu);
      $form = new Form();
      $form->start('item','/Items/Manage/Items');
      $form->group('items');
      $form->hidden('stockid');
      $form->text('stock_id')->label('Item Code:');
      $form->text('description')->label('Item Name:');
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
      $form->button('_action', ADD, ADD)->type('primary')->id('btnNew')->mergeAttr(['form' => 'item_form']);
      $form->button('_action', CANCEL, CANCEL)->type('danger')->preIcon(ICON_CANCEL)->id('btnCancel')->hide()->mergeAttr(['form' => 'item_form']);
      $form->button('_action', SAVE, SAVE)->type('success')->preIcon(ICON_SAVE)->id('btnConfirm')->hide()->mergeAttr(['form' => 'item_form']);
      $view->set('form', $form);
      $this->JS->autocomplete('itemSearchId', 'Items.fetch', 'Item');
      if (!$this->Input->hasGet('stock_id')) {
        $searchBox = UI::search(
          'itemSearchId', [
                          'url'      => 'Item',
                          'idField'  => 'stock_id',
                          'name'     => 'itemSearchId', //
                          'focus'    => true,
                          'callback' => 'Items.fetch'
                          ], true
        );
        $view->set('searchBox', $searchBox);
        $this->JS->setFocus('itemSearchId');
      }
      $data = $this->getItemData();
      $view->set('sellprices', $data['sellprices']);
      $view->set('buyprices', $data['buyprices']);
      $view->set('reorderlevels', $data['reorderlevels']);
      $view->set('firstPage', $this->Input->get('page'));
      $view->render();
      $this->JS->tabs('tabs' . MenuUI::$menuCount, [], 0);
      $this->JS->onload("Items.onload(" . json_encode($data) . ");");
    }
    /**
     * @return \ADV\App\Pager\Edit
     */
    protected function generateSellPrices() {
      $price           = new Price();
      $price->stock_id = $this->stock_id;
      $price->stockid  = $this->stockid;
      $price_pager     = \ADV\App\Pager\Edit::newPager('sellprices', $price->getPagerColumns());
      $price_pager->setObject($price);
      $price_pager->editing->stock_id = $this->stock_id;
      $price_pager->editing->stockid  = $this->stockid;
      $price_pager->setData(Item_Price::getAll($this->stock_id));
      return $price_pager;
    }
    /**
     * @return \ADV\App\Pager\Edit
     */
    protected function generateBuyPrices() {
      $price           = new Purchase();
      $price->stock_id = $this->stock_id;
      $price->stockid  = $this->stockid;
      $price_pager     = \ADV\App\Pager\Edit::newPager('buyprices', $price->getPagerColumns());
      $price_pager->setObject($price);
      $price_pager->editing->stock_id = $this->stock_id;
      $price_pager->editing->stockid  = $this->stockid;
      $price_pager->setData(Purchase::getAll($this->stock_id));
      return $price_pager;
    }
    /**
     * @return \ADV\App\Pager\Pager
     */
    protected function generateReorderLevels() {
      $reorder       = new Reorder();
      $reorder_pager = \ADV\App\Pager\Pager::newPager('reorderlevels', $reorder->getPagerColumns());
      $reorder_pager->setData($reorder->getAll($this->stockid));
      return $reorder_pager;
    }
  }

