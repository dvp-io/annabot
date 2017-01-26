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
use Core\Logger as Logger;
use Core\Commands\Parser as CMDParser;

class Annabot {

  use Traits\EventEmitter;
  use Traits\Cronjob;
  use Traits\Man;
  use Traits\Bang;

  use Traits\Uploads;
  use CMDParser;

  const LEVEL_ADMIN     = 4;
  const LEVEL_MODERATOR = 3;
  const LEVEL_BOT       = 2;
  const LEVEL_MEMBER    = 1;

  const RULES_FR            = 1;
  const RULES_SMS           = 2;
  const RULES_BADWORDS      = 3;
  const RULES_INSULTS       = 4;
  const RULES_FLOOD         = 5;
  const RULES_PM            = 6;
  const RULES_REGLIGION     = 7;
  const RULES_LAW           = 8;
  const RULES_CONTESTATION  = 9;
  const RULES_AMBIANCE      = 10;

  const BAN_QUIET_ENABLED = true;
  const BAN_QUIET_DISABLED = false;
  const BAN_DURATION_DEFAULT = '';
  const BAN_DURATION_HOURS = 'H';
  const BAN_DURATION_DAYS = 'J';

  protected $_debug;
  protected $_session;
  protected $_host;
  protected $_useragent;
  protected $_counter = 0;
  protected $_version;
  protected $_event;

  protected static $lastError = [];
  protected static $botInfos = [];
  protected static $userList = [];
  protected static $roomList = [];

  protected static $isOnline = false;

  /**
   * Connect
   * Connecte le client au serveur et retourne l'état
   */
  public function connect($mode = 1) {

    $cfg = Config::getinstance();

    $this->_version = $cfg->get('chatVersion');
    $this->_useragent = $cfg->get('useragent');
    $this->_host = $cfg->get('host');
    $this->_debug = $cfg->get('debugLogs');
    $this->_pseudo = $cfg->get('username');

    if(strlen($cfg->get('password')) !== 32) {
      $mode = 0;
    }

    return $this->send([
      'q' => 'conn',
      'v' => $this->_version,
      'identifiant' => $cfg->get('username'),
      'motdepasse' => $cfg->get('password'),
      'decalageHoraire' => date('O') * -6 / 10,
      'options' => 'I',
      'mode' => $mode,
      'salon' => $cfg->get('roomid')
    ]);

  }

  public function reconnect() {
    return $this->connect(2);
  }

  public function update() {
    $this->send(array('q' => 'act'));
    $this->emit('self.update', ['counter' => $this->_counter], 'ee:prefix');
  }

  public function sendCmd($command) {
    return $this->send(array('q' => 'cmd', 'c' => $command));
  }


  private function post(array $data) {

    // On récupère la configuration
    $cfg = Config::getInstance();

    // On instancie le gestionnaire de logs
    $logger = new Logger();

    // On indique la version du chat
    $data['v'] = $this->_version;

    // Si on envoie une commande ou une actualisation
    if(in_array($data['q'], ['cmd','act'])) {

      // On ajoute la session aux données
      $data['s'] = $this->_session;

      // On incrémente le compteur de requêtes et on l'ajoute
      $data['a'] = $this->_counter++;
    }

    // Si le mode debug est actif
    if($cfg->get('debugQueries')) {

      $logger->printLog('Query #'.$data['a'].' sent', 's');

      if($cfg->get('debugPretty')) {

        $logger->printLog(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_PRETTY_PRINT));

      } else {

        $logger->printLog(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));

      }

    }

    // Remplacer par cURL ? Non prioritaire
    $host = parse_url($this->_host);
    if(empty($host['scheme'])) {
      $this->_host = 'http://'.$this->_host;
    }

    $ch = curl_init();

    if(!$ch) {
      $logger->writeLog('Could not initialize cURL Handler', 'e');
      return;
    }

    curl_setopt($ch, CURLOPT_URL,             $this->_host.'/ajax.php');
    curl_setopt($ch, CURLOPT_POST,            true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,      $data);
    curl_setopt($ch, CURLOPT_USERAGENT,       $this->_useragent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,  true);
    curl_setopt($ch, CURLOPT_AUTOREFERER,     true);
    curl_setopt($ch, CURLOPT_FORBID_REUSE,    true);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT,   true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,  false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  false);

    // On récupère les résultats de la requête
    $result = json_decode(curl_exec($ch));

    // On récupère le code HTTP
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if(0 != $errno = curl_errno($ch)) {
      $logger->writeLog('Client request fail, cURL '.$errno.': '.curl_error($ch), 'e');
      return;
    }

    curl_close($ch);

    if($status !== 200) {
      $logger->writeLog('Client request fail, HTTP code: '.$status, 'e');
    }

    if(!is_object($result)) {
	   $logger->writeLog('Data receiveid are not valid, '.gettype($result).', object expected','e');
		return;
    }

	 return $result;
  }


  private function send($data) {

    $cfg = Config::getInstance();
    $logger = new Logger();

    $result = $this->post($data);

    if($result == null) {
		return;
    }

    if($result->etat === -1) {
      $logger->writeLog('Session expirée côté serveur, reconnexion','w');
      $this->reconnect();
      return;
    }

    if($result->etat === 0) {

      $logger->writeLog('Client disconnected, reason: '.$result->message, 'w');

      if($cfg->get('autoReconnect') && $result->message != 'Vous avez quitté le Chat.') {
        $this->reconnect();
        return;
      }

      exit(0);
    }

    if($result->etat === 2) {
      static::$isOnline = true;
      $this->_session = $result->session;
    }

    // Extraction des données et emission des évènements
    $DOMParser = new JSONParser($this);
    $DOMParser->extractData($result, $this->_counter, $this->_session);

    $this->emitCron();
  }



	static private function decoder($phrase) {
		return trim(strip_tags(str_replace('<span class="horodatage">', "\n" . '<span class="horodatage">', $phrase)));
	}

	private function erreur($fonction, $message) {
		//echo 'Erreur de ' . $this->_pseudo . ' dans Serveur.' . $fonction . '(): ' . $message . "\n";
	}

  public static function isOnline() {
    return static::$isOnline;
  }

  public function shutdown() {
    $cfg = Config::getInstance();
    $logger = new Logger();
    $msg = '';

    // Si les /QUIT custom sont activés
    if($cfg->get('customQuit')) {

      $filePath = $cfg->get('rootPath').'/Misc/Quit.txt';

      if(file_exists($filePath) && is_readable($filePath)) {

        // On lis le fichier contenant ces infos
        $data = file($filePath);

        if(!empty($data)) {

          // On prend un message au pif
          $msg = $data[rand(0, count($data) -1)];
        }
      }
    }

    $this->emit('self.shutdown', [], 'ee:prefix');
    $this->quit($msg);
    $logger->writeLog('Client disconnected', 's');
  }


  public function whoami() {
    return (object) self::$botInfos;
  }

  public function getLastError() {
    return (object) self::$lastError;
  }

  public function getUserByID(int $userID) : \stdClass {

    foreach(self::$userList as $user) {

      if($user['id'] == $userID) {

        return (object) $user;

      }

    }

    return (object) [];
  }

  public function getRoomByID(int $roomID) : \stdClass {

    foreach(self::$roomList as $room) {

      if($room['id'] == $roomID) {

        return (object) $room;

      }

    }

    return (object) [];
  }

  public function getUsersByRoomID(int $roomID) : \stdClass {

    $users = [];

    foreach(self::$userList as $user) {

      if($user['room'] === $roomID) {

        $users[] = $user;

      }

    }

    return (object) $users;
  }

  public function logError(string $error) {
    $logger = new Logger();
    $this->writeLog($message, 'e');
  }

  public function setUserList(array $userList) {
    self::$userList = $userList;
  }

  public function setRoomList(array $roomList) {
    self::$roomList = $roomList;
  }

  public function setBotInfos(array $botInfos) {
    self::$botInfos = $botInfos;
  }
}
