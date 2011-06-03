<?php

/* * ********************************************************************
  Copyright (C) FrontAccounting, LLC.
  Released under the terms of the GNU General Public License, GPL,
  as published by the Free Software Foundation, either version 3
  of the License, or (at your option) any later version.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
  See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
 * ********************************************************************* */

class advanced_app extends application
{

	function advanced_app()
	{
		$this->application("Advanced", _($this->help_context = "&Advanced"));
		$this->add_module(_("Websales To Jobsboard"));
		$this->add_lapp_function(0, _("Put websales on Bobs Joard"),
								 "http://advaccounts/jobsboard/websales/", 'SA_OPEN');
		$this->add_lapp_function(0, _("Search"),
								 "http://advaccounts/jobsboard/search/", 'SA_OPEN');
$this->add_rapp_function(0,"Add To Order from Website",'javascript:var%20s=document.createElement("script");s.setAttribute("src",%20"http://advaccounts/js/js2/addfromsite.js?"+Math.floor(Math . random() * 1000000));document .
                                                                                                                       getElementsByTagName("body")[0].appendChild(s);;void(0);',
                         'SA_OPEN');
	}

}
