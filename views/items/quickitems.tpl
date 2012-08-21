<div class='width90 center ui-tabs ui-widget ui-widget-content ui-corner-all tabs' id='tabs0'>
  <ul class='ui-tabs-nav
            ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all'>
    <li class='ui-state-default ui-corner-top'>
      <a title='Items' href='#tabs0-0'><span>Items</span></a>
    </li>
    <li class='ui-state-default ui-corner-top'>
      <a title='Accounts' href='#tabs0-1'><span>Accounts</span></a>
    </li>
    <li class='ui-state-default ui-corner-top'>
      <a title='Selling' href='#tabs0-2'><span>Selling</span></a>
    </li>
    <li class='ui-state-default ui-corner-top'>
      <a title='Purchasing' href='#tabs0-3'><span>Purchasing</span></a>
    </li>
    <li class='ui-state-default ui-corner-top'>
      <a title='Locations' href='#tabs0-4'><span>Locations</span></a>
    </li>
    <li class='ui-state-default ui-corner-top hidden'>
      <a title='Website' href='#tabs0-5'><span>Website</span></a>
    </li>
  </ul>
  <div id="tabs0-0">
    <div id="Items" class="width40 formbox" style='display:none'>
      <input type="hidden" value="${id}"  name="id" id="id">
      <label for="stock_id"><span>Code:</span><input name="stock_id" id="stock_id" type="text" value="${stock_id}" maxlength="20"></label>
      <label for="description"><span>Name:</span><input id="name" name="description" type="text" value="${description}" maxlength="200"></label>
      <label for="long_description"><span>Description:</span><textarea name="long_description"  id="long_description" rows="6" cols="36">${long_description}</textarea></label>
      <label for="no_sale"><span>Not for sale:</span><input id="no_sale" name="no_sale" type="checkbox"/></label>
      <label for="inactive"><span>inactive:</span><input id="inactive" name="inactive" type="checkbox"/></label>
      <label for="category_id"><span>Category:</span>{{$stock_cats}}</label>
      <label for="uom"><span>Units of Mesasure:</span>{{$units}}</label>
    </div>
    <div class="center">
      <table id="stockLevels" class="marginauto grid width80" style="display:none">
        <thead><tr>
          <th>Location</th>
          <th>QOH</th>
          <th>Shelf Primary</th>
          <th>Shelf Secondary</th>
          <th>Reorder Level</th>
          <th>On SalesOrder</th>
          <th>Available</th>
          <th>On PurchaseOrder</th>
        </tr>
        </thead><tbody id="stockRow">
        <tr >
            <td>${location_name}</td>
            <td>${qty}</td>
            <td>${shelf_primary}</td>
            <td>${shelf_secondary}</td>
            <td>${reorder_level}</td>
            <td>${demand}</td>
            <td>${available}</td>
            <td>${onorder}</td>
        </tr>
        </tbody>
      </table>
    </div>
  </div>
  <div id="tabs0-1" class="ui-tabs-hide">
    <div id="Accounts" class="formbox width40">
      <label for="tax_type_id"><span>Item Tax Type:</span>{{$tax_itemtype}}</label>
      <label for="mb_flag"><span>Item Type:</span>{{$stock_type}}</label>
      {{$sales_account?}} <label for="sales_account"><span>Sales Account:</span>{{$sales_account}}</label>{{/$sales_account?}}
      {{$inventory_account?}} <label for="inventory_account"><span>Inventory Account:</span>{{$inventory_account}}</label>{{/$inventory_account?}}
      <label for="cogs_account"><span>COGS Account:</span>{{$cogs_account}}</label>
      {{$adjustment_account?}} <label for="adjustment_account"><span>Adjustments&nbsp;Account:</span>{{$adjustment_account}}</label> {{/$adjustment_account?}}
      {{$assembly_account?}} <label for="assembly_account"><span>Assembly Account:</span>{{$assembly_account}}</label>{{/$assembly_account?}}</div>

  </div>
  <div id="tabs0-2" class="ui-tabs-hide">
    <iframe id='sellFrame' data-src='{{BASE_URL}}inventory/prices.php?frame=1&stock_id={{$item->stock_id}}' style='width:95%' height='500' frameborder='0'></iframe>
  </div>
  <div id="tabs0-3" class="ui-tabs-hide">
    <iframe id='buyFrame' data-src='{{BASE_URL}}inventory/purchasing_data.php?frame=1&stock_id={{$item->stock_id}}' style='width:100%' height='500' frameborder='0'></iframe>
  </div>
  <div id="tabs0-4" class="ui-tabs-hide">

    <iframe id='locationFrame' data-src='{{BASE_URL}}inventory/reorder_level.php?frame=1&stock_id={{$item->stock_id}}' style='width:100%' height='500' frameborder='0'></iframe>
  </div>
  <div id="tabs0-5" class="ui-tabs-hide hidden">

    <iframe id='webFrame' data-srcpre='{{Config::_get('modules.webstore')['product_url']}}' data-srcpost='{{Config::_get('modules.webstore')['url_extension']}}'
            style='width:100%'
            height='500' frameborder='0'></iframe>
  </div><button name='btnCancel' id='btnCancel' >Cancel</button>
  <button name='btnSave' id='btnSave' >Save</button>

</div>
