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
	class Apps_Dimensions extends Application {
		function __construct() {
			global $installed_extensions;
			$dim = DB_Company::get_pref('use_dimension');
			parent::__construct("Dimensions", _($this->help_context = "&Dimensions"), $dim);

			if ($dim > 0) {
				$this->add_module(_("Transactions"));
				$this->add_lapp_function(0, _("Dimension &Entry"),
					"dimensions/dimension_entry.php?", 'SA_DIMENSION');
				$this->add_lapp_function(0, _("&Outstanding Dimensions"),
					"dimensions/inquiry/search_dimensions.php?outstanding_only=1", 'SA_DIMTRANSVIEW');

				$this->add_module(_("Inquiries and Reports"));
				$this->add_lapp_function(1, _("Dimension &Inquiry"),
					"dimensions/inquiry/search_dimensions.php?", 'SA_DIMTRANSVIEW');

				$this->add_rapp_function(1, _("Dimension &Reports"),
					"reporting/reports_main.php?Class=4", 'SA_DIMENSIONREP');

				$this->add_module(_("Maintenance"));
				$this->add_lapp_function(2, _("Dimension &Tags"),
					"system/tags.php?type=dimension", 'SA_DIMTAGS');

				if (count($installed_extensions) > 0) {
					foreach ($installed_extensions as $mod)
					{
						if (@$mod['active'] && $mod['type'] == 'plugin' && $mod["tab"] == "dimensions") {
							$this->add_rapp_function(2, $mod["title"],
							 "modules/" . $mod["path"] . "/" . $mod["filename"] . "?",
								isset($mod["access"]) ? $mod["access"] : 'SA_OPEN');
						}
					}
				}
			}
		}
	}

?>