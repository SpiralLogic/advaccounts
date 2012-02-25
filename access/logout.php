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
	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
	Page::start(_("Logout"), SA_OPEN, true, false, '');
	echo "<table style='width:100%' > <tr><td class='center'><img src='/themes/default/images/logo_advaccounts.png' alt='ADVAccounts' style='width:250px; height:50px' /></td>
 </tr>
 <tr>
 <td>&nbsp;</td>
 </tr>
 <tr>
 <td><div class='center'><span class='apptitle'>";
	echo _("Thank you for using") . " ";
	echo "<strong>" . APP_TITLE . ' ' . VERSION . "</strong>";
	echo "</span></div></td>
 </tr>
 <tr>
 <td>&nbsp;</td>
 </tr>
 <tr>
 <td><div class='center'>";
	echo "<a class='bold' href='/index.php'>" . _("Click here to Login Again.") . "</a>";
	echo "</div></td>
 </tr>
</table>
<br>\n";
	session_unset();
	session_destroy();
	Page::end(true);
?>


