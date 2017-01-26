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

/**
 * CLASSE INCOMPLETE
 */
namespace Core;

use \Exception as Exception;
use \Redis as Redis;

class SharedData {

  protected static $_instance;

  public static function getInstance() : Redis {

    if(!static::$_instance) {

      $cfg = Config::getInstance();
      $logger = new Logger();

      static::$_instance = new Redis();

      static::$_instance->pconnect($cfg->get('Redis_Host'), $cfg->get('Redis_Port'));

      $infos = static::$_instance->info();

      $logger->writeLog('Connected to Redis server v'.$infos['redis_version'], 's');

      if(!empty($cfg->get('Redis_Auth'))) {
        static::$_instance->auth($cfg->get('Redis_Auth'));
      }

      static::$_instance->setOption('name', 'Annabot-'.$cfg->get('username'));

      static::$_instance->select($cfg->get('Redis_Db'));

    }

    return static::$_instance;
  }

}
