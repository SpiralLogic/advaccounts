<?php
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
  require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");

  Page::start(_($help_context = "Costed Bill Of Material Inquiry"), SA_WORKORDERCOST);
  Validation::check(Validation::BOM_ITEMS, _("There are no manufactured or kit items defined in the system."), STOCK_MANUFACTURE);
  if (isset($_GET['stock_id'])) {
    $_POST['stock_id'] = $_GET['stock_id'];
  }
  if (list_updated('stock_id')) {
    Ajax::i()->activate('_page_body');
  }
  start_form(FALSE);
  start_table('tablestyle_noborder');
  Item_UI::manufactured_row(_("Select a manufacturable item:"), 'stock_id', NULL, FALSE, TRUE);
  end_table();
  Display::br();
  Display::heading(_("All Costs Are In:") . " " . Bank_Currency::for_company());
  WO::display_bom(Input::post('stock_id'));
  end_form();
  Page::end();
?>
