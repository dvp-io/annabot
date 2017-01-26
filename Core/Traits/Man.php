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

declare(strict_types = 1);
namespace Core\Traits;

use Core\Config as Config;
use Core\Logger as Logger;
use \Exception as Exception;

trait Man {

  protected $manList = [];

  /**
   * Déclare un man auprès du bang !man, les manuels doivent faire référence aux !bangs
   *
   * @param string $bangName Nom du bang concerné
   * @return true Succès
   */
  public function registerMan(string $bangName, int $level) {

    $cfg = Config::getInstance();
    $logger = new Logger($cfg->get('debugLogs'));

    $trace = debug_backtrace();
    $modulePath = pathinfo($trace[1]['file'], PATHINFO_DIRNAME);
    $manFile = $modulePath.'/Man/'.$bangName.'.txt';

    // Si on déclare le man pour !man
    if($bangName == 'man') {
      $manFile = $cfg->get('rootPath').'/Misc/Man.txt';
    }

    if(empty(trim($bangName))) {
      throw new Exception('Bang name cannot be empty in '.$trace[1]['file'].' line '.$trace[1]['line']);
    }

    if(isset($this->manList[$bangName])) {
      throw new Exception($bangName.' already have manual in '.$this->manList[$bangName]);
    }

    if(!file_exists($manFile)) {
      throw new Exception('Manual for '.$bangName.' ('.$manFile.') not found!');
    }

    if($cfg->get('debugEvents')) { $logger->printLog('Manual for '.$bangName.' registered ('.$manFile.')', 's'); }
    $this->manList[$bangName] = $manFile;

    return true;
  }

  /**
   * Récupère le manuel du bang
   *
   * @param string $bangName Nom du bang concerné
   */
  protected function getMan(string $bangName) {

    $cfg = Config::getInstance();
    $logger = new Logger($cfg->get('debugLogs'));

    if(!isset($this->manList[$bangName])) {
      return false;
    }

    return file_get_contents($this->manList[$bangName]);
  }

  /**
   * Parse les manuels et injecte les informations relatives aux clients/salons/bots
   *
   * @param string $bangName Nom du bang pour lequel on veut le manuel
   * @param array $invoker Utilisateur appelant le manuel
   * @param array $bot Compte utilisé par l'instance actuelle
   * @param array $room Salon de l'utilisateur
   * @param array $botRoom Salon du bot
   */
  public function parseMan(string $bangName, array $invoker, array $bot, array $room) {

    if(!$man = $this->getMan($bangName)) {
      return false;
    }




  }

}
