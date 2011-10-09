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

	include_once(APP_PATH . "admin/db/security_db.inc");

	include_once(APP_PATH . "gl/includes/gl_db.inc");
	//	include_once(APP_PATH . "includes/banking.inc");
	include_once(APP_PATH . "includes/ui/ui_lists.inc");
	include_once(APP_PATH . "includes/ui/ui_controls.inc");
	include_once(APP_PATH . "includes/ui/ui_input.inc");

	include_once(APP_PATH . "includes/db/inventory_db.inc");

	include_once(APP_PATH . "sales/includes/sales_db.inc");
	include_once(APP_PATH . "purchasing/includes/purchasing_db.inc");
	include_once(APP_PATH . "inventory/includes/inventory_db.inc");
	include_once(APP_PATH . "manufacturing/includes/manufacturing_db.inc");

	include_once(APP_PATH . "admin/db/voiding_db.inc");
	//	include_once(APP_PATH . "includes/ui/ui_view.inc");
	include_once(APP_PATH . "includes/data_checks.inc");
	//include_once(APP_PATH . "includes/dates.inc");

	include_once(APP_PATH . "includes/db/connect_db.inc");

	include_once(APP_PATH . "includes/systypes.inc");
	//include_once(APP_PATH . "includes/references.inc");
	include_once(APP_PATH . "includes/db/comments_db.inc");
	include_once(APP_PATH . "includes/db/sql_functions.inc");
	include_once(APP_PATH . "includes/db/audit_trail_db.inc");
	include_once(APP_PATH . "admin/db/users_db.inc");
	include_once(APP_PATH . "admin/db/company_db.inc");
	include_once(APP_PATH . "includes/db/references_db.inc");
	include_once(APP_PATH . "includes/session.inc");

