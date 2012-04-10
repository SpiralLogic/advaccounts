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

    /**
     * @var int
     */
    static private $count = 1;
    /**
     * @static
     *
     * @param       $city
     * @param       $state
     * @param       $postcode
     * @param array $options
     */
    static public function render($city, $state, $postcode, $options = array()) {
      $o = array('url' => '/contacts/postcode.php');
      extract(array_merge($o, $options));
      HTML::tr(TRUE)->td(array('class' => 'label '))->label(array('content' => 'City: ', 'for' => $city[0]), FALSE)->td->td(TRUE);
      UI::search($city[0], array(
        'url' => $o['url'] . '?city=1',
        'nodiv' => TRUE,
        'set' => static::$count,
        'name' => $city[0],
        'size' => 35,
        'max' => 40,
        'callback' => 'Adv.postcode.fetch'
      ));
      HTML::td()->tr;
      HTML::tr(TRUE)->td(array('class' => 'label'))->label(array('content' => 'State: ', 'for' => $state[0]), FALSE)->td->td(TRUE);
      HTML::input($state[0], array('maxlength' => 35, 'data-set' => static::$count, 'size' => 35, 'value' => $state[1], 'name' => $state[0]));
      HTML::tr(TRUE)->td(array('class' => 'label'))->label(array('content' => 'Postcode: ', 'for' => $postcode[0]), FALSE)->td->td(TRUE);
      UI::search($postcode[0], array(
        'url' => $o['url'] . '?postcode=1',
        'nodiv' => TRUE,
        'set' => static::$count,
        'name' => $postcode[0],
        'size' => 35,
        'max' => 40,
        'callback' => 'Adv.postcode.fetch'
      ));
      HTML::td()->tr;
      static::registerJS("#" . $city[0], "#" . $state[0], "#" . $postcode[0]);
    }
    /**
     * @static
     *
     * @param $city
     * @param $state
     * @param $postcode
     */
    static public function registerJS($city, $state, $postcode) {
      if (static::$count == 1) {
        static::initjs();
      }
      $set = static::$count;
      $js = <<<JS
				Adv.postcode.add('$set','$city','$state','$postcode');
JS;
      JS::onload($js);
      static::$count++;
    }
    /**
     * @static
     *
     * @param string $city
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

    static protected function initjs() {
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
      JS::beforeload($js);
    }
  }
