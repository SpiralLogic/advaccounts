<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 11/07/11
	 * Time: 4:29 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Contact_Postcode {
		static private $count = 1;

		static public function render($city, $state, $postcode, $options = array()) {
			$o = array('url' => '/contacts/postcode.php');
			extract(array_merge($o, $options));
			HTML::tr(true)->td(array('class' => 'label '))->label(array('content' => 'City: ', 'for' => $city[0]), false)->td->td(true);
			UI::search($city[0], array(
				'url' => $o['url'] . '?city=1',
				'nodiv' => true,
				'set' => static::$count,
				'name' => $city[0],
				'size' => 35,
				'max' => 40,
				'callback' => 'Adv.postcode.fetch'
			));
			HTML::td()->tr;
			HTML::tr(true)->td(array('class' => 'label'))->label(array('content' => 'State: ', 'for' => $state[0]), false)->td->td(true);
			HTML::input($state[0], array('maxlength' => 35, 'data-set' => static::$count, 'size' => 35, 'value' => $state[1], 'name' => $state[0]));
			HTML::tr(true)->td(array('class' => 'label'))->label(array('content' => 'Postcode: ', 'for' => $postcode[0]), false)->td->td(true);
			UI::search($postcode[0], array(
				'url' => $o['url'] . '?postcode=1',
				'nodiv' => true,
				'set' => static::$count,
				'name' => $postcode[0],
				'size' => 35,
				'max' => 40,
				'callback' => 'Adv.postcode.fetch'
			));
			HTML::td()->tr;
			static::registerJS("#" . $city[0], "#" . $state[0], "#" . $postcode[0]);
		}

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

		static public function searchByCity($city = "*") {
			$sql = "SELECT ID as id, CONCAT(Locality,', ',State,', ',Pcode) as label, CONCAT(Locality,'|',State,'|',Pcode) as value FROM postcodes WHERE Locality LIKE " . DB::escape('%' . $city . '%') . " ORDER BY Locality LIMIT 20";
			$result = DB::query($sql, "Could not find city");
			while (($resultArray[] = DB::fetch_assoc($result)) || array_pop($resultArray)) {
				;
			}
			return $resultArray;
		}

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
							sets[set].city.val(data[0]);
						 sets[set].state.val(data[1]);
						 sets[set].postcode.val(data[2]);
						 return false;
						 }
						 }
						 }())
						})
JS;
			JS::beforeload($js);
		}
	}
