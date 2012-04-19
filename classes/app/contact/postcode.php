<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Contact_Postcode {

  use \ADV\Core\Traits\SetFromArray;

    /**
     * @var int
     */
    static private $count = 0;
    protected $city;
    protected $state;
    protected $postcode;
    protected $url = '/contacts/postcode.php';

    /**
     * @param $options
     */
    public function __construct($options) {
      static::$count++;
      $this->setFromArray($options);
    }
    /**
     * @static
     *
     * @param       $city
     * @param       $state
     * @param       $postcode
     * @param array $options
     */
    public function render() {
      HTML::tr(TRUE)->td(array('class' => 'label '))->label(array('content' => 'City: ', 'for' => $this->city[0]), FALSE)->td->td(TRUE);
      UI::search($this->city[0], array(
        'url' => $this->url . '?city=1',
        'nodiv' => TRUE,
        'set' => static::$count,
        'name' => $this->city[0],
        'size' => 35,
        'max' => 40,
        'callback' => 'Adv.postcode.fetch'
      ));
      HTML::td()->tr;
      HTML::tr(TRUE)->td(array('class' => 'label'))->label(array('content' => 'State: ', 'for' => $this->state[0]), FALSE)->td->td(TRUE);
      HTML::input($this->state[0], array('maxlength' => 35, 'data-set' => static::$count, 'size' => 35, 'value' => $this->state[1], 'name' => $this->state[0]));
      HTML::tr(TRUE)->td(array('class' => 'label'))->label(array('content' => 'Postcode: ', 'for' => $this->postcode[0]), FALSE)->td->td(TRUE);
      UI::search($this->postcode[0], array(
        'url' => $this->url . '?postcode=1',
        'nodiv' => TRUE,
        'set' => static::$count,
        'name' => $this->postcode[0],
        'size' => 35,
        'max' => 40,
        'callback' => 'Adv.postcode.fetch'
      ));
      HTML::td()->tr;
      $this->registerJS();
    }
    /**
     * @static
     *
     * @param $this->city
     * @param $state
     * @param $postcode
     */
    public function registerJS() {
      if (static::$count == 1) {
        static::initjs();
      }
      $set = static::$count;
      $city = "#" . $this->city[0];
      $state = "#" . $this->state[0];
      $postcode = "#" . $this->postcode[0];
      $js = <<<JS
				Adv.postcode.add('$set','$city}','$state','$postcode');
JS;
      JS::onload($js);
      static::$count++;
    }
    /**
     * @static
     *
     * @param string $this->cit
     *
     * @return array
     */
    static public function searchByCity($city = "*") {
      $sql = "SELECT ID as id, CONCAT(Locality,', ',State,', ',Pcode) as label, CONCAT(Locality,'|',State,'|',Pcode) as value FROM postcodes WHERE Locality LIKE " . DB::escape('%' . $city . '%') . " ORDER BY Locality LIMIT 20";
      $result = DB::query($sql, "Could not find city");
      while (($resultArray[] = DB::fetch_assoc($result)) || array_pop($resultArray)) {
        ;
      }
      return $resultArray;
    }
    /**
     * @static
     *
     * @param string $postcode
     *
     * @return array
     */
    static public function searchByPostcode($postcode = "*") {
      $sql = "SELECT ID as id, CONCAT(Locality,', ',State,', ',Pcode) as label, CONCAT(Locality,'|',State,'|',Pcode) as value FROM postcodes WHERE Pcode LIKE " . DB::escape($postcode . '%') . " ORDER BY Pcode LIMIT 20";
      $result = DB::query($sql, "Could not find postcode");
      while (($resultArray[] = DB::fetch_assoc($result)) || array_pop($resultArray)) {
        ;
      }
      return $resultArray;
    }

    protected function initjs() {
      $js = Cache::get('js.postcode');
      if ($js === FALSE) {
        $js = <<<JS
						Adv.extend({
						 postcode: (function() {
						 var sets= [];
						 return {
												add: function(set,city,state,code) {
													sets[set] = {city:$(city),state:$(state),postcode:$(code)}
												},
						 fetch: function(data,item,ui) {
						 		var set=$(ui).data("set");
						 data = data.value.split('|');
							sets[set].city.val(data[0]).trigger('change');
						 sets[set].state.val(data[1]).trigger('change');
						 sets[set].postcode.val(data[2]).trigger('change');
						 return false;
						 }
						 }
						 }())
						})
JS;
        $jsmin = new JSMin();
        $js = $jsmin->minify($js);
        Cache::set('js.postcode', $js);
      }
      JS::beforeload($js);
    }
  }
