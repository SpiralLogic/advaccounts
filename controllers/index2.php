<?php
  /**
   * Object hashing tests.
   */
  $sos = new SplObjectStorage();

  $docs       = array();
  $iterations = 100000;

  for ($i = 0; $i < $iterations; ++$i) {
    $doc = new DOMDocument();
    //$doc = new stdClass();

    $docs[] = $doc;
  }

  $start = $finis = 0;

  $mem_empty = memory_get_usage();

  // Load the SplObjectStorage
  $start = microtime(true);
  foreach ($docs as $d) {
    $sos->attach($d);
  }
  $finis = microtime(true);

  $time_to_fill = $finis - $start;

  // Check membership on the object storage
  $start = microtime(false);
  foreach ($docs as $d) {
    $sos->contains($d);
  }

  $finis = microtime(false);

  $time_to_check = $finis - $start;

  $mem_spl = memory_get_usage();

  $mem_used = $mem_spl - $mem_empty;

  printf("SplObjectStorage:\nTime to fill: %0.12f.\nTime to check: %0.12f.\nMemory: %d\n\n", $time_to_fill, $time_to_check, $mem_used);

  unset($sos);
  $mem_empty = memory_get_usage();

  // Test arrays:
  $start = microtime(true);
  $arr   = array();

  // Load the array
  foreach ($docs as $d) {
    $arr[spl_object_hash($d)] = $d;
  }
  $finis = microtime(true);

  $time_to_fill = $finis - $start;

  // Check membership on the array
  $start = microtime(false);
  foreach ($docs as $d) {
    //$arr[spl_object_hash($d)];
    isset($arr[spl_object_hash($d)]);
  }

  $finis = microtime(false);

  $time_to_check = $finis - $start;
  $mem_arr       = memory_get_usage();

  $mem_used = $mem_arr - $mem_empty;

  printf("Arrays:\nTime to fill: %0.12f.\nTime to check: %0.12f.\nMemory: %d\n\n", $time_to_fill, $time_to_check, $mem_used);
?>
