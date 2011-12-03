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
	$page_security = 'SA_MANUFTRANSVIEW';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::open_window(800, 500);
	Page::start(_($help_context = "View Work Order"), true);

	$woid = 0;
	if ($_GET['trans_no'] != "") {
		$woid = $_GET['trans_no'];
	}
	Display::heading($systypes_array[ST_WORKORDER] . " # " . $woid);
	Display::br(1);
	$myrow = WO_WorkOrder::get($woid);
	if ($myrow["type"] == WO_ADVANCED) {
		WO_Cost::display($woid, true);
	} else {
		WO_Cost::display_quick($woid, true);
	}
	echo "<div class='center'>";
	// display the WO requirements
	Display::br(1);
	if ($myrow["released"] == false) {
		Display::heading(_("BOM for item:") . " " . $myrow["StockItemName"]);
		Manufacturing::display_bom($myrow["stock_id"]);
	} else {
		Display::heading(_("Work Order Requirements"));
		WO_Requirements::display($woid, $myrow["units_reqd"]);
		if ($myrow["type"] == WO_ADVANCED) {
			echo "<br><table cellspacing=7><tr class='top'><td>";
			Display::heading(_("Issues"));
			WO_Issue::display($woid);
			echo "</td><td>";
			Display::heading(_("Productions"));
			WO_Produce::display($woid);
			echo "</td><td>";
			Display::heading(_("Additional Costs"));
			WO_Cost::display_payments($woid);
			echo "</td></tr></table>";
		} else {
			echo "<br><table cellspacing=7><tr class='top'><td>";
			Display::heading(_("Additional Costs"));
			WO_Cost::display_payments($woid);
			echo "</td></tr></table>";
		}
	}
	echo "<br></div>";
	Display::is_voided(ST_WORKORDER, $woid, _("This work order has been voided."));
	end_page(true);

?>
