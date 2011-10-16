<?php
	//memcached simple test
	session_module_name('memcache');
	phpinfo();
	$memcache = new Memcache;
	$memcache->connect('localhost', 11211) or die ("Could not connect");
	$key = md5('42data'); //something unique
	for ($k = 0; $k < 5; $k++) {
		$data = $memcache->get($key);
		if ($data == NULL) {
			$data = array();
			//generate an array of random shit
			echo "expensive query";
			for ($i = 0; $i < 100; $i++) {
				for ($j = 0; $j < 10; $j++) {
					$data[$i][$j] = 42; //who cares
				}
			}
			$memcache->set($key, $data, 0, 3600);
		} else {
			echo "cached";
		}
	}
