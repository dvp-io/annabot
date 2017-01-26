<?php
/**
 * # The MIT License (MIT)
 *
 * Copyright (c) 2015-2017 Antoine Pous <gecko@dvp.io>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Core;

use Core\Config as Config;

class Logger {

  protected static $debug;
  protected static $types = ['n' => 'info', 'e' => 'error', 'w' => 'warning', 's' => 'success', 'z' => '', 'd' => 'deprecated'];

  public function __construct($debug = false) {
    $cfg = Config::getInstance();
    static::$debug = ($cfg->get('debugLogs') || $debug);
  }

  /*
   n = normal // white
   e = error // red
   s = success // green
   w = warn // yellow
  */
  public function printLog($msg, $type = 'n') {

    if(!array_key_exists($type, static::$types)) {
      throw new \Exception('Type of data log is wrong');
    }

    $cfg = Config::getInstance();

    switch($type) {
      case 'd':
        $msg = "\033[33m**DEPRECATED** $msg\033[37m";
        break;
      case 'e':
        $msg = "\033[31m**ERROR** $msg\033[37m";
        break;
      case 's':
        $msg = "\033[32m**OK** $msg\033[37m";
        break;
      case 'w':
        $msg = "\033[33m**WARN** $msg\033[37m";
        break;
      case 'z':
        $msg = "\033[34m$msg\033[37m";
    }

    echo "$msg\r\n";
  }

  public function writeLog($msg, $type) {

    if(!array_key_exists($type, static::$types)) {
      throw new \Exception('Type of data log is wrong');
    }

    $cfg = Config::getInstance();

    if(static::$debug) {
      $this->printLog($msg, $type);
    }

    $logEntry = '['.date('D M d H:i:s Y').'] ['.static::$types[$type].'] '.$msg."\r\n";
    $logPath = $cfg->get('rootPath').'/Logs/';
    $logFile = date('Y\_m\_d').'_'.$cfg->get('username').'.log';

    if(!is_writable($logPath)) {
      $this->printLog('Cannot write logs in '.$cfg->get('rootPath').'/Logs/','e');
      $this->printLog('Exiting bot instance','e');
      exit;
    }

    if($fp = fopen($logPath.$logFile,'a')) {
      fwrite($fp, $logEntry);
      fclose($fp);
    }

  }
}
