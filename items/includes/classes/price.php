<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 14/09/11
 * Time: 2:32 PM
 * To change this template use File | Settings | File Templates.
 */

 class Price {

	const PURCHASE = 1;
	const SALE = 2;
	const SORT_UPDATE = 'last_update';
	const SORT_PRICE = 'price';
	const SORT_CODE = 'stock_id';

	public $stockid;
	protected $_type;

	static function getPrices($stockid, $type = self::SALE,$sort = self::SORT_PRICE) {
	 switch ($type) {
		case self::PURCHASE:
		 $result = DB::select()->from('purch_data')->where('stockid=', $stockid)->orderby($sort)->fetch()->asClassLate('Price', array(self::PURCHASE))->all();
		 break;
		case self::SALE:
		 $result = DB::select()->from('prices')->where('stockid=', $stockid)->orderby($sort)->fetch()->asClassLate('Price', array(self::SALE))->all();
		 break;
		default:
		 throw Exception();
	 }
	 if ($sort != self::SORT_CODE) $result = array_reverse($result);
	return $result;
	}

	static function getPriceBySupplier($stockid, $supplierid) {
	 $result = DB::select()->from('purch_data')->where('stockid=', $stockid)->and_where('supplier_id=', $supplierid)->fetch()->asClassLate('Price', array(self::PURCHASE))->one();
	 return $result;
	}

	public function __construct($type) {
	 $this->_type = $type;
	}

	public function save() {
	 DB::update('prices')->where('stockid=', $this->stockid)->and_where('id=', $this->id)->exec($this);
	}

 }
