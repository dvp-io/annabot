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

use Core\Config as Config;
use Core\Logger as Logger;

trait EventEmitter {

  protected $_listeners = [];

  public function on($event, callable $listener) {

    if(!is_array($event)) {
      $event = [$event];
    }

    foreach($event as $e) {

      if(empty($this->_listeners[$e])) {
        $this->_listeners[$e] = [];
      }

      $this->_listeners[$e][] = $listener;
    }

    return $this;
  }

  public function once($event, callable $listener) {

    $onceListener = function ()

      use (&$onceListener, $event, $listener) {

        $this->removeListener($event, $onceListener);

        $listener(...func_get_args());

      };


    return $this->on($event, $onceListener);
  }

  public function removeListener($event, callable $listener) {

    if(empty($this->_listeners[$event])) {

      return $this;
    }

    $index = array_search($listener, $this->_listeners[$event], true);

    if(false !== $index) {
      unset($this->_listeners[$event][$index]);
    }

    return $this;
  }

  public function removeAllListeners($event = null) {

    $listeners = $this->_listeners;

    if(is_null($event)) {

      $events = array_keys($listeners);

    } else {

      $events = [$event];

    }

    foreach($events as $name) {

      if(empty($listeners[$name])) {

        continue;

      }


      foreach($listeners[$name] as $listener) {

        $this->removeListener($name, $listener);

      }

    }

    return $this;
  }

  public function listeners($event) {

    return $this->_listeners[$event] ?? [];
  }

  public function emit($event, ...$arguments) {

    $cfg = Config::getInstance();
    $logger = new Logger();

    if($event !== 'newEvent') {

      // Si on est pas sur un event natif
      if(is_string($arguments[count($arguments) -1]) && $arguments[count($arguments) -1] === 'ee:prefix') {

        unset($arguments[count($arguments) -1]);

      } else {

        $event = ':'.$event;

      }

      // Si le debug des events est actif
      if($cfg->get('debugEvents')) {

        if(
          (($cfg->get('debugSkipCompletion') && $event != 'completion')  || !$cfg->get('debugSkipCompletion')) &&
          (($cfg->get('debugSkipUpdate') && $event != 'self.update') || !$cfg->get('debugSkipUpdate'))
        ) {

          $logger->printLog('Event '.$event.' emitted', 's');


          if($cfg->get('debugPretty')) {

            $logger->printLog(json_encode($arguments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_PRETTY_PRINT), 'n');

          } else {

            $logger->printLog(json_encode($arguments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION), 'n');

          }

        }

      }

    }

    foreach ($this->listeners($event) as $listener) {

      $listener->call($this, ...$arguments);

    }

    return $this;
  }

}
