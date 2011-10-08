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
	class MenuUI {

		protected $tabs = array();
		protected $menu = '';
		protected $content = '';
		protected $options = array();
		public $firstPage = false;
		static $menuCount = 0;

		function __construct($options = array()) {
			$this->options = $options;
			ob_start();
			$this->menu = "<div class='tabs' id='tabs" . MenuUI::$menuCount . "'>";
		}

		function addTab($title, $tooltip = '', $link = '#') {
			$this->tabs[] = new MenuUi_item($title, $tooltip, $link);
			return $this;
		}

		function startTab($title, $tooltip, $link = '#', $style = '') {
			$this->addTab($title, $tooltip, $link);
			echo '<div id="tabs' . MenuUI::$menuCount . '-' . count($this->tabs) . '" ';
			if (!empty($style)) echo ' style="' . $style . '" ';
			echo '>';
			return $this;
		}

		function endTab() {
			echo '</div>';
			return $this;
		}

		function render() {
			$this->menu .= "<ul>";
			foreach ($this->tabs as $key => $tab) {
				$num = $key + 1;
				$this->menu .= "<li><a title='{$tab->title}'";
				$this->menu .= ($tab->link != "#") ? ' href="' . $tab->link . '" ' : " href='#tabs" . MenuUI::$menuCount . "-{$num}'";
				$this->menu .= "><span>{$tab->title}</span></a></li>";
			}
			$this->menu .= "</ul>";
			$this->content = ob_get_clean();
			echo $this->menu . $this->content . '</div>';
			JS::tabs('#tabs' . MenuUI::$menuCount, $this->options, $this->firstPage);
			MenuUI::$menuCount++;
		}
	}


	class MenuUi_item {
		public $content = '';
		public $link = '';
		public $title = '';
		public $tooltip = '';

		function __construct($title, $tooltip = '', $link = '') {
			$this->title = $title;
			$this->link = $link;
			$this->tooltip = $tooltip;
		}
	}
