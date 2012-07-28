<?php
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
    JS::renderJSON($data, JSON_NUMERIC_CHECK);
  }
  JS::footerFile("/js/quickitems.js");
  Page::start(_($help_context = "Items"), SA_CUSTOMER, isset($_GET['frame']));
  $view               = new View('items/quickitems');
  $view->set('stock_cats',Item_Category::select('category_id'));
  $view->set('units',Item_Unit::select('uom'));
  $tax_itemtype       = Tax_ItemType::select('tax_type_id');
  $stock_type         = Item_UI::type('mb_flag');
  $sales_account      = GL_UI::all('sales_account');
  $inventory_account  = GL_UI::all('inventory_account');
  $cogs_account       = GL_UI::all('cogs_account');
  $adjustment_account = GL_UI::all('adjustment_account');
  $assembly_account   = GL_UI::all('assembly_account');
  if (!isset($_GET['stock_id'])) {
    HTML::div('itemSearch', array('class' => 'bold pad10 center'));
    Item::addSearchBox('itemSearchId', array(
      'label'    => 'Item:', 'size' => '50px',
      'selectjs' => '$("#itemSearchId").val("");Items.fetch(value.stock_id);return false;'
    ));
    HTML::div();
    $id = 0;
  } else {
    $id = Item::getStockId($_GET['stock_id']);
  }
  $data['item']        = $item = new Item($id);
  $data['stockLevels'] = $item->getStockLevels();
  $data                = json_encode($data, JSON_NUMERIC_CHECK);
  JS::onload("Items.onload($data);");
  $menu = new MenuUI();
    $menu->firstPage = Input::get('page',null,null);
  $menu->startTab("Items", "Items");
  $view->render();
  $menu->endTab();
  $menu->startTab("Accounts", "Accounts");
  echo <<<HTML
	<div id="Accounts" class="left formbox">
	<label for="tax_type_id"><span>Item Tax Type:</span>$tax_itemtype</label>
		<label for="mb_flag"><span>Item Type:</span>$stock_type</label>
	{{if sales_account}}	<label for="sales_account"><span>Sales Account:</span>$sales_account</label>{{/if}}
	{{if inventory_account}}		<label for="inventory_account"><span>Inventory Account:</span>$inventory_account</label>{{/if}}
	<label for="cogs_account"><span>COGS Account:</span>$cogs_account</label>
	{{if adjustment_account}} <label for="adjustment_account"><span>Adjustments&nbsp;Account:</span>$adjustment_account</label> {{/if}}
	{{if assembly_account}} <label for="assembly_account"><span>Assembly Account:</span>$assembly_account</label>{{/if}}</div>
HTML;
  $menu->endTab();
  $menu->startTab("Selling", "Sales Prices");
  echo "<iframe id='sellFrame' data-src='" . BASE_URL . "inventory/prices.php?frame=1&stock_id=" . $item->stock_id . "' style='width:95%' height='500' frameborder='0'></iframe> ";
  $menu->endTab();
  $menu->startTab("Purchasing", "Purchasing Prices");
  echo "<iframe id='buyFrame' data-src='" . BASE_URL . "inventory/purchasing_data.php?frame=1&stock_id=" . $item->stock_id . "' style='width:100%' height='500' frameborder='0'></iframe> ";
  $menu->endTab();
  $menu->startTab("Locations", "Stock Locations");
  echo "<iframe id='locationFrame' data-src='" . BASE_URL . "inventory/reorder_level.php?frame=1&stock_id=" . $item->stock_id . "' style='width:100%' height='500' frameborder='0'></iframe> ";
  $menu->endTab();
  $menu->startTab("Website", "Website page for product");
  echo "<iframe id='webFrame' data-srcpre='" . Config::get('modules.webstore')['product_url'] . "' data-srcpost='" . Config::get('modules.webstore')['url_extension'] . "'
	style='width:100%'
	height='500' frameborder='0'></iframe> ";
  $menu->endTab();
  $menu->render();
  UI::button('btnCancel', 'Cancel', array("style" => "display:none"));
  UI::button('btnSave', 'Save', array("style" => "display:none"));
  Page::end(true);
