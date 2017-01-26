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

namespace Core\Traits;

use Cron\CronExpression as Cron;
use Core\Config as Config;
use Core\Logger as Logger;

trait Cronjob {

  protected $cronJob = [];

  public function registerCron($name, $schedule, $default = null) {
    $cfg = Config::getInstance();
    $logger = new Logger();

    if(isset($this->cronJob[$name])) {
      $logger->writeLog('CronJob "'.$name.'" is already registered');
      return false;
    }

    if(!Cron::isValidExpression($schedule)) {
      throw new Exception('Expression for '.$name.' ('.$schedule.') is not valid');
    }

    $this->cronJob[$name] = ['schedule' => $schedule, 'runTime' => 0, 'prefix' => $default];
    $logger->writeLog('CronJob '.$name.' ('.$schedule.') registered', 's');
    return true;
  }

  protected function emitCron() {

    $cfg = Config::getInstance();

    $logger = new Logger();

    foreach($this->cronJob as $name => $job) {

      $cron = Cron::factory($job['schedule']);

      $time = time();

      if($cron->isDue() && $job['runTime'] != $time) {

        $this->cronJob[$name]['runTime'] = $time;

        $next = $cron->getNextRunDate()->format('Y-m-d H:i:s') ? $cron->getNextRunDate()->format('Y-m-d H:i:s') : null;

        if($job['prefix'] == 'ee:prefix') {
          $this->emit('cron.'.$name, ['next' => $next], 'ee:prefix');
          continue;
        }

        $this->emit('cron.'.$name, ['next' => $next]);
      }

    }
  }

}
