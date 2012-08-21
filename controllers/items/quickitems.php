<?php
  use ADV\App\Item\Item;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  ADVAccounting::i()->set_selected('items');
  if (AJAX_REFERRER) {
    if (isset($_GET['term'])) {
      $data = Item::search($_GET['term']);
    } elseif (isset($_POST['id'])) {
      if (isset($_POST['name'])) {
        $item = new Item($_POST);
        $item->save($_POST);
      } else {
        $id   = Item::getStockId($_POST['id']);
        $item = new Item($id);
      }
      $data['item']        = $item;
      $data['stockLevels'] = $item->getStockLevels();
    }
    if (isset($_GET['page'])) {
      $data['page'] = $_GET['page'];
    }
    JS::_renderJSON($data);
  }
  JS::_footerFile("/js/quickitems.js");
  Page::start(_($help_context = "Items"), SA_CUSTOMER, isset($_GET['frame']));
  $view = new View('items/quickitems');
  $view->set('stock_cats', Item_Category::select('category_id'));
  $view->set('units', Item_Unit::select('uom'));
  $view->set('tax_itemtype', Tax_ItemType::select('tax_type_id'));
  $view->set('stock_type', Item_UI::type('mb_flag'));
  $view->set('sales_account', GL_UI::all('sales_account'));
  $view->set('inventory_account', GL_UI::all('inventory_account'));
  $view->set('cogs_account', GL_UI::all('cogs_account'));
  $view->set('adjustment_account', GL_UI::all('adjustment_account'));
  $view->set('assembly_account', GL_UI::all('assembly_account'));
  if (!isset($_GET['stock_id'])) {
    HTML::div('itemSearch', array('class' => 'bold pad10 center'));
    Item::addSearchBox('itemSearchId', array(
                                            'label'    => 'Item:', 'size' => '50px', 'selectjs' => '$("#itemSearchId").val(value.item_name);Items.fetch(value.stock_id);return false;'
                                       ));
    HTML::div();
    $id = 0;
  } else {
    $id = Item::getStockId($_GET['stock_id']);
  }
  $data['item']        = $item = new Item($id);
  $data['stockLevels'] = $item->getStockLevels();
  $data                = json_encode($data, JSON_NUMERIC_CHECK);
  $view->set('firstPage', Input::_get('page', null, null));
  $view->render();
  JS::_tabs('tabs' . MenuUI::$menuCount, [], 1);
  JS::_onload("Items.onload($data);");
  Page::end(true);
