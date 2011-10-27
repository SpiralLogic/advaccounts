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
	function page_footer($no_menu = false, $is_index = false, $hide_back_link = false) {
		global $Editors;
		$Validate = array();
		$Ajax = Ajax::instance();
		include_once(APP_PATH . "themes/" . user_theme() . "/renderer.php");
		$rend = renderer::getInstance();
		$rend->menu_footer($no_menu, $is_index);
		$edits = "editors = " . $Ajax->php2js(set_editor(false, false)) . ";";
		$Ajax->addScript('editors', $edits);
		JS::beforeload("_focus = '" . get_post('_focus') . "';_validate = " . $Ajax->php2js($Validate) . ";var $edits");
		add_user_js_data();
		if ($rend->has_header) {
			Sidemenu::render();
		}
		Messages::showNewMessages();
		JS::render();

		if (AJAX_REFERRER) return;
		var_dump(convert(memory_get_usage(true)));
		echo "<br>";
		var_dump(convert(memory_get_peak_usage(true)));
		echo "<br>";
		var_dump(getReadableTime(microtime(true) - FUEL_START_TIME));
		echo "<br>";
		echo "<p></p>";

		echo "</div></body>";

		ui_view::get_websales();

		echo	 "</html>\n";
	}

	function convert($size) {
		$unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
		return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
	}

	function getReadableTime($time) {
		$ret = $time;
		$formatter = 0;
		$formats = array('ms', 's', 'm');
		if ($time >= 1000 && $time < 60000) {
			$formatter = 1;
			$ret = ($time / 1000);
		}
		if ($time >= 60000) {
			$formatter = 2;
			$ret = ($time / 1000) / 60;
		}
		$ret = number_format($ret, 3, '.', '') . ' ' . $formats[$formatter];
		return $ret;
	}
