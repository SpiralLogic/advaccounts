<?php
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

	include_once(APP_PATH . "gl/includes/gl_db.php");
	include_once(APP_PATH . "inventory/includes/inventory_db.php");
	include_once(APP_PATH . "purchasing/includes/purchasing_db.php");
	include_once(APP_PATH . "sales/includes/sales_db.php");

	include_once(APP_PATH . "includes/ui/ui_lists.php");
	include_once(APP_PATH . "includes/ui/ui_controls.php");
	include_once(APP_PATH . "includes/ui/ui_input.php");
	include_once(APP_PATH . "includes/session.php");