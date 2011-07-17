<?php

   $page_security = 'SA_CUSTOMER';
   $path_to_root = "..";
   include_once("includes/items.inc");
   if (AJAX_REFERRER) {
      echo json_encode($data);
      exit();
   }
   add_js_ffile("/js/js2/jquery-tmpl.min.js");
   add_js_ffile("includes/js/quickitems.js");


   page(_($help_context = "Customers"), true);
   HTML::div('itemSearch');
   UI::search('item', array(
                           'label' => 'Search Item', 'size' => 80, 'url' => 'search.php',
                           'callback' => 'Items.fetch'));
HTML::div();

   echo <<<HTML
<div id="Items" class="aligncenter">
<table class="marginauto ">
<tr><td><label for="code">Item Code:</label></td><td><input id="code" type="text" value="\${id}" maxlength="10"></td></tr>
<tr><td><label for="name">Item Name:</label><br></td><td><input id="name" type="text" value="\${name}" maxlength="10"></td></tr>
<tr><td><label for="description">Item Description:</label></td><td><textarea id="description" rows="6" cols="36">\${description} </textarea></td></tr>

<tr><td><label for="uom">Item Units of Mesasure:</label><br></td><td><input id="uom" type="text" value="\${uom}" maxlength="10"></td></tr>
</table>
</div>
HTML;
stock_categories_list_row('Category: ','category_id');

   end_page(true, true);