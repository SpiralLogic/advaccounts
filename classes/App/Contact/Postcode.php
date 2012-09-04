<?php
  use ADV\App\UI\UI;
  use ADV\App\Form\Form;
  use ADV\Core\JS;
  use ADV\Core\DB\DB;

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
    }
    /**
     * @static
     * @internal param $city
     * @internal param $state
     * @internal param $postcode
     * @internal param array $options
     */
    protected function generate()
    {
      $form = new Form();
      $form->custom(
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
      )->label('City: ');
      $form->text(
        $this->state[0],
        [
        'placeholder'       => 'State',
        'maxlength'         => 35,
        'data-set'          => static::$count,
        'size'              => 35,
        'name'              => $this->state[0]
        ]
      )->label('State: ')->val($this->state[1]);
      $form->custom(
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
      )->label('Postcode: ');
      $this->registerJS();

      return $form;
    }
    /**
     * @param array|mixed $values
     *
     * @return ADV\App\Form\Form
     */
    public function getForm($values= null)
    {
      $form = $this->generate();
      if ($values) $form->setValues($values);
      return $form;
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
      JS::_onload("Adv.postcode.add('$set','$city','$state','$postcode');");
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
    /**
     * @param        $term
     * @param string $type
     *
     * @return mixed
     */
    public static function search($term, $type = 'Locality')
    {
      $result = DB::_select('id', "CONCAT(Locality,', ',State,', ',Pcode) as label", "CONCAT(Locality,'|',State,'|',Pcode) as value")->from('postcodes')->where(
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
  }
