<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 11/07/11
	 * Time: 4:29 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Contacts_Postcode
	{
		private static $count = 1;

		static function				 searchByPostcode($postcode = "*")
		{
			$sql = "SELECT ID as id, CONCAT(Locality,', ',Pcode,', ',State) as label,  CONCAT(Locality,'|',Pcode,'|',State) as value FROM postcodes WHERE Pcode LIKE " . DB::escape($postcode . '%') . " ORDER BY Pcode LIMIT 20";
			$result = DB::query($sql, "Could not find postcode");
			while (($resultArray[] = DB::fetch_assoc($result)) || array_pop($resultArray)) {
				;
			}
			return $resultArray;
		}

		static function searchByCity($city = "*")
		{
			$sql = "SELECT ID as id, CONCAT(Locality,', ',Pcode,', ',State) as label,  CONCAT(Locality,'|',Pcode,'|',State) as value FROM postcodes WHERE Locality LIKE " . DB::escape('%' . $city . '%') . " ORDER BY Locality LIMIT 20";
			$result = DB::query($sql, "Could not find city");
			while (($resultArray[] = DB::fetch_assoc($result)) || array_pop($resultArray)) {
				;
			}
			return $resultArray;
		}

		static function render($postcode, $city, $state, $options = array())
		{
			$o = array('url' => '/contacts/postcode.php');
			extract(array_merge($o, $options));
			HTML::tr(true)->td(array('class' => 'label '))->label(array('content' => 'City: ', 'for' => $city[0]), false)->td->td(true);
			UI::search($city[0], array('url' => $o['url'] . '?city=1', 'nodiv' => true, 'set' => static::$count, 'name' => $city[0], 'size' => 35, 'max' => 40, 'callback' => 'Adv.postcode.fetch'));
			HTML::td()->tr;
			HTML::tr(true)->td(array('class' => 'label'))->label(array('content' => 'State: ', 'for' => $state[0]), false)->td->td(true);
			HTML::input($state[0], array('maxlength' => 35, 'data-set' => static::$count, 'size' => 35, 'value' => $state[1], 'name' => $state[0]));
			HTML::tr(true)->td(array('class' => 'label'))->label(array('content' => 'Postcode: ', 'for' => $postcode[0]), false)->td->td(true);
			UI::search($postcode[0], array('url' => $o['url'] . '?postcode=1', 'nodiv' => true, 'set' => static::$count, 'name' => $postcode[0], 'size' => 35, 'max' => 40, 'callback' => 'Adv.postcode.fetch'));
			HTML::td()->tr;
			static::registerJS("#" . $postcode[0], "#" . $city[0], "#" . $state[0]);
		}

		static function registerJS($postcode, $city, $state)
		{
			$js = <<<JS
			Adv.extend({
			    postcode: (function() {
			            var sets= [];
			        return {
									add: function(set,code,state,city) {
										sets[set] = {postcode:code,state:state,city:city}
									},
			            fetch: function(data,ui) {
			            console.log(ui);
			               data = data.value.split('|');
									    postcode.val(data[1]).trigger('change');
			                state.val(data[2]).trigger('change');
			                city.val(data[0]).trigger('change');
			                return false;
			            }
			        }
			    }())
			})
JS;
			JS::beforeload($js);
			static::$count++;
		}
	}
