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
use Core\Annabot as Annabot;

class GitHub {

  private function callAPI($uri) {
    $cfg = Config::getInstance();

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $cfg->get('GitHub_APILogin'));
    curl_setopt($ch, CURLOPT_USERPWD, $cfg->get('GitHub_APILogin').':'.$cfg->get('GitHub_APIToken'));

    $data = curl_exec($ch);

    curl_close($ch);

    return json_decode($data, false);
  }

  public function CheckForUpdate(Annabot $bot) {

    $cfg = Config::getInstance();

    // Si la détection de nouvelles versions est active, que la dernière vérification et qu'il est l'heure voulue
    if($cfg->get('GitHub_CFUEnabled')) {

      // Si le TTL est dépassé et que l'heure correspond
      if(($this->getLastCFU() + $cfg->get('GitHub_CFUInterval')) < time() && $cfg->get('GitHub_CFUTime') == date('H:i')) {

        // On récupère les PID actifs
        $PIDs = scandir($cfg->get('rootPath').'/PID/');

        // Si cette bottine est celle qui à le PID le plus petit c'est elle qui à le droit de beugler (évite les doublons)
        if(!empty($PIDs[3]) && $PIDs[3] == getmypid()) {

          // Si les credentials GitHub sont définis
          if(!empty($cfg->get('GitHub_APILogin')) && !empty($cfg->get('GitHub_APIToken'))) {

            // On récupère les infos sur la dernière version du bot
            $latest = self::callAPI($cfg->get('GitHub_API'));

            // Si la version du repo est supérieure à la version actuelle
            if(!empty($latest->tag_name) && version_compare($latest->tag_name, $cfg->get('botVersion'), '>')) {

              // On averti l'@ de la nouvelle version
              $bot->alert('[B][COLOR=#FF3399][Annabot v'.$cfg->get('botVersion').']:[/COLOR][/B] La version [COLOR=#459042]'.$latest->tag_name.'[/COLOR] est disponible depuis le '.date('d/m/Y', strtotime($latest->published_at)).'!');

              if(!empty($cfg->get('GitHub_CFUReporting')) && is_array($cfg->get('GitHub_CFUReporting'))) {

                foreach($cfg->get('GitHub_CFUReporting') as $i => $uid) {

                  $bot->tell($uid, '[B][COLOR=#FF3399][Annabot v'.$cfg->get('botVersion').']:[/COLOR][/B] La version [COLOR=#459042]'.$latest->tag_name.'[/COLOR] est disponible depuis le '.date('d/m/Y', strtotime($latest->published_at)).'!');
                  $bot->tell($uid, '[B][COLOR=#FF3399][Annabot v'.$cfg->get('botVersion').']:[/COLOR][/B] [URL=https://github.com/Antoine-Pous/dvp-annabot/archive/'.$latest->tag_name.'.tar.gz]Télécharger l\'archive TAR GZ[/URL]');

                }

              }

              $this->updateLastCFU();
            }
          }
        }
      }
    }
  }

  private function updateLastCFU() {

    $cfg = Config::getInstance();

    $file = $cfg->get('rootPath').'/Cache/.cfu';

    $fp = fopen($file, 'w');

    fwrite($fp, time());

    fclose($fp);
  }

  private function getLastCFU() {

    $cfg = Config::getInstance();
    $logger = new Logger();

    // Path du fichier
    $file = $cfg->get('rootPath').'/Cache/.cfu';

    // Retour par défaut
    $lastCFU = 0;

    if(!file_exists($file)) {
      $logger->writeLog('Missing CFU file, try to generate it!', 'w');
    }

    $fp = fopen($file, 'r+');

    // Si la ressource est pas disponible
    if(!$fp) {

      $logger->writeLog('CFU file cannot be read, written or created, please check pemissions for '.$file, 'e');

      $this->updateLastCFU();

      // On retourne le timestamp actuel pour pas être emmerdé avec un éventuel flood en cas de fail
      return time();
    }

    if(0 != $length = filesize($file)) {
      $lastCFU = (int) fread($fp, $length);
    }

    fclose($fp);

    return $lastCFU;
  }

}
