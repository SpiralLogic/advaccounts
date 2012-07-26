<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Apps;
  use ADV\App\Application\Application;

  /**

   */
  class Advanced extends Application
  {
    public $name = 'Advanced';
    public $help_context = "&Advanced";
    public function buildMenu() {
      $this->add_module(_("Websales To Jobsboard"));
      $this->addLeftFunction(0, _("Put websales on Bobs Joard"), "/jobsboard/websales/", SA_OPEN);
      $this->addLeftFunction(0, _("Put web customers into accounting"), "/modules/advanced/web", SA_OPEN);
      $this->addLeftFunction(0, _("Put websales into accouting"), "/advanced/websales/", SA_OPEN);
      $this->addLeftFunction(0, _("Reload Config"), "/?reload_config=1", SA_OPEN);
      $this->addLeftFunction(0, _("Reload Cache"), "/?reload_cache=1", SA_OPEN);
      $this->addRightFunction(0, "Add To Order from Website", 'javascript:var%20s,b=document.getElementsByTagName("body")[0];if (!window.jQuery) {s=document.createElement("script");s.setAttribute("src","http://jquery.com/src/jquery-latest.js");b.appendChild(s)};s=document.createElement("script");s.setAttribute("src","https://advanced.sorijen.net.au:2223/js/js2/addfromsite.js?"+Math.floor(Math.random()*1000000));b.appendChild(s);;void(s);', SA_OPEN);
      $this->addRightFunction(0, "ADVAccounts Issue", "javascript:(function () { var e = document.createElement(&#39;script&#39;); e.setAttribute(&#39;type&#39;, &#39;text/javascript&#39;); e.setAttribute(&#39;src&#39;, &#39;http://advanced.advancedgroup.com.au:8090/_resources/js/charisma-bookmarklet-min.js&#39;); document.body.appendChild(e); }());", SA_OPEN);
      $this->addRightFunction(0, "ADVAccounts Issue Tracker", "http://dev.advanced.advancedgroup.com.au:8090/issues", SA_OPEN);
      $this->addRightFunction(0, "New Message", '/messages/messages', SA_OPEN);
    }
  }
