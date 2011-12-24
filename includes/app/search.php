<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 24/12/11
	 * Time: 1:58 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Search
	{
		protected $id = false;
		protected $description = '';
		protected $disabled = false;
		protected $editable = true;
		protected $selected = '';
		protected $cells = false;
		protected $inactive = false;
		protected $purchase = false;
		protected $sale = false;
		protected $descjs = '';
		protected $selectjs = '';
		protected $submitonselect = '';
		protected $sales_type = 1;
		protected $no_sale = false;
		protected $select = false;
		protected $type = 'local';
		protected $where = '';
		protected $UniqueID;
		protected $cachetime;
		protected $url;
		/**
		 *
		 */
		public function __construct(array $options = null) {
			if ($options) {
				$this->setOptions($options);
			}
			$this->cachetime = DB_Company::get_pref('login_tout');
			$this->UniqueID = md5(serialize($options));
			Cache::set($this->UniqueID, serialize($this), $this->cachetime);
			if ($this->cells) {
				$this->cells();
			};
			$this->noCells();
		}

		/**
		 * @param array $options
		 * @return mixed
		 **/
		public function setOptions(array $options) {
			if (!$options) {
				return;
			}
			foreach ($options as $k => $v) {
				(property_exists($this, $k)) and $this->$k = $v;
			}
	}
		/**
		 *
		 */
		protected function cells() {
			HTML::td(true);
			HTML::input($this->id, array('value' => $this->selected, 'name' => $this->id));
			$this->editable();
			HTML::td()->td(true);
			$this->description();
			HTML::td();
			JS::footerFile('/js/search.js');
			$jsoptions = array('url'=>$this->url,'descjs'=>$this->descjs,'selectjs'=>$this->selectjs,'UniqueID'=>$this->UniqueID);
			JS::addLive("Adv.itemsarch.init('".$this->id."',".JS::arrayToOptions($jsoptions),"Adv.itemsearch.clean()");
	}
		/**
		 *
		 */
		protected function editable() {
			HTML::label('lineedit', 'edit', array('for' => 'stock_id', 'class' => 'stock button', 'style' => 'display:none'));
	}
		/**
		 *
		 */
		protected function description() {
			$this->descjs = false;
			if ($this->description) {
				HTML::textarea('description', $this->description, array('name' => 'description', 'rows' => 1, 'cols' => 45), false);
				$this->descjs = true;
			}
		}
	}
