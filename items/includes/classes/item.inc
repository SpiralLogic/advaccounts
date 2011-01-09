<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 12/4/10
 * Time: 7:37 PM
 * To change this template use File | Settings | File Templates.
 */
class Item extends DB_abstract {
    public $stock_id;
    public $description;
    public $long_description;
    public $data;

    public function __construct($id = 0) {
        if ($id) {
            $this->_read($id);
        } else {
            $this->_new($id);
        }
    }

    protected function _read($id = 0) {
        $sql = "SELECT * FROM " . TB_PREF . "stock_master WHERE stock_id = " . db_escape($id);
        $result = db_query($sql, 'Could not retrieve stock information');
        if (db_num_rows($result) == 1) {
            $myrow = db_fetch_assoc($result);
            $this->data = $myrow;
            $this->stock_id = $myrow['stock_id'];
            $this->description = $myrow['description'];
            $this->long_description = $myrow['long_description'];
        }
    }

    protected function _canProcess() {
        // TODO: Implement _canProcess() method.
    }

    protected function _countTransactions() {
        // TODO: Implement _countTransactions() method.
    }

    protected function _defaults() {
        // TODO: Implement _defaults() method.
    }

    protected function _new() {
        // TODO: Implement _new() method.
    }

    protected function _saveNew() {
        // TODO: Implement _saveNew() method.
    }

    protected function delete() {
        // TODO: Implement delete() method.
    }

    function save($changes = null) {
        // TODO: Implement save() method.
    }
}
