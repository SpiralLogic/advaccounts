<?php
class fbTimer {
    protected static $timerStart = 0;
    protected static $timerEnd = 0;

    public static function start() {
        self::$timerStart = microtime();
        self::$timerEnd = 0;
    }

    public static function stop() {
        self::$timerEnd = microtime();
    }

    public static function get() {
        if (self::$timerEnd == 0) {
            self::stop();
        }
        return self::$timerEnd - self::$timerStart;
    }
}

// this class is simple to use
fbTimer::start();
// some code you want to benchmark
fbTimer::stop();
fb::log("execution time :" . fbTimer::get() . ' seconds');
