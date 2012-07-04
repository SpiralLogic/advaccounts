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
  namespace ADV\Core;
  /**

   */
  class Assets
  {

    protected $baseDir = WEBROOT;
    protected $charSet = 'UTF-8';
    protected $debug = false;
    protected $gzip = true;
    protected $compressionLevel = 9;
    protected $gzipExceptions = array('gif', 'jpeg', 'jpg', 'png', 'swf', 'ico');
    protected $minify = true;
    protected $concatenate = true;
    protected $separator = ',';
    protected $serverCache = true;
    protected $serverCacheCheck = true;
    protected $cacheDir = 'cache';
    protected $cachePrefix = 'so_';
    protected $clientCache = true;
    protected $clientCacheCheck = true;
    protected $file = array();
    protected $minifyTypes
      = array(
        'js'  => array(
          'minify'   => true, //
          'minifier' => 'JSMin', //
          'settings' => array() //
        ), //
        'css' => array( //
          'minify'   => true, //
          'minifier' => 'CSSMin', //
          'settings' => array( //
            'embed'           => true, //
            'embedMaxSize'    => 5120, //
            'embedExceptions' => 'htc',
          )
        )
      );
    protected $mimeTypes
      = array(
        "js"   => "text/javascript",
        "css"  => "text/css",
        "htm"  => "text/html",
        "html" => "text/html",
        "xml"  => "text/xml",
        "txt"  => "text/plain",
        "jpg"  => "image/jpeg",
        "jpeg" => "image/jpeg",
        "png"  => "image/png",
        "gif"  => "image/gif",
        "swf"  => "application/x-shockwave-flash",
        "ico"  => "image/x-icon",
      ); //mime types
    /**
     * @param $status
     */
    protected function headerExit($status)
    {
      header("Pragma: Public");
      header("Expires: " . $this->gmdatestr(time() + 315360000));
      header("Cache-Control: max-age=315360000");
      header("HTTP/1.0 $status");
      header("Vary: Accept-Encoding", false);
      $this->contentHeader();
      exit();
    }
    protected function headerNoCache()
    {
      // already expired
      header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
      // always modified
      header("Last-Modified: " . $this->gmdatestr());
      // HTTP/1.1
      header("Cache-Control: no-store, no-cache, must-revalidate");
      header("Cache-Control: post-check=0, pre-check=0", false);
      header("Cache-Control: max-age=0", false);
      header("Vary: Accept-Encoding", false);
      // HTTP/1.0
      header("Pragma: no-cache");
      //generate a unique Etag each time
      header('Etag: ' . microtime());
      $this->contentHeader();
    }
    protected function headerNeverExpire()
    {
      header("Expires: " . $this->gmdatestr(time() + 315360000));
      header("Cache-Control: max-age=315360000");
      header("Vary: Accept-Encoding", false);
      header("Last-Modified: " . $this->gmdatestr());
      $this->contentHeader();
    }
    /**
     * @param $msg
     */
    protected function debugExit($msg)
    {
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
    /**
     * @param null $time
     *
     * @return string
     */
    protected function gmdatestr($time = null)
    {
      if (is_null($time)) {
        $time = time();
      }

      return gmdate("D, d M Y H:i:s", $time) . " GMT";
    }
    /**
     * @return int|mixed
     */
    protected function filesmtime()
    {
      static $filesmtime;
      if ($filesmtime) {
        return $filesmtime;
      }
      foreach ($this->files as $file) {
        if (!file_exists($file)) {
          $this->debugExit("File not found ($file).");
        }
        $filesmtime = max(filemtime($file), $filesmtime);
      }

      return $filesmtime;
    }
    /**

     */
    public function __construct()
    {
      //  $this->setFromArray(Config::getAll('assets22'));
      list($query) = explode('?', urldecode($_SERVER['QUERY_STRING']));
      if (preg_match('/^\/?(.+\/)?(.+)$/', $query, $matchResult)) {
        $fileNames = $matchResult[2];
        $fileDir   = $this->baseDir . $matchResult[1];
      } else {
        $this->debugExit("Invalid file name ($query)");
      }
      if ($this->concatenate) {
        $this->files       = explode('&', $fileNames);
        $this->files       = explode($this->separator, $this->files[0]);
        $this->concatenate = count($this->files) > 1;
      } else {
        $this->files = array($fileNames);
      }
      foreach ($this->files as $key => $file) {
        if (preg_match('/^[^\x00]+\.([a-z0-9]+)$/i', $file, $matchResult)) {
          $fileTypes[] = strtolower($matchResult[1]);
        } else {
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
      if (!headers_sent($file, $log)) {
        header("Content-Type: {$this->mimeTypes[$this->fileType]}; charset=" . $this->charSet);
      }
      $this->gzip = ($this->gzip && !in_array($this->fileType, $this->gzipExceptions) && in_array('gzip', array_map('trim', explode(',', @$_SERVER['HTTP_ACCEPT_ENCODING']))) && function_exists('gzencode'));
      if ($this->gzip) {
        header("Content-Encoding: gzip");
      }
      $this->minify      = $this->minify && class_exists($this->minifyTypes[$this->fileType]['minifier']);
      $this->serverCache = $this->serverCache && ($this->minify || $this->gzip || $this->concatenate);
      if ($this->serverCache) {
        $cachedFile = $this->cacheDir . DIRECTORY_SEPARATOR . $this->cachePrefix . md5($query) . '.' . $this->fileType . ($this->gzip ?
          '.gz' : '');
      }
      $generateContent = ((!$this->serverCache && (!$this->clientCache || !$this->clientCacheCheck || !isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || $_SERVER['HTTP_IF_MODIFIED_SINCE'] != $this->gmdatestr($this->filesmtime()))) || ($this->serverCache && (!file_exists($cachedFile) || ($this->serverCacheCheck && $this->filesmtime() > filemtime($cachedFile)))));
      if ($this->clientCache && $this->clientCacheCheck) {
        if ($this->serverCache && !$generateContent) {
          $mtime = filemtime($cachedFile);
        } elseif ($this->serverCache) {
          $mtime = time();
        } else {
          $mtime = $this->filesmtime();
        }
        $mtimestr = $this->gmdatestr($mtime);
      }
      if (!$this->clientCache || !$this->clientCacheCheck || !isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || $_SERVER['HTTP_IF_MODIFIED_SINCE'] != $mtimestr) {
        if ($this->clientCache && $this->clientCacheCheck) {
          header("Last-Modified: " . $mtimestr);
          header("Expires: " . $this->gmdatestr(time() + 315360000));
          header("Vary: Accept-Encoding", false);
          header("Cache-Control: must-revalidate");
        } elseif ($this->clientCache) {
          $this->headerNeverExpire();
        } else {
          $this->headerNoCache();
        }
        if ($generateContent) {
          $content = array();
          foreach ($this->files as $file) {
            (($content[] = file_get_contents($file)) !== false) || $this->debugExit("File not found ($file).");
          }
          $content = implode("\n", $content);
          if (isset($this->minifyTypes[$this->fileType])) {
            $minify_type_settings = $this->minifyTypes[$this->fileType];
            if (isset($minify_type_settings['minify']) && $minify_type_settings['minify']) {
              if (!isset($minify_type_settings['minifier'])) {
                $this->debugExit("Minifier not set for type " . $this->fileType);
              }
              $minifier_class                   = $minify_type_settings['minifier'];
              $minify_type_settings['settings'] = $minify_type_settings['settings'] ? : array();
              $minifier                         = new $minifier_class($content, array(
                'fileDir'             => $fileDir,
                'minify_type_settings'=> $minify_type_settings['settings'],
                'mimeTypes'           => $this->mimeTypes
              ));
              $content                          = $minifier->minify();
            }
          }
          if ($this->gzip) {
            $content = gzencode($content, $this->compressionLevel);
          }
          if ($this->serverCache) {
            {
              $handle = fopen($cachedFile, 'w');
              fwrite($handle, $content);
              fclose($handle);
            }
          }
          header('Content-Length: ' . strlen($content));
          echo $content;
        } else {
          if (!headers_sent($file, $log)) {
            header('Content-Length: ' . filesize($cachedFile));
          }
          readfile($cachedFile);
        }
      } else {
        $this->headerExit('304 Not Modified');
      }
    }
    protected function contentHeader()
    {
      if (isset($this->mimeTypes[$this->fileType])) {
        header('Content-Type: ' . $this->mimeTypes[$this->fileType]);
      }
    }
  }
