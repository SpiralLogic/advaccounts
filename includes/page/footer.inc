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
function page_footer($no_menu = false, $is_index = false, $hide_back_link = false)
{
	global $path_to_root, $js_lib, $Validate, $Editors, $Ajax;
	include_once($path_to_root . "/themes/" . user_theme() . "/renderer.php");
	$rend = new renderer();
	$rend->menu_footer($no_menu, $is_index);
	$edits = "editors = " . $Ajax->php2js($Editors) . ";";
	$Ajax->addScript('editors', $edits);
	echo "<script language=\"javascript\"  type=\"text/javascript\">
		_focus = '" . get_post('_focus') . "';
		_validate = " . $Ajax->php2js($Validate) . ";
		var $edits
	</script>";
	add_user_js_data();
	echo "\n<script language=\"javascript\"  type=\"text/javascript\"><!--\n";
	foreach ($js_lib as $text) {
		$text = str_replace(array('<script>', '</script>'), '', $text);
		echo $text;
	}
	//show_messages();
	echo "\n--></script>\n";
	$messages = new Messages();
	if ($messages->getNewUserMessages($_SESSION['wa_current_user']->user) > 0) {
		$messages->makeDialog();
	}
	echo "</body>";
    send_fscripts();
get_websales();
    echo    "</html>\n";
	if ($_SESSION["wa_current_user"]->user == 1) {
		FB::info($_SESSION);
	}
}