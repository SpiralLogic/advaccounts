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


class ui_menu {

    protected $tabs = array();
    protected $content = '';
    function __construct() {

        FB::info('test');
        $this->content = "<div id='tabs'><ul>";
    }
    function addTab($title,$link,$tooltip='') {
            $this->tabs[] = new ui_menu_item($title,$link,$tooltip);
            FB::info($this->tabs);
    }
    function render() {
        global $js_lib;

        foreach ($this->tabs as $key => $tab) {
            $this->content .= "<li><a href='{$tab->link}'><span>{$tab->title}</span></a></li>";
        }
        $this->content .= "</ul>";/*
        foreach ($this->tabs as $key => $tab) {
            $this->content .= "<div id='tabs-$key'><p>{$tab->link}</p></div>";
        }*/
    //    $this->content .= '<div id="tabs-0"></div>';
        echo $this->content;

    }
}


class ui_menu_item {
    public $content ='';
    public $link = '';
    public $title = '';
    public $tooltip = '';
function __construct($title,$link,$tooltip='') {
    $this->title = $title;
    $this->link = $link;
    $this->tooltop = $tooltip;
    
}


}