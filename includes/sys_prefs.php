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
	include_once(APP_PATH . "admin/db/company_db.inc");

	class sys_prefs {

		function allow_negative_stock() {
			return get_company_pref('allow_negative_stock');
		}

		/* Sherifoz 26.06.03 Proportion by which items can be received over the quantity that is specified in a purchase
		invoice
		The figure entered is interpreted as a percentage ie 10 means 0.1 or 10% not 10 times
		*/
		function over_receive_allowance() {
			return get_company_pref('po_over_receive');
		}

		/* Proportion by which a purchase invoice line is an overcharge for a purchase order item received
		is an overcharge. If the overcharge is more than this percentage then an error is reported and
		purchase invoice line cannot be entered
		The figure entered is interpreted as a percentage ie 20 means 0.2 or 20% not 20 times
		*/
		function over_charge_allowance() {
			return get_company_pref('po_over_charge');
		}

		function default_credit_limit() {
			return get_company_pref('default_credit_limit');
		}

		function default_wo_required_by() {
			return get_company_pref('default_workorder_required');
		}

		function default_delivery_required_by() {
			return get_company_pref('default_delivery_required');
		}

		function default_dimension_required_by() {
			return get_company_pref('default_dim_required');
		}

		function allocation_settled_allowance() {
			return Config::get('accounts.allocation_allowance');
		}
	}

?>