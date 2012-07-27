<div id="Items" class="left formbox" style='display:none'>
  <input type="hidden" value="${id}" id="id">
  <label for="stock_id"><span>Code:</span><input name="stock_id" id="stock_id" type="text" value="${stock_id}" maxlength="20"></label>
  <label for="name"><span>Name:</span><input id="name" type="text" value="${name}" maxlength="200"></label>
  <label for="long_description"><span>Description:</span><textarea id="long_description" rows="6" cols="36">${long_description} </textarea></label>
  <label for="no_sale"><span>Not for sale:</span><input id="no_sale" value=1 type="checkbox"/></label>
  <label for="inactive"><span>inactive:</span><input id="inactive" value=1 type="checkbox"/></label>

  <label for="category_id"><span>Category:</span>{{$stock_cats}}</label>
  <label for="uom"><span>Units of Mesasure:</span>{{$units}}</label>
</div>
<div class="center">
  <table id="stockLevels" class="marginauto grid width80" style="display:none">
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
    <tbody>
    <script id="stockRow" type="text/x-jquery-tmpl">
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
    </script>
    </tbody>
  </table>
</div>
