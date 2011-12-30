<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 12/4/10
	 * Time: 6:28 PM
	 * To change this template use File | Settings | File Templates.
	 */
	/**********************************************************************
	Copyright (C) Advanced Group PTY LTD
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	$page_security = 'SA_ITEM';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	$_SESSION['App']->selected_application = 'Items';
	if (AJAX_REFERRER) {
		if (isset($_GET['term'])) {
			$data = Item::search($_GET['term']);
		}
		elseif (isset ($_POST['id'])) {
			$data = new Item($_POST['id']);
		}
		 JS::renderJSON($data);
	}
	JS::footerFile("js/items.js");
	JS::footerFile('/js/js2/jquery.jeditable.js');
	JS::footerFile('/js/js2/jquery.dataTables.min.js');
	Page::start(_($help_context = "Items"), Input::request('frame'));
?>
<div id="dynamic" style="margin:0 auto;text-align:center;width:80%">
	<table width="100%" cellpadding="0" cellspacing="0" border="0" class="display" id="itemDetails">
		<thead>
		<tr>
			<th width="20%">Stock Id</th>
			<th>Description</th>
			<th>Long Description</th>
			<th width="5%">Price</th>
			<th width="5%">Last Cost</th>
			<th width="5%">Inactive</th>
			<th width="5%">No Sale</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td class="dataTables_empty">Loading data from server</td>
		</tr>
		</tbody>
		<tfoot>
		<tr>
			<th>Stock Id</th>
			<th>Description</th>
			<th>Long Description</th>
			<th width="5%">price</th>
			<th width="5%">Last Cost</th>
			<th width="5%">Inactive</th>
			<th width="5%">No Sale</th>

		</tr>
		</tfoot>
	</table>
</div>
<div class="spacer"></div>
<?

	Renderer::end_page(true, true);