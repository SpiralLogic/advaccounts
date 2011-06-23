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
ini_set('display_errors', 'On'); $page_security = 'SA_SALESORDER';
$path_to_root = ".";
include_once($path_to_root . "/includes/session.inc");

if (!file_exists($path_to_root . '/config.php'))
   header("Location: " . $path_to_root . "/install/index.php");
/*ini_set('display_errors', 'On');
define('INSIGHT_IPS', '*'); // <- Your IP here for extra security
define('INSIGHT_AUTHKEYS', 'AAA03DDB961CE9B9B1C407F95A490521');
define('INSIGHT_PATHS', dirname(__FILE__));
define('INSIGHT_SERVER_PATH', '/');


// If using the zip archive
// TODO: Put the 'lib' folder from the archive on your include path
require_once('includes/lib/FirePHP/fb.php'); // (procedural API) or
//$firephp = FirePHP::getInstance(true);
//FB::setLogToInsightConsole('Firebug');
//$firephp->setLogToInsightConsole('Firebug'); // or

//require_once('includes/lib/FirePHP/Init.php'); // (object oriented API)

FB::info(__FILE__);*/

///$inspector = FirePHP::to('page');
//$console = $inspector->console();
add_access_extensions();
$app = &$_SESSION["App"];
if (isset($_GET['application']))
   $app->selected_application = $_GET['application'];

$app->display();
