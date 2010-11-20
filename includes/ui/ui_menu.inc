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
include_once($path_to_root . "/admin/db/voiding_db.inc");
include_once($path_to_root . "/includes/types.inc");

//--------------------------------------------------------------------------------------

class MenuUi {

	protected $tabs = array();
	protected $menu = '';
	protected $content = '';

	function __construct() {
		ob_start();
		$this->menu = "<div id='tabs'>";
	}

	function addTab($title, $tooltip = '', $link = '#') {
		$this->tabs[] = new MenuUi_item($title, $tooltip, $link);
	}

	function startTab($title, $tooltip) {
		$this->addTab($title, $tooltip,'#');
		echo '<div id="tabs-' . count($this->tabs) . '">';
	}

	function endTab() {
		echo '</div>';
	}

	function render() {
		global $js_lib;
		$this->menu.="<ul>";
		foreach ($this->tabs as $key => $tab) {
			$num=$key+1;
			$this->menu .= "<li><a title='{$tab->title}' href='#tabs-{$num}'><span>{$tab->title}</span></a></li>";
		}
		$this->menu .= "</ul>";
		 $this->content = ob_get_clean();
	     echo $this->menu . $this->content.'</div>';
	     $js_lib[] = '$("#tabs").tabs();';
	}
}


class MenuUi_item {
	public $content = '';
	public $link = '';
	public $title = '';
	public $tooltip = '';

	function __construct($title, $link, $tooltip = '') {
		$this->title = $title;
		$this->link = $link;
		$this->$tooltip = $tooltip;

	}


}