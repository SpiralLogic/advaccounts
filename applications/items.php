<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 12/4/10
 * Time: 6:22 PM
 * To change this template use File | Settings | File Templates.
 */


class items_app extends application
{

    function __construct()
    {
        $this->direct = 'items/items.php';
        $this->application("items", _($this->help_context = "Items"));

    }
}