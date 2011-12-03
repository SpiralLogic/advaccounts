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
	class MenuUI extends Menu
	{
		/**
		 * @var array
		 */
		protected $options = array();
		/**
		 * @var bool
		 */
		public $firstPage = false;
		/**
		 * @var int
		 */
		static $menuCount = 0;

		/**
		 * @param array $options
		 */
		function __construct($options = array()) {
			$this->options = $options;
			ob_start();
		}

		/**
		 * @param        $title
		 * @param string $tooltip
		 * @param string $link
		 *
		 * @return MenuUI
		 */function addTab($title, $tooltip = '', $link = '#') {
			$this->items[] = new MenuUi_item($title, $tooltip, $link);
			return $this;
	}

		/**
		 * @param        $title
		 * @param        $tooltip
		 * @param string $link
		 * @param string $style
		 * @return MenuUI
		 */function startTab($title, $tooltip, $link = '#', $style = '') {
			$this->addTab($title, $tooltip, $link);
			echo '<div id="tabs' . MenuUI::$menuCount . '-' . count($this->items) . '" ';
			if (!empty($style)) {
				echo ' style="' . $style . '" ';
			}
			echo '>';
			return $this;
	}

		/**
		 * @return MenuUI
		 */function endTab() {
			echo '</div>';
			return $this;
	}

		/**
		 *
		 */function render() {
			$menu = "<div class='tabs' id='tabs" . MenuUI::$menuCount . "'><ul>";
			foreach ($this->items as $key => $item) {
				$num = $key + 1;
				$menu .= "<li><a title='{$item->label}'";
				$menu .= ($item->link != "#") ? ' href="' . $item->link . '" ' : " href='#tabs" . MenuUI::$menuCount . "-{$num}'";
				$menu .= "><span>{$item->label}</span></a></li>";
			}
			$menu .= "</ul>";
			$content = ob_get_clean();
			echo $menu . $content . '</div>';
			JS::tabs('#tabs' . MenuUI::$menuCount, $this->options, $this->firstPage);
			MenuUI::$menuCount++;
		}
	}

	/**
	 *
	 */class MenuUi_item extends menu_item
{
	/**
	 * @var string
	 */public $tooltip = '';

	/**
	 * @param        $label
	 * @param string $tooltip
	 * @param string $link
	 */function __construct($label, $tooltip = '', $link = '') {
			$this->label = $label;
			$this->link = $link;
			$this->tooltip = $tooltip;
		}
	}
