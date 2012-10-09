<?php
  use ADV\App\GL\Account;

  $array = Account::getAll();
  unset($start, $type, $name, $data, $memstart, $i);
  $memstart = memory_get_usage(true);
  print_r(memory_get_usage(true) - $memstart . "\n");
  $start = microtime(true);
  for ($i = 0; $i < 1000; $i++) {
    unset($type, $name, $data);
    $data = $array;
    foreach ($data as $key => $row) {
      $type[$key] = $row['type'];
      $name[$key] = $row['account_name'];
    }

    // Sort the data with volume descending, edition ascending
    // Add $data as the last parameter, to sort by the common key
    array_multisort($type, SORT_ASC, $name, SORT_ASC, $data);
  }
  print_r(\ADV\Core\Dates::getReadableTime(microtime(true) - $start) . "\n");
  print_r(memory_get_usage(true) - $memstart . "\n");

  unset($start, $type, $name, $data, $memstart, $i);
  $memstart = memory_get_usage(true);
  print_r(memory_get_usage(true) - $memstart . "\n");
  $start = microtime(true);
  for ($i = 0; $i < 1000; $i++) {
    unset($data);
    $data = $array;
    usort(
      $data,
      function ($a, $b) {
        $r = strcasecmp($a['type'], $b['type']);

        if ($r === 0) {
          $r = strcasecmp($a['account_name'], $b['account_name']);
        }
        return $r;
      }
    );
  }
  print_r(\ADV\Core\Dates::getReadableTime(microtime(true) - $start) . "\n");
  print_r(memory_get_usage(true) - $memstart . "\n");
  unset($start, $type, $name, $data, $memstart, $i);
  $memstart = memory_get_usage(true);
  print_r(memory_get_usage(true) - $memstart . "\n");
  $start = microtime(true);
  for ($i = 0; $i < 1000; $i++) {
    unset($data);
    $data = $array;
    usort(
      $data,
      function ($a, $b) {
        $r = strcasecmp($a['type'], $b['type']);

        if ($r === 0) {
          $r = strcasecmp($a['account_name'], $b['account_name']);
        }
        return $r;
      }
    );
  }
  print_r(\ADV\Core\Dates::getReadableTime(microtime(true) - $start) . "\n");
  print_r(memory_get_usage(true) - $memstart . "\n");

  unset($start, $type, $name, $data, $memstart, $i);
  $memstart = memory_get_usage(true);
  print_r(memory_get_usage(true) - $memstart . "\n");
  $start = microtime(true);
  for ($i = 0; $i < 1000; $i++) {
    unset($type, $name, $data);
    $data = $array;
    foreach ($data as $key => $row) {
      $type[$key] = $row['type'];
      $name[$key] = $row['account_name'];
    }

    // Sort the data with volume descending, edition ascending
    // Add $data as the last parameter, to sort by the common key
    array_multisort($type, SORT_ASC, $name, SORT_ASC, $data);
  }
  print_r(\ADV\Core\Dates::getReadableTime(microtime(true) - $start) . "\n");
  print_r(memory_get_usage(true) - $memstart . "\n");
