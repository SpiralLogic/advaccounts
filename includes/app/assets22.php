<?php
  /* SmartOptimizer v1.8
     * SmartOptimizer enhances your website performance using techniques
     * such as compression, concatenation, minifying, caching, and embedding on demand.
     *
     * Copyright (c) 2006-2010 Ali Farhadi (http://farhadi.ir/)
     * Released under the terms of the GNU Public License.
     * See the GPL for details (http://www.gnu.org/licenses/gpl.html).
     *
     * Author: Ali Farhadi (a.farhadi@gmail.com)
     * Website: http://farhadi.ir/
     */
  //Default settings
  class Assets22 {

  use \ADV\Core\Traits\SetFromArray;

    protected $baseDir = WEBROOT;
    protected $charSet = 'UTF-8';
    protected $debug = FALSE;
    protected $gzip = TRUE;
    protected $compressionLevel = 9;
    protected $gzipExceptions = array('gif', 'jpeg', 'jpg', 'png', 'swf', 'ico');
    protected $minify = TRUE;
    protected $concatenate = TRUE;
    protected $separator = ',';
    protected $serverCache = TRUE;
    protected $serverCacheCheck = FALSE;
    protected $cacheDir = 'cache';
    protected $cachePrefix = 'so_';
    protected $clientCache = TRUE;
    protected $clientCacheCheck = TRUE;
    protected $file = array();
    protected $minifyTypes = array(
      'js' => array(
        'minify' => TRUE, //
        'minifier' => 'JSMin' //
      ), //
      'css' => array( //
        'minify' => TRUE, //
        'minifier' => 'JSMin', //
        'settings' => array( //
          'embed' => TRUE, //
          'embedMaxSize' => 5120, //
          'embedExceptions' => 'htc',
        )
      )
    );
    //mime types
    protected $mimeTypes = array(
      "js" => "text/javascript",
      "css" => "text/css",
      "htm" => "text/html",
      "html" => "text/html",
      "xml" => "text/xml",
      "txt" => "text/plain",
      "jpg" => "image/jpeg",
      "jpeg" => "image/jpeg",
      "png" => "image/png",
      "gif" => "image/gif",
      "swf" => "application/x-shockwave-flash",
      "ico" => "image/x-icon",
    );
    protected function headerExit($status) {      header("Pragma: Public");
      header("Expires: " . $this->gmdatestr(time() + 315360000));
      header("Cache-Control: max-age=315360000");
      header("HTTP/1.0 $status");
      exit();
    }

    protected function headerNoCache() {
      // already expired
      header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
      // always modified
      header("Last-Modified: " . $this->gmdatestr());
      // HTTP/1.1
      header("Cache-Control: no-store, no-cache, must-revalidate");
      header("Cache-Control: post-check=0, pre-check=0", FALSE);
      header("Cache-Control: max-age=0", FALSE);
      // HTTP/1.0
      header("Pragma: no-cache");
      //generate a unique Etag each time
      header('Etag: ' . microtime());
    }

    protected function headerNeverExpire() {
      header("Expires: " . $this->gmdatestr(time() + 315360000));
      header("Cache-Control: max-age=315360000");
    }

    protected function debugExit($msg) {
      if (!$this->debug) {
        $this->headerExit('404 Not Found');
      }
      $this->headerNoCache();
      header('Content-Type: text/html; charset=' . $this->charSet);
      header("Content-Encoding: none");
      echo "<script>\n";
      echo "alert('Optimizer Error: " . str_replace("\n", "\\n", addslashes($msg)) . "');\n";
      echo "</script>\n";
      exit();
    }

    protected function gmdatestr($time = NULL) {
      if (is_null($time)) {
        $time = time();
      }
      return gmdate("D, d M Y H:i:s", $time) . " GMT";
    }

    protected function filesmtime() {
      static $filesmtime;
      if ($filesmtime) {
        return $filesmtime;
      }
      $filesmtime = max(@filemtime(__FILE__));
      foreach ($this->files as $file) {
        if (!file_exists($file)) {
          $this->debugExit("File not found ($file).");
        }
        $filesmtime = max(filemtime($file), $filesmtime);
      }
      return $filesmtime;
    }
    public function __construct() {
      $this->setFromArray(Config::get_all('assets22'));
      list($query) = explode('?', urldecode($_SERVER['QUERY_STRING']));
      if (preg_match('/^\/?(.+\/)?(.+)$/', $query, $matchResult)) {
        $fileNames = $matchResult[2];
        $fileDir = $this->baseDir . $matchResult[1];
      }
      else {
        $this->debugExit("Invalid file name ($query)");
      }
      //if (strpos(realpath($fileDir), realpath($this->baseDir)) !== 0) $this->debugExit("File is out of base directory.");
      if ($this->concatenate) {
        $this->files = explode('&', $fileNames);
        $this->files = explode($this->separator, $this->files[0]);
        $this->concatenate = count($this->files) > 1;
      }
      else {
        $this->files = array($fileNames);
      }
      foreach ($this->files as $key => $file) {
        if (preg_match('/^[^\x00]+\.([a-z0-9]+)$/i', $file, $matchResult)) {
          $fileTypes[] = strtolower($matchResult[1]);
        }
        else {
          $this->debugExit("Unsupported file ($file)");
        }
        $this->files[$key] = $fileDir . $file;
      }
      if ($this->concatenate) {
        if (count(array_unique($fileTypes)) > 1) {
          $this->debugExit("Files must be of the same type.");
        }
      }
      $this->fileType = $fileTypes[0];
      if (!isset($this->mimeTypes[$this->fileType])) {
        $this->debugExit("Unsupported file type ($this->fileType)");
      }
      header("Content-Type: {$this->mimeTypes[$this->fileType]}; charset=" . $this->charSet);
      $this->gzip = ($this->gzip &&
        !in_array($this->fileType, $this->gzipExceptions) &&
        in_array('gzip', array_map('trim', explode(',', @$_SERVER['HTTP_ACCEPT_ENCODING']))) &&
        function_exists('gzencode'));
      if ($this->gzip) {
        header("Content-Encoding: gzip");
      }
      $this->minify = $this->minify && class_exists($this->minifyTypes[$this->fileType]);
            $this->serverCache = $this->serverCache && ($this->minify || $this->gzip || $this->concatenate);
      if ($this->serverCache) {
        $cachedFile = $this->cacheDir .DIRECTORY_SEPARATOR. $this->cachePrefix . md5($query) . '.' . $this->fileType . ($this->gzip ? '.gz' : '');
      }
      $generateContent = ((!$this->serverCache && (!$this->clientCache || !$this->clientCacheCheck || !isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || $_SERVER['HTTP_IF_MODIFIED_SINCE'] != $this->gmdatestr($this->filesmtime()))) ||
        ($this->serverCache && (!file_exists($cachedFile) || ($this->serverCacheCheck && $this->filesmtime() > filemtime($cachedFile)))));
      if ($this->clientCache && $this->clientCacheCheck) {
        if ($this->serverCache && !$generateContent) {
          $mtime = filemtime($cachedFile);
        }
        elseif ($this->serverCache) {
          $mtime = time();
        }
        else {
          $mtime = $this->filesmtime();
        }
        $mtimestr = $this->gmdatestr($mtime);
      }
      if (!$this->clientCache || !$this->clientCacheCheck || !isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || $_SERVER['HTTP_IF_MODIFIED_SINCE'] != $mtimestr) {
        if ($this->clientCache && $this->clientCacheCheck) {
          header("Last-Modified: " . $mtimestr);
          header("Cache-Control: must-revalidate");
        }
        elseif ($this->clientCache) {
          $this->headerNeverExpire();
        }
        else {
          $this->headerNoCache();
        }
        if ($generateContent) {
          $content = array();
          foreach ($this->files as $file) {
            (($content[] = @file_get_contents($file)) !== FALSE) || $this->debugExit("File not found ($file).");
          }
          $content = implode("\n", $content);
          if (isset($this->minifyTypes[$this->fileType])) {
            $minify_type_settings = $this->minifyTypes[$this->fileType];
            if (isset($minify_type_settings['minify']) && $minify_type_settings['minify']) {
              if (!isset($minify_type_settings['minifier'])) {
                $this->debugExit("Minifier not set for type " . $this->fileType);
              }
              $minifier_class = $minify_type_settings['minifier'];
              $minifier = new $minifier_class($fileDir, $minify_type_settings['settings'], $this->mimeTypes);
              $content = $minifier->minify($content);
            }
          }
          if ($this->gzip) {
            $content = gzencode($content, $this->compressionLevel);
          }
          if ($this->serverCache) {
            {
              $handle = @fopen($cachedFile, 'w');
              fwrite($handle, $content);
              fclose($handle);
            }
          }
          header('Content-Length: ' . strlen($content));
          echo $content;
        }
        else {
          header('Content-Length: ' . filesize($cachedFile));
          readfile($cachedFile);
        }
      }
      else {
        $this->headerExit('304 Not Modified');
      }
    }
  }