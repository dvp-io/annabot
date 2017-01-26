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
namespace Core;

use Core\Config as Config;
use Core\Annabot as Annabot;
use Core\Logger as Logger;

class Modules {

  public static function loadAnomods(Annabot $bot) /*: null*/ {

    $cfg = Config::getInstance();
    $logger = new Logger();

    $logPrefix = '[Anomods]';
    $modsList = $cfg->get('anomods');
    $modsPath = $cfg->get('anomodsPath');

    if(!is_dir($modsPath)) {
      $logger->writeLog($logPrefix.' Modules directory not found! `'.$modsPath.'`', 'e');
      return;
    }

    if(!$modsList || empty(trim($modsList))) {
      $logger->writeLog($logPrefix.' No module to load!', 'n');
      return;
    }

    self::__load($bot, $logPrefix, $modsPath, $modsList);
  }

  public static function loadAnnamods(Annabot $bot) /*: null*/ {

    $cfg = Config::getInstance();
    $logger = new Logger();

    $logPrefix = '[Annamods]';
    $modsList = $cfg->get('annamods');
    $modsPath = $cfg->get('annamodsPath');

    if(!is_dir($modsPath)) {
      $logger->writeLog($logPrefix.' Modules directory not found! `'.$modsPath.'`', 'e');
      return;
    }

    if(!$modsList || empty(trim($modsList))) {
      $logger->writeLog($logPrefix.' No module to load!', 'n');
      return;
    }

    self::__load($bot, $logPrefix, $modsPath, $modsList);
  }

  private static function __load(Annabot $bot, string $logPrefix, string $modsPath, string $modsList) /*: null*/ {

    $cfg = Config::getInstance();
    $logger = new Logger();

    // On liste les dossiers des modules
    $modsDir = scandir($modsPath);

    // On split la liste des modules
    $modsList = explode(' ', $modsList);

    // Si on à des modules à charger
    $logger->writeLog($logPrefix.' '.count($modsList).' module(s) to load', 'n');

    // On match les modules invoqués avec la liste des modules dispos dans l'arbo
    $modsEnabled = array_intersect(array_map('strtolower', $modsDir), array_map('strtolower', $modsList));

    // Si le nombre de module diffère
    foreach(array_diff($modsList, $modsEnabled) as $deadMod) {

      // On indique les modules inaccessibles
      $logger->writeLog($logPrefix.' "'.$deadMod.'" missing module!', 'e');
    }

    // On boucle sur les modules reconnus lors du premier test
    foreach($modsEnabled as $key => $badName) {

      $modName = $modsDir[$key];

      // On établi le namespace
      $ns = "\\Modules\\$modName\\$modName";

      // Si on est sur les Anomods
      if($logPrefix == '[Anomods]') {
        $ns = "\\Anomods\\$modName\\$modName";
      }

      // Si la classe n'existe pas
      if(!class_exists($ns)) {
        $logger->writeLog($logPrefix.' "'.$modName.'" not found','e');
        continue;
      }

      $mod = new $ns();

      // Si le module est inactif
      if(!$mod::isRunnable()) {

        if(!$cfg->get('chatDev')) {

          $logger->writeLog($logPrefix.' "'.$modName.'" is not runnable, change the module configuration or enable debug mode','e');
          continue;

        } else {

          $logger->printLog($logPrefix.' "'.$modName.'" start in development mode','w');
        }

      }

      $mod->__load($bot);
      $logger->printLog($logPrefix.' "'.$modName.'" loaded','s');
    }

  }

}
