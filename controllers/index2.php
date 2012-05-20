<?php
  /*  if (!User::i()->can_access(SA_DEBUGGING)) {
    throw new Adv_Exception("Administrator access only");
  }*/
//todo remove
  namespace Test {
    class Orders extends \ArrayIterator {

      function __construct() {
        $array = [
          'one'  => array('test2'=>'wawa','wawa'=>'tete'),
          'two'  => 'stuff',
          'three'=> 'things'
        ];
        parent::__construct($array);
      }
    }
  }
  namespace {
    $test = new Test\Orders();
    foreach ($test as $k=>$t) {
      var_dump($k,$t);
    }
  }
