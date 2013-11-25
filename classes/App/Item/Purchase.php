<?php
  namespace ADV\App\Item {
    use ADV\Core\DB\DB;
    use ADV\App\Validation;

    /**

     */
    class Purchase extends \ADV\App\DB\Base {
      protected $_table = 'purch_data';
      protected $_classname = 'Pruchase Price';
      protected $_id_column = 'id';
      public $id=0;
      public $creditor_id=0;
      public $stock_id;
      public $stockid;
      public $price = 0.0000;
      public $suppliers_uom='ea';
      public $supplier;
      public $conversion_factor = 1.00000000;
      public $supplier_description;
      public $last_update;
      /**
       * @static
       *
       * @param $stock_id
       *
       * @return null|\PDOStatement
       */
      public static function getAll($stock_id) {
        $sql = "SELECT id, suppliers.name, stock_id,stockid,price,suppliers_uom,conversion_factor,supplier_description, last_update
              FROM purch_data INNER JOIN suppliers
              ON purch_data.creditor_id=suppliers.creditor_id
              WHERE stock_id = " . DB::_escape($stock_id);
        return DB::_query($sql, "The supplier purchasing details for the selected part could not be retrieved")->fetchAll(\PDO::FETCH_ASSOC);
      }
      /**
       * @param \ADV\Core\DB\Query\Select $query
       *
       * @return \ADV\Core\DB\Query\Select
       */
      protected function getSelectModifiers(\ADV\Core\DB\Query\Select $query) {
        return $query->select('purch_data.*', 'name as supplier')->from('suppliers')->where('suppliers.creditor_id=purch_data.creditor_id');
      }
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      protected function canProcess() {
        if (!Validation::is_num($this->creditor_id, 0)) {
          return $this->status(false, 'Creditor_id must be a number', 'creditor_id');
        }
        if (strlen($this->stock_id) > 20) {
          return $this->status(false, 'Stock_id must be not be longer than 20 characters!', 'stock_id');
        }
        $this->stockid = Item::getStockID((string) $this->stock_id);
        if (!$this->stockid) {
          return $this->status(false, 'Can\'t add prive to non-existing item', 'stock_id');
        }
        if (!Validation::is_num($this->price, 0)) {
          return $this->status(false, 'Price must be a number and $0 or more', 'price');
        }
        if (strlen($this->suppliers_uom) > 50) {
          return $this->status(false, 'Suppliers_uom must be not be longer than 50 characters!', 'suppliers_uom');
        }
        if (!Validation::is_num($this->conversion_factor)) {
          return $this->status(
            false,
            '"The conversion factor entered was not numeric. The conversion factor is the number by which the price must be divided by to get the unit price in our unit of measure."r',
            'conversion_factor'
          );
        }
        if (strlen($this->supplier_description) > 20) {
          return $this->status(false, 'Supplier_description must be not be longer than 20 characters!', 'supplier_description');
        }
        if (!strlen($this->supplier_description)) {
          $this->supplier_description = $this->stock_id;
        }
        return true;
      }
    }
  }
  namespace {
    use ADV\App\Item\Item;

    /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
    class Item_Purchase {
      /**
       * @static
       *
       * @param      $creditor_id
       * @param      $stock_id
       * @param      $price
       * @param      $suppliers_uom
       * @param      $conversion_factor
       * @param      $supplier_description
       * @param null $stockid
       */
      public static function add($creditor_id, $stock_id, $price, $suppliers_uom, $conversion_factor, $supplier_description, $stockid = null) {
        if ($stockid == null) {
          $stockid = Item::get_stockid($stock_id);
        }
        $sql = "INSERT INTO purch_data (creditor_id, stockid, stock_id, price, suppliers_uom,
        conversion_factor, supplier_description) VALUES (";
        $sql .= DB::_escape($creditor_id) . ", " . DB::_escape($stock_id) . ", " . DB::_escape($stockid) . ", " . $price . ", " . DB::_escape(
          $suppliers_uom
        ) . ", " . $conversion_factor . ", " . DB::_escape($supplier_description) . ")";
        DB::_query($sql, "The supplier purchasing details could not be added");
      }
      /**
       * @static
       *
       * @param $selected_id
       * @param $stock_id
       * @param $price
       * @param $suppliers_uom
       * @param $conversion_factor
       * @param $supplier_description
       */
      public static function update($selected_id, $stock_id, $price, $suppliers_uom, $conversion_factor, $supplier_description) {
        $sql = "UPDATE purch_data SET price=" . $price . ",
        suppliers_uom=" . DB::_escape($suppliers_uom) . ",
        conversion_factor=" . $conversion_factor . ",
        supplier_description=" . DB::_escape($supplier_description) . "
        WHERE stock_id=" . DB::_escape($stock_id) . " AND
        creditor_id=" . DB::_escape($selected_id);
        DB::_query($sql, "The supplier purchasing details could not be updated");
      }
      /**
       * @static
       *
       * @param $selected_id
       * @param $stock_id
       */
      public static function delete($selected_id, $stock_id) {
        $sql = "DELETE FROM purch_data WHERE creditor_id=" . DB::_escape($selected_id) . "
        AND stock_id=" . DB::_escape($stock_id);
        DB::_query($sql, "could not delete purchasing data");
      }
      /**
       * @static
       *
       * @param $stock_id
       *
       * @return null|PDOStatement
       */
      public static function getAll($stock_id) {
        $sql = "SELECT purch_data.*,suppliers.name, suppliers.curr_code
        FROM purch_data INNER JOIN suppliers
        ON purch_data.creditor_id=suppliers.creditor_id
        WHERE stock_id = " . DB::_escape($stock_id);
        return DB::_query($sql, "The supplier purchasing details for the selected part could not be retrieved");
      }
      /**
       * @static
       *
       * @param $selected_id
       * @param $stock_id
       *
       * @return \ADV\Core\DB\Query\Result|Array
       */
      public static function get($selected_id, $stock_id) {
        $sql    = "SELECT purch_data.*,suppliers.name FROM purch_data
        INNER JOIN suppliers ON purch_data.creditor_id=suppliers.creditor_id
        WHERE purch_data.creditor_id=" . DB::_escape($selected_id) . "
        AND purch_data.stock_id=" . DB::_escape($stock_id);
        $result = DB::_query($sql, "The supplier purchasing details for the selected supplier and item could not be retrieved");
        return DB::_fetch($result);
      }
      /**
       * @static
       *
       * @param      $name
       * @param null $selected_id
       * @param bool $all_option
       * @param bool $submit_on_change
       * @param bool $all
       * @param bool $editkey
       * @param bool $legacy
       *
       * @return string
       */
      public static function select($name, $selected_id = null, $all_option = false, $submit_on_change = false, $all = false, $editkey = false, $legacy = false) {
        return Item::select(
          $name,
          $selected_id,
          $all_option,
          $submit_on_change,
          array(
               'where'         => "mb_flag!= '" . STOCK_MANUFACTURE . "'",
               'show_inactive' => $all,
               'editable'      => false
          ),
          false,
          $legacy
        );
      }
      /**
       * @static
       *
       * @param      $label
       * @param      $name
       * @param null $selected_id
       * @param bool $all_option
       * @param bool $submit_on_change
       * @param bool $editkey
       */
      public static function cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $editkey = false) {
        if ($label != null) {
          echo "<td>$label</td>\n";
        }
        echo Item::select(
          $name,
          $selected_id,
          $all_option,
          $submit_on_change,
          array(
               'where'       => "mb_flag!= '" . STOCK_MANUFACTURE . "'",
               'editable'    => 30,
               'cells'       => true,
               'description' => '',
               'class'       => 'auto'
          )
        );
      }
      /**
       * @static
       *
       * @param      $label
       * @param      $name
       * @param null $selected_id
       * @param bool $all_option
       * @param bool $submit_on_change
       * @param bool $editkey
       */
      public static function row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $editkey = false) {
        echo "<tr><td class='label'>$label</td>";
        Item_Purchase::cells(null, $name, $selected_id, $all_option, $submit_on_change, $editkey);
        echo "</tr>\n";
      }
    }
  }
