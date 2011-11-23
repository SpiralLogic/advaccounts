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
	if (!file_exists('config/config.php')) {
		header("Location: /install/index.php");
	}
	$page_security = 'SA_OPEN';
	require_once("bootstrap.php");
	$app = Session::get()->App;
	Extensions::add_access();
	if (Input::get('application')) {
		$app->selected_application = $_GET['application'];
	} else {
		$app->selected_application = Config::get('apps.default');
	}
	$app->display();