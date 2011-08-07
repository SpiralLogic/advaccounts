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
      global $path_to_root, $Validate, $Editors, $Ajax;
      include_once($path_to_root . "/themes/" . user_theme() . "/renderer.php");
      $rend = new renderer();
      $rend->menu_footer($no_menu, $is_index);
      $edits = "editors = " . $Ajax->php2js($Editors) . ";";
      $Ajax->addScript('editors', $edits);
      JS::beforeload("_focus = '" . get_post('_focus') . "';_validate = " . $Ajax->php2js($Validate) . ";var $edits");
      add_user_js_data();
      Messages::showNewMessages();
      return JS::render();
      if (AJAX_REFERRER) return;

      global $start22;
      $time = microtime();
      $time = explode(' ', $time);
      $time = $time[1] + $time[0];
      $finish = $time;
      $total_time = getReadableTime($finish - $start22);
      $generateinfo = 'Memory Usage: ' . convert(memory_get_usage()) . "<br>\n" .
                      'Memory Peak Usage: ' . convert(memory_get_peak_usage()) . "<br>" . 'Page generated in ' . $total_time . ' seconds.' . "\n";
      HTML::div('generateinfo', $generateinfo, false);
      echo "</div></body>";
      get_websales();
      echo   "</html>\n";

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