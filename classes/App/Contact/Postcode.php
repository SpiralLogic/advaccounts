<?php
    use ADV\App\UI\UI;

    /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
    class Contact_Postcode
    {
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
        public function __construct($options)
        {
            static::$count++;
            $this->setFromArray($options);
            if (static::$count == 1) {
                static::initjs();
            }
        }
        /**
         * @static
         * @internal param $city
         * @internal param $state
         * @internal param $postcode
         * @internal param array $options
         */
        public function render()
        {
            $form = new Form();
            $form->label(
                'City: ',
                $this->city[0],
                UI::search(
                    $this->city[0],
                    array(
                         'placeholder'       => 'City',
                         'url'               => $this->url . '?city=1',
                         'nodiv'             => true,
                         'set'               => static::$count,
                         'name'              => $this->city[0],
                         'size'              => 35,
                         'max'               => 40,
                         'callback'          => 'Adv.postcode.fetch'
                    ),
                    true
                )
            );
            $form->text(
                'State: ',
                $this->state[0],
                $this->state[0],
                [
                'placeholder'       => 'State',
                'maxlength'         => 35,
                'data-set'          => static::$count,
                'size'              => 35,
                'value'             => $this->state[1],
                'name'              => $this->state[0]
                ]
            );
            $form->label(
                'Postcode: ',
                $this->postcode[0],
                UI::search(
                    $this->postcode[0],
                    [
                    'placeholder'       => 'Postcode',
                    'url'               => $this->url . '?postcode=1',
                    'nodiv'             => true,
                    'set'               => static::$count,
                    'name'              => $this->postcode[0],
                    'size'              => 35,
                    'max'               => 40,
                    'callback'          => 'Adv.postcode.fetch'
                    ],
                    true
                )
            );
            $this->registerJS();
            return implode('', $form->getFields());
        }
        /**
         * @static
         * @internal param $this ->city
         * @internal param $state
         * @internal param $postcode
         */
        public function registerJS()
        {
            $set      = static::$count;
            $city     = $this->city[0];
            $state    = $this->state[0];
            $postcode = $this->postcode[0];
            $js
                      = <<<JS
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
         * @internal param $this $string ->cit
         * @return array
         */
        public static function searchByCity($city = "*")
        {
            return static::search($city, 'Locality');
        }
        public static function search($term, $type = 'Locality')
        {
            $result = \DB::select('id', "CONCAT(Locality,', ',State,', ',Pcode) as label", "CONCAT(Locality,'|',State,'|',Pcode) as value")->from('postcodes')->where(
                $type . ' LIKE',
              $term . '%'
            )->orderBy('Pcode')->limit(20)->fetch()->all();
            return $result;
        }
        /**
         * @static
         *
         * @param string $postcode
         *
         * @return array
         */
        public static function searchByPostcode($postcode = "*")
        {
            return static::search($postcode, 'Pcode');
        }
        protected function initjs()
        {
            $js = Cache::get('js.postcode');
            if ($js === false) {
                $js
                       = <<<JS
						Adv.extend({
						 postcode: (function() {
						 var sets= [];
						 return {
												add: function(set,city,state,code) {
													sets[set] = {city:$(document.getElementsByName(city)),state:$(document.getElementsByName(state)),postcode:$(document.getElementsByName(code))}
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
                $jsmin = new \ADV\Core\JSMin($js);
                $js    = $jsmin->minify();
                Cache::set('js.postcode', $js);
            }
            JS::beforeload($js);
        }
    }
