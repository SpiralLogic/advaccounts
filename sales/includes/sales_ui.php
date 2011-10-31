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
	//----------------------------------------------------------------------------
	// helper functions for script execution control
	//
	function processing_start()
	{
		processing_end();
		$_SESSION['Processing'] = $_SERVER['PHP_SELF'];
	}

	function processing_end()
	{
		unset($_SESSION['Processing']);
		if (isset($_SESSION['Items'])) {
			unset($_SESSION['Items']->line_items);
			unset($_SESSION['Items']);
		}
	}

	function processing_active()
	{
		return (isset($_SESSION['Processing']) && $_SESSION['Processing'] == $_SERVER['PHP_SELF']);
	}

	/*
			 Check if the cart was not destroyed during opening the edition page in
			 another browser tab.
		 */
	function check_edit_conflicts($cartname = 'Items')
	{
		$Ajax = Ajax::instance();
		if (Input::post('cart_id') && Input::post('cart_id') != $_SESSION[$cartname]->cart_id) {
			ui_msgs::display_error(_('This edit session has been abandoned by opening sales document in another browser tab. You cannot edit more than one sales document at once.'));
			$Ajax->activate('_page_body');
			ui_view::display_footer_exit();
		}
	}

?>