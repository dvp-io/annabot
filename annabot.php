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
$opts = getopt("a:m:p:r:u:", ["pretty-debug::", "debug-logs::", "dev::", "debug-events::", "debug-queries::", "skip-completion::", "skip-update::"]);
$pid = getmypid();
$uid = getmyuid();
$gid = getmygid();
$rootPath = __DIR__;

// Paramètres requis
if((!$opts || empty($opts)) || !isset($opts['m'], $opts['p'], $opts['r'], $opts['u'])) {
  echo file_get_contents($rootPath.'/README');
  exit;
}

require_once $rootPath.'/Includes/Mapping.php';
require_once $rootPath.'/Includes/Config.inc.php';

// On map les dossiers des modules
$loader->addNamespace('Anomods', $cfg->get('anomodsPath'));
$loader->addNamespace('Modules', $cfg->get('annamodsPath'));

// Log toutes les exceptions
set_exception_handler(function($e) {
  $cfg = Core\Config::getInstance();

  $logger = new Core\Logger();
  $logger->writeLog($e->getMessage(). ' in '.$e->getFile().':'.$e->getLine(), 'e');

  // Si GitHub est configuré et que le report est actif
  // TODO envoyer le log à Core/GitHub::Reporting

  posix_kill(posix_getpid(), SIGTERM);
});

use Core\Logger   as Logger;
use Core\Annabot  as Annabot;
use Core\Modules  as Modules;
use Core\GitHub   as GitHub;

$_anomods = !empty($opts['a']) ? $opts['a'] : 'n/a';
$_annamods = !empty($opts['m']) ? $opts['m'] : 'n/a';

$logger = new Logger($cfg->get('debugLogs'));
$logger->printLog('                             ____        _   ','n');
$logger->printLog('     /\                     |  _ \      | |  ','n');
$logger->printLog('    /  \   _ __  _ __   __ _| |_) | ___ | |_ ','n');
$logger->printLog('   / /\ \ | \'_ \| \'_ \ / _` |  _ < / _ \| __|','n');
$logger->printLog('  / ____ \| | | | | | | (_| | |_) | (_) | |_ ','n');
$logger->printLog(' /_/    \_\_| |_|_| |_|\__,_|____/ \___/ \__|','n');
$logger->printLog('                  v'.$cfg->get('botVersion'),'n');
$logger->printLog(' Close source project by Antoine `Gecko` Pous');
$logger->printLog('','n');
$logger->writeLog('Starting new bot instance [Account: '.$opts['u'].'] [Room: '.$opts['r'].'] [Anomods: '.$_anomods.'] [Annamods: '.$_annamods.'] [Chat version: '.$cfg->get('chatVersion').']','n');

if($cfg->get('chatDev')) {
  $logger->printLog('Dev mode is enabled','w');
}

if($cfg->get('debugLogs')) {
  $logger->printLog('Debug mode is enabled, sensitive data will be exposed!','w');
} else {
  $logger->printLog('Starting new bot instance [Account: '.$opts['u'].'] [Room: '.$opts['r'].'] [Anomods: '.$_anomods.'] [Annamods: '.$_annamods.'] [Chat version: '.$cfg->get('chatVersion').']','n');
}

// On créé l'instance pour l'utilisation de l'API GitHub
$GitHub = new GitHub;

// On charge le client
$bot = new Annabot();

// On log le bot sur le chat
$bot->connect();

// On déclare les cronjob par défaut
$bot->registerCron('yearly',  '@yearly',  'ee:prefix');
$bot->registerCron('monthly', '@monthly', 'ee:prefix');
$bot->registerCron('weekly',  '@weekly',  'ee:prefix');
$bot->registerCron('daily',   '@daily',   'ee:prefix');
$bot->registerCron('hourly',  '@hourly',  'ee:prefix');

// On enregistre la commande /MAN qui deviens la commande d'obtention de l'aide
$bot->registerCmd('man', Annabot::LEVEL_MEMBER, 'x_x');

// On charge les modules natifs
Modules::loadAnomods($bot);

// On charge les module tiers
Modules::loadAnnamods($bot);

// On test si le bot est bien connecté
if($bot->isOnline()) {
  $logger->writeLog('Bot online [PID: '.getmypid().'] [UID: '.getmyuid().'] [GID: '.getmygid().']', 's');

  if(!$cfg->get('debugLogs')) {
    $logger->printLog('Bot online [PID: '.getmypid().'] [UID: '.getmyuid().'] [GID: '.getmygid().']', 's');
  }

  if(!$fp = fopen($rootPath.'/PID/'.$pid, 'w+')) {
    $logger->writeLog('Cannot write PID file for '.$opts['u'], 'e');
  } else {
    fwrite($fp, $opts['u']);
    fclose($fp);
  }
} else {
  $logger->writeLog('Bot offline: '.$bot->getLastError(), 'e');
  exit(1);
}

declare(ticks = 1);
$sigint = function($signo) use($pid, $bot, $logger, $opts, $rootPath) {
  unlink($rootPath.'/PID/'.$pid);
  $bot->shutdown();
};

pcntl_signal(SIGINT, $sigint);
pcntl_signal(SIGTERM, $sigint);

stream_set_blocking(STDIN, 0);
stream_set_timeout(STDIN, 0, 1);

$sd = Core\SharedData::getInstance();

while(true) {

  // Ecoute de la ligne de commande
  $clicmd = trim(fgets(STDIN));

  // Si on reçois quelque chose
  if(!empty($clicmd)) {

    // On l'envoie tel quel au serveur
    $bot->sendCmd($clicmd);

    // On affiche le retour console
    $logger->printLog('Commande envoyée: '.$clicmd, 's');
  }

  // On check les mises à jour
  $GitHub->checkForUpdate($bot);

  // Le bot interroge le serveur
  $bot->update();

  // Refresh toutes les 0.(n)s
  usleep($cfg->get('refreshInterval') * 100000);
}
