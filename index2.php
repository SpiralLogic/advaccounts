<?$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$start22 = $time;
	phpinfo();
	$var = getrusage();
	echo '<pre>';
	var_dump($var);

	function convert($size) {
		$unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
		return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
	}

	function getReadableTime($time) {
		$ret = $time;
		$formatter = 0;
		$formats = array('ms', 's', 'm');
		if ($time >= 1000 && $time < 60000) {
			$formatter = 1;
			$ret = ($time / 1000);
		}
		if ($time >= 60000) {
			$formatter = 2;
			$ret = ($time / 1000) / 60;
		}
		$ret = number_format($ret, 3, '.', '') . ' ' . $formats[$formatter];
		return $ret;
	}

	global $start22;
	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$finish = $time;
	$total_time = getReadableTime($finish - $start22);
	$generateinfo = 'Memory Usage: ' . convert(memory_get_usage()) . "<br>\n" .
	                'Memory Peak Usage: ' . convert(memory_get_peak_usage()) . "<br>" . 'Page generated in ' . $total_time . ' seconds.' . "\n";
	echo $generateinfo;
	echo "</div></body>";
	get_websales();
	echo   "</html>\n";