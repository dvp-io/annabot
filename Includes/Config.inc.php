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
 * Fichier de configuration
 */
$cfg = Core\Config::getInstance();

/**
 * Configuration des logs
 */
$cfg->set('debugLogs',              isset($opts['debug-logs']));
$cfg->set('debugEvents',            isset($opts['debug-events']));
$cfg->set('debugQueries',           isset($opts['debug-queries']));
$cfg->set('debugPretty',            isset($opts['pretty-debug']));
$cfg->set('debugSkipCompletion',    isset($opts['skip-completion']));
$cfg->set('debugSkipUpdate',        isset($opts['skip-update']));

/**
 * Configuration de l'environnement
 */
$EnvConfig = $rootPath.'/Includes/Env.inc.php';
if(file_exists($EnvConfig)) {
  include_once $EnvConfig;
} else {
  throw new Exception('Missing Includes/Env.inc.php');
}

/**
 * Configuration de Redis
 */
$RedisConfig = $rootPath.'/Includes/Redis.inc.php';
if(file_exists($RedisConfig)) {
  include_once $RedisConfig;
} else {
  throw new Exception('Missing Includes/Redis.inc.php');
}

/**
 * Configuration de GitHub
 */
$GitHubConfig = $rootPath.'/Includes/GitHub.inc.php';
if(file_exists($GitHubConfig)) {
  include_once $GitHubConfig;
}

/**
 * Configuration interne
 * Aucune modification n'est nécessaire pour créer une instance
 * Se référer aux paramètres CLI disponibles dans Docs/man.txt
 */
$cfg->set('rootPath',               $rootPath);
$cfg->set('botVersion',             '2.0.0-beta.8');
$cfg->set('chatDev',                isset($opts['dev']));
$cfg->set('chatVersion',            isset($opts['dev']) ? $chatDevVersion : $chatProdVersion);
$cfg->set('useragent',              'AnnaBot '.$cfg->get('botVersion').' - Anomods: '.(!empty($opts['a']) ? $opts['a'] : 'n/a').' - Annamods: '.(!empty($opts['m']) ? $opts['m'] : 'n/a'));

$cfg->set('anomods',                $opts['a']);
$cfg->set('annamods',               $opts['m']);
$cfg->set('username',               $opts['u']);
$cfg->set('password',               $opts['p']);
$cfg->set('roomid',                 $opts['r']);
$cfg->set('host',                   isset($opts['dev']) ? $chatDevHost : $chatProdHost);
$cfg->set('modes',                  '');
