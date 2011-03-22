<?php
class fbTimer
{
	protected static $timerStart = 0;
	protected static $timerEnd = 0;

	public static function start()
	{
		self::$timerStart = microtime(true);
		self::$timerEnd = 0;
	}

	public static function stop()
	{
		self::$timerEnd = microtime(true);
	}

	public static function get()
	{
		if (self::$timerEnd == 0) {
			return microtime(true) - self::$timerStart;
		}
		return self::$timerEnd - self::$timerStart;
	}
}


