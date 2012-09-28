<?php
  echo memory_get_usage() / 1024 / 1024 . "\n";

  function getReadableTime($time) {
    $ret       = $time;
    $formatter = 0;
    $formats   = array('ms', 's', 'm');
    if ($time >= 1000 && $time < 60000) {
      $formatter = 1;
      $ret       = ($time / 1000);
    }
    if ($time >= 60000) {
      $formatter = 2;
      $ret       = ($time / 1000) / 60;
    }
    $ret = number_format($ret, 3, '.', '') . ' ' . $formats[$formatter];
    return $ret;
  }

  class test {
    public $test;
    public $test2 = [];
    public function __construct($i) {
      $this->test1   = 'wawa' . $i;
      $this->test2[] = new test2('rere');
      $this->test2[] = new test2('brbr');
      $this->test2[] = new test2('gdgd');
    }
  }

  class test2 {
    public $var;
    public function __construct($var) {
      $this->var = $var;
    }
  }

  flush();

  $memstart = memory_get_usage();

  $start = microtime(true);

  for ($i = 0; $i < 10000; $i++) {
    $test            = ['test'=> 'wawa' . $i];
    $test['test2']   = [];
    $test['test2'][] = 'rere';
    $test['test2'][] = 'brbr';
    $test['test2'][] = 'gdgd';

    $test3[] = $test;
  }

  $time = microtime(true) - $start;
  echo (memory_get_usage() - $memstart) / 1024 / 1024 . "\n";

  echo  getReadableTime($time) . "\n";
  unset($test, $test3, $start, $time, $memstart);
  flush();
  $memstart = memory_get_usage();

  $start = microtime(true);

  for ($i = 0; $i < 10000; $i++) {
    $test    = new test($i);
    $test3[] = $test;
  }
  $time = microtime(true) - $start;
  echo (memory_get_usage() - $memstart) / 1024 / 1024 . "\n";

  echo getReadableTime($time) . "\n";
  unset($test, $test3, $start, $time, $memstart);
  flush();

  echo memory_get_usage() / 1024 / 1024 . "\n";
