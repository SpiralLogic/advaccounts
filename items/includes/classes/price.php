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

		public $stockid;
		protected $_type;

		static function getPrices($stockid, $type) {
			switch ($type) {
				case self::PURCHASE:
					return DB::select()->from('purch_data')->where('stockid=', $stockid)->fetch()->asClassLate('Price')->all();
				case self::SALE:
					return DB::select()->from('prices')->where('stockid=', $stockid)->fetch()->asClassLate('Price')->all();
				default:
					throw Exception();
			}
		}

		public function save() {
			DB::update('prices')->where('stockid=', $this->stockid)->and_where('id=', $this->id)->exec($this);
		}

	}
