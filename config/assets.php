<?php
  /*
     * SmartOptimizer Configuration File
     */
  return array( //base dir (a relative path to the base directory)
    'baseDir'          => WEBROOT,
    //Encoding of your js and css files. (utf-8 or iso-8859-1)
    'charSet'          => 'UTF-8',
    //Show error messages if any error occurs (true or false)
    'debug'            => true,
    //use this to set gzip compression On or Off
    'gzip'             => true,
    //use this to set gzip compression level (an integer between 1 and 9)
    'compressionLevel' => 9,
    //these types of files will not be gzipped nor minified
    'gzipExceptions'   => array('gif', 'jpeg', 'jpg', 'png', 'swf'),
    //use this to set Minifier On or Off
    'minifytypes'      => array(
      'js'  => array(
        'minify'   => true, //
        'minifier' => 'JSMin' //
      ), //
      'css' => array( //
        'minify'   => true, //
        'minifier' => 'JSMin', //
        'settings' => array( //
          //specifies whether to emebed files included in css files using the data URI scheme or not
          'embed'           => true, //
          //The maximum size of an embedded file. (use 0 for unlimited size)
          'embedMaxSize'    => 5120, //
          //these types of files will not be embedded
          'embedExceptions' => 'htc',
        )
      )
    ),
    //use this to set file concatenation On or Off
    'concatenate'      => true,
    //separator for files to be concatenated
    'separator'        => ',',
    //to set server-side cache On or Off
    'serverCache'      => true,
    //if you change it to false, the files will not be checked for modifications and always cached files will be used (for better performance)
    'serverCacheCheck' => true,
    //cache dir
    'cacheDir'         => WEBROOT . 'cache',
    //prefix for cache files
    'cachePrefix'      => 'so_',
    //to set client-side cache On or Off
    'clientCache'      => true,
    //Setting this to false will force the browser to use cached files without checking for changes.
    'clientCacheCheck' => true,
    'css'              => array(
      'default.css', //
      'jquery-ui.css', //
      'jquery.calc.css', //
      //	'jquery.fileupload-ui.css'
    ),
    //
    'footer'           => array(
      '/js/libs/JsHttpRequest.js',
      '/js/libs/jquery.min.js',
      '/js/libs/jquery-ui.min.js',
      '/js/advanced.js',
      '/js/behaviour.js',
      '/js/inserts.js',
      '/js/libs/jquery-tmpl.min.js',
      '/js/libs/jquery.calc.js',
      '/js/calc.js',
      '/js/sidemenu.js',
    ),
    //
    'maps_api_key'     => 'ABQIAAAAi6QUBXugcwuPqyTcPMoi7BRpI0TaLspvsTOnOMM4T78mt8FLYBQC5z4qKk3rJIKFVEWI7RCzzewJLQ',
    //
  );
