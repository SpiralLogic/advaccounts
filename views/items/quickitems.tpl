<div id="itemSearch" class='bold pad10 center'>
{{$searchBox}}
</div>
<form>
    {#$menu->startTab('Items', 'Items')#}
    <div id="Items" class="width40 formbox" style='display:none'>
        <input type="hidden" value="${id}" name="id" id="id">
        <label for="stock_id"><span>Code:</span><input name="stock_id" id="stock_id" type="text" value="${stock_id}" maxlength="20"></label>
        <label for="description"><span>Name:</span><input id="name" name="description" type="text" value="${description}" maxlength="200"></label>
        <label for="long_description"><span>Description:</span><textarea name="long_description" id="long_description" rows="6" cols="36">${long_description}</textarea></label>
        <label for="no_sale"><span>Not for sale:</span><input id="no_sale" name="no_sale" type="checkbox"/></label>
        <label for="inactive"><span>inactive:</span><input id="inactive" name="inactive" type="checkbox"/></label>
        <label for="category_id"><span>Category:</span>{{$stock_cats}}</label>
        <label for="uom"><span>Units of Mesasure:</span>{{$units}}</label>
    </div>
    <div class="center">
        <table id="stockLevels" class="marginauto grid width80" style="display:none">
            <thead>
            <tr>
                <th>Location</th>
                <th>QOH</th>
                <th>Shelf Primary</th>
                <th>Shelf Secondary</th>
                <th>Reorder Level</th>
                <th>On SalesOrder</th>
                <th>Available</th>
                <th>On PurchaseOrder</th>
            </tr>
            </thead>
            <tbody id="stockRow">
            <tr>
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
    {#$menu->endTab()->startTab('Accounts', 'Accounts')#}
    <div id="Accounts" class="formbox width40">
        <label for="tax_type_id"><span>Item Tax Type:</span>{{$tax_itemtype}}</label>
        <label for="mb_flag"><span>Item Type:</span>{{$stock_type}}</label>
    {{$sales_account?}} <label for="sales_account"><span>Sales Account:</span>{{$sales_account}}</label>{{/$sales_account?}}
    {{$inventory_account?}} <label for="inventory_account"><span>Inventory Account:</span>{{$inventory_account}}</label>{{/$inventory_account?}}
        <label for="cogs_account"><span>COGS Account:</span>{{$cogs_account}}</label>
    {{$adjustment_account?}} <label for="adjustment_account"><span>Adjustments&nbsp;Account:</span>{{$adjustment_account}}</label> {{/$adjustment_account?}}
    {{$assembly_account?}} <label for="assembly_account"><span>Assembly Account:</span>{{$assembly_account}}</label>{{/$assembly_account?}}</div>
    {#$menu->endTab()->startTab('Selling', 'Selling')#}
    <iframe id='sellFrame' data-src='{{ROOT_URL}}items/manage/prices?frame=1&stock_id={{$item->stock_id}}' style='width:95%' height='500' frameborder='0'></iframe>
    {#$menu->endTab()->startTab('Purchasing', 'Purchasing')#}
    <iframe id='buyFrame' data-src='{{ROOT_URL}}inventory/purchasing_data.php?frame=1&stock_id={{$item->stock_id}}' style='width:100%' height='500' frameborder='0'></iframe>
    {#$menu->endTab()->startTab('Locations', 'Locations')#}
    <iframe id='locationFrame' data-src='{{ROOT_URL}}inventory/reorder_level.php?frame=1&stock_id={{$item->stock_id}}' style='width:100%' height='500' frameborder='0'></iframe>
    {#$menu->endTab()->startTab('Website', 'Website')#}
    <iframe id='webFrame' data-srcpre='{{Config::_get('modules.webstore')['product_url']}}' data-srcpost='{{Config::_get('modules.webstore')['url_extension']}}'
            style='width:100%'
            height='500' frameborder='0'></iframe>
    {#$menu->endTab()->render()#}
</form>
<div class="center">
    <button id="btnNew" name="new" type="submit" class="btn btn-primary">New</button>
    <button id="btnCancel" name="cancel" type="submit" class="btn btn-danger ui-helper-hidden">Cancel</button>
    <button id="btnConfirm" name="save" type="submit" class="btn btn-success ui-helper-hidden">New Customer</button>
</div>
