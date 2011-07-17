<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 12/4/10
 * Time: 7:37 PM
 * To change this template use File | Settings | File Templates.
 */
   class Item extends DB_abstract
   {
      public $stock_id;
      public $name;
      public $category_id;
      public $description;
      public $last_cost;
      public $uom;
      public $units;
      public $salePrices = array();
      public $purchPrices = array();

      public function __construct($id = 0)
      {
         parent::__construct($id);
         $this->id = &$this->stock_id;
         $this->uom = &$this->units;


      }

      protected function _read($id = 0)
      {
         $sql = "SELECT * FROM stock_master WHERE stock_id = " . db_escape($id);
         $result = db_query($sql, 'Could not retrieve stock information');
         if (db_num_rows($result) == 1) {
            $myrow = db_fetch_assoc($result);
            $this->stock_id = $myrow['stock_id'];
            $this->name = $myrow['description'];
            $this->category_id = $myrow['category_id'];
            $this->description = $myrow['long_description'];
            $this->last_cost = $myrow['last_cost'];
            $this->units = $myrow['units'];
         }

      }

      protected function _canProcess()
      {
         // TODO: Implement _canProcess() method.
      }

      protected function _countTransactions()
      {
         // TODO: Implement _countTransactions() method.
      }

      protected function _defaults()
      {
         // TODO: Implement _defaults() method.
      }

      protected function _new()
      {
         // TODO: Implement _new() method.
      }

      protected function _saveNew()
      {
         // TODO: Implement _saveNew() method.
      }

      public function delete()
      {
         // TODO: Implement delete() method.
      }

      function save($changes = null)
      {
         // TODO: Implement save() method.
      }

      function  getSalePrices()
      {
         $sql = "SELECT * FROM prices WHERE stock_id = " . $this->id;
         $result = db_query($sql, 'Could not get item pricing');
         while ($row = db_fetch_assoc($result)) {
            $this->prices[$row['id']] = array("curr" => $row['curr_abrev'], "type" => $row['type'], "price" => $row['price']);
         }
      }

      function  getPurchPrices()
      {
         $sql = "SELECT * FROM purch_data WHERE stock_id = " . $this->id;
         $result = db_query($sql, 'Could not get item pricing');
         while ($row = db_fetch_assoc($result)) {
            $this->prices[$row['supplier_id']] =
               array("code" => $row['supplier_description'], "price" => $row['price'], "suppliers_uom" => $row['uom'], "conv" => $row['conversion_factor']);
         }
      }
function  getStock()
      {
         $sql = "SELECT * FROM loc_stock WHERE stock_id = " . $this->id;
         $result = db_query($sql, 'Could not get item stock levels');
         while ($row = db_fetch_assoc($result)) {
            $this->stock[] =
               array("loc_code" => $row['loc_code'], "price" => $row['price'], "suppliers_uom" => $row['uom'], "conv" => $row['conversion_factor']);
         }
      }
      static function search($term)
      {
         $sql = "SELECT stock_id as id, description as label, stock_id as value FROM stock_master " . "where stock_id LIKE '%" . $term . "%' OR description LIKE '%" . $term . "%' LIMIT 200";
         $result = db_query($sql, 'Couldn\'t Get Items');
         while ($row = db_fetch_assoc($result)) {
            $data[] = $row;
         }
         return $data;
      }

   }
