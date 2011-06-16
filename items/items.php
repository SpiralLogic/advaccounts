<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 12/4/10
 * Time: 6:28 PM
 * To change this template use File | Settings | File Templates.
 */
   /**********************************************************************
   Copyright (C) FrontAccounting, LLC.
   Released under the terms of the GNU General Public License, GPL,
   as published by the Free Software Foundation, either version 3
   of the License, or (at your option) any later version.
   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
   See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
    ***********************************************************************/
   $page_security = 'SA_ITEM';
   $path_to_root = "..";
   include_once("includes/items.inc");
   if (isAjaxReferrer()) {
      if (isset($_GET['term'])) {
         $sql = "SELECT stock_id as id, description as label, description as value FROM " . TB_PREF . "stock_master " . "where stock_id LIKE '%" . $_GET['term'] . "%' OR description LIKE '%" . $_GET['term'] . "%' LIMIT 200";
         $result = db_query($sql, 'Couldn\'t Get Items');
         while ($row = db_fetch_assoc($result)) {
            $data[] = $row;
         }
      }
      elseif (isset ($_POST['id'])) {
         $data = new Item($_POST['id']);
         $data = $data->data;

      }
      echo json_encode($data);
      exit;
   }
   add_js_ffile("includes/js/items.js");
   $js_fstatic[] = 'jquery.jeditable.js';
   $js_fstatic[] = 'jquery.dataTables.min.js';

   page(_($help_context = "Items"), @$_REQUEST['popup']);
 ?>			<div id="dynamic"  style="margin:0 auto;text-align:center;width:80%">
<table width="100%" cellpadding="0" cellspacing="0" border="0" class="display" id="itemDetails">
	<thead>
		<tr>
			<th width="20%">Stock Id</th>
			<th >Description</th>
         <th >Long Description</th>
         <th width="5%">Price</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td class="dataTables_empty">Loading data from server</td>
		</tr>
	</tbody>
	<tfoot>
		<tr>
	<th >Stock Id</th>
			<th >Description</th>
            <th >Long Description</th>
         <th width="5%">price</th>
		</tr>
	</tfoot>
</table>
			</div>
			<div class="spacer"></div>
<?

   end_page(true, true);