<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/

  Page::start(_("Logout"), SA_OPEN, TRUE, FALSE, '');
  echo "<table style='width:100%' > <tr><td class='center'><img src='/themes/default/images/logo_advaccounts.png' alt='ADVAccounts' style='width:250px; height:50px' /></td></tr>
 <tr><td>&nbsp;</td></tr>
 <tr><td><div class='center'><span class='apptitle'>";
  echo _("Thank you for using") . " ";
  echo "<strong>" . APP_TITLE . ' ' . VERSION . "</strong>";
  echo "</span></div></td></tr>
 <tr><td>&nbsp;</td></tr>
 <tr><td><div class='center'>";
  echo "<a class='bold' href='/index.php'>" . _("Click here to Login Again.") . "</a>";
  echo "</div></td></tr>
</table><br>\n";
  Session::kill();
  Page::end(TRUE);


