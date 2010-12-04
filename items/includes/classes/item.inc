<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 12/4/10
 * Time: 7:37 PM
 * To change this template use File | Settings | File Templates.
 */
 
class Item {
public $stock_id;
    public $description;
    public $long_description;
public $data;
    public function __construct($id=0) {
        if ($id) {
            $this->_read($id);
        } else {
            $this->_new($id);
        }
    }
    protected function _read($id=0) {
        $sql = "SELECT * FROM ".TB_PREF."stock_master WHERE stock_id = ".db_escape($id);
        $result = db_query($sql,'Could not retrieve stock information');
        if (db_num_rows($result)==1) {

        $myrow  = db_fetch_assoc($result);
        $this->data = $myrow;    
        $this->stock_id = $myrow['stock_id'];
            $this->description = $myrow['description'];
            $this->long_description = $myrow['long_description'];
        }
    }

}
