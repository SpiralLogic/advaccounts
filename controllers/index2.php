<?php
  include '../classes/Core/Arr.php';
  include '../classes/Core/Traits/Singleton.php';
  include '../classes/Core/Traits/StaticAccess2.php';
  include '../classes/Core/Dates.php';
  \ADV\Core\Arr::get(['test'], 0, false);
  unset($start, $test, $memstart, $test2, $i);
  $memstart = memory_get_usage(true);
  print_r(memory_get_usage(true) - $memstart . "\n");
  $start = microtime(true);
  $test  = ['one', 'two', 'three'];
  for ($i = 0; $i < 10000; $i++) {
    $test2 = [$i=> 'four', $i + 1=> 'five'];
    \ADV\Core\Arr::append($test, $test2);
  }
  print_r(\ADV\Core\Dates::getReadableTime(microtime(true) - $start) . "\n");
  print_r(memory_get_usage(true) - $memstart . "\n");
  unset($start, $test, $memstart, $test2, $i);
  $memstart = memory_get_usage(true);
  print_r(memory_get_usage(true) - $memstart . "\n");
  $start = microtime(true);
  $test  = ['one', 'two', 'three'];
  for ($i = 0; $i < 10000; $i++) {
    $test2 = [$i=> 'four', $i + 1=> 'five'];
    array_append($test, $test2);
  }
  print_r(\ADV\Core\Dates::getReadableTime(microtime(true) - $start) . "\n");
  print_r(memory_get_usage(true) - $memstart . "\n");
