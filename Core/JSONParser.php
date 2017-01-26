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

use SimpleHTMLDOM\Helper as SHDOM;
use SimpleHTMLDOM\simple_html_dom_node as SHDOMN;
use Core\Logger as Logger;
use Core\Config as Config;

class JSONParser {

  const LEVEL_ADMIN     = 4;
  const LEVEL_MODERATOR = 3;
  const LEVEL_BOT       = 2;
  const LEVEL_MEMBER    = 1;

  private static $msgType = [
    'contenu'       => 'normal',
    'alerte'        => 'alert',
    'notification'  => 'notification',
    'notice'        => 'notice'
  ];

  private static $msgSubst = [
    'supprimé' => 'delete',
    'modéré'   => 'censored',
    'restauré' => 'restored'
  ];

  private static $userList  = [];
  private static $_userList = [];
  private static $roomList  = [];
  private static $_roomList = [];
  private static $botInfos  = [];
  private static $eventList = [];


  public function __construct($bot) {
    $this->bot = $bot;
    $cfg = Config::getInstance();
    $this->logger = new Logger();
  }

  /**
   * Extract data and start parsers
   * @param  object $data       [[Description]]
   * @param  integer $queryCount Iteration count
   * @since 2.0.0
   * @return array Bot infos
   */
  public function extractData($data, $queryCount, $session) {

    if($queryCount == 0) {

      // On préviens que quelque chose est pas encore dans le DOM
      $this->logger->printLog('Impossible de récupérer les descriptions des salons','w');

      // La connexion est établie, on commence à grab le 1er lot de données, on indique la connexion
      $this->prepareEvent('self.connected', [], 'ee:prefix');

    }

    // Si la liste des utilisateurs ou des salons à changée
    if(!empty($data->connectes))
      $this->updateTree($data->connectes, $queryCount);

    // Si la liste de completion à été mise à jour
    if(!empty($data->listeCompletion))
      $this->prepareEvent('completion', json_decode($data->listeCompletion), 'ee:prefix');

    // Si le bot reçois un message privé
    if(!empty($data->pvs))
      $this->parsePrivateMessage($data->pvs);

    // Si des messages sont envoyés dans le salon du bot
    if(!empty($data->salon))
      $this->parseRoomMessage($data->salon);

    // Si un message est supprimé, modéré ou restauré
    if(!empty($data->subst))
      $this->parseSubst($data->subst);

    if(!empty($data->message))
      $this->logger->writeLog($data->message, 'e');

    // Voir si Ano les implémente proprement
    /*if(!empty($data->bang))
      var_dump('Bang détecté');

    if(!empty($data->cmd))
      var_dump('Commande détectée');*/

    // Si le bot viens de se connecter et qu'il à grab le premier lot d'infos on indique qu'il est ready
    if($queryCount == 1) {
      $this->prepareEvent('self.ready', static::$botInfos, 'ee:prefix');
    }

    // On met à jour les données du bot
    $this->bot->setRoomList(static::$roomList);
    $this->bot->setUserList(static::$userList);
    $this->bot->setBotInfos(static::$botInfos);

    // On execute tous les events préparées
    $this->execEvent();
  }

  private function updateTree($html, $queryCount) {

    // On redéfini les listes
    static::$_userList = static::$userList;
    static::$userList = [];

    static::$_roomList = static::$roomList;
    static::$roomList = [];

    // Récupération de la configuration
    $cfg = Config::getInstance();

    // Parser HTML
    $shdom = new SHDOM();
    $_html = $shdom->loadFromFile($html);

    // On cherche tous les éléments `a`
    $el = $_html->find('a');

    $init = $queryCount == 0 ? true : false;

    // Ordre du salon
    $rorder = 0;

    // On parcourt tous les éléments
    foreach($el as $a) {

      // Si on est sur un salon, on extrait les infos
      if(empty($a->find('span'))) {

        preg_match("~ouvrirMenuSalon\(\"(.*)\",(\d),(\d),(\d),(\d),(\d),(\d),(\d),(\d)\)\;~i", $a->onclick, $roomInfos);
        preg_match("/\[(\d+)\]$/i", $a->plaintext, $roomCount);

        $roomID = (int) $a->{'data-id'};

        // ID du salon
        static::$roomList[$roomID]['id'] = $roomID;

        // Nom du salon (propre)
        static::$roomList[$roomID]['name'] = $this->decode($roomInfos[1]);

        // Nom du salon (autocompletion)
        static::$roomList[$roomID]['completion'] = str_replace(' ', '_', $this->decode($roomInfos[1]));

        // Description du salon (actuellement indisponible)
        static::$roomList[$roomID]['description'] = null;

        // Mode muet
        static::$roomList[$roomID]['mute'] = (bool) $roomInfos[7];

        // Etat /DICE
        static::$roomList[$roomID]['dice'] = (bool) $roomInfos[8];

        // Salon privé
        static::$roomList[$roomID]['private'] = (bool) $roomInfos[5];

        // Publié sur la page d'identification
        static::$roomList[$roomID]['published'] = (bool) $roomInfos[6];

        // Ordre
        static::$roomList[$roomID]['order'] = ++$rorder;

        // Nombre d'utilisateurs connectés dans ce salon
        static::$roomList[$roomID]['count'] = $roomCount[1];

        // On cherche les anciennes valeurs par l'auto-completion (mettre l'ID quand ce sera possible)
        if(!empty(static::$_roomList[$roomID])) {

          // Event modification des conditions d'accès (public/privé)
          // NB: Si le compte ne peut voir le salon il sera considéré comme détruit
          if(static::$roomList[$roomID]['private'] != static::$_roomList[$roomID]['private']) {
            $this->prepareEvent('room.private', static::$roomList[$roomID], 'ee:prefix');
          }

          // Salon (dé)muté
          if(static::$roomList[$roomID]['mute'] != static::$_roomList[$roomID]['mute']) {
            $this->prepareEvent('room.mute', static::$roomList[$roomID], 'ee:prefix');
          }

          // Salon (dé)publié
          if(static::$roomList[$roomID]['published'] != static::$_roomList[$roomID]['published']) {
            $this->prepareEvent('room.published', static::$roomList[$roomID], 'ee:prefix');
          }

          // (dés)activation du /DICE
          if(static::$roomList[$roomID]['dice'] != static::$_roomList[$roomID]['dice']) {
            $this->prepareEvent('room.dice', static::$roomList[$roomID], 'ee:prefix');
          }

          // Changement de la description (inerte actuellement)
          if(static::$roomList[$roomID]['description'] != static::$_roomList[$roomID]['description']) {
            $this->prepareEvent('room.description', array_merge(static::$roomList[$roomID], ['_description' => static::$_roomList[$roomID]['description']]), 'ee:prefix');
          }

          // Changement du nom du salon (inerte actuellement)
          if(static::$roomList[$roomID]['name'] != static::$_roomList[$roomID]['name']) {
            $this->prepareEvent('room.name', array_merge(static::$roomList[$roomID], ['_name' => static::$_roomList[$roomID]['name']]), 'ee:prefix');
          }

        } else {
          $eventSuffix = $init ? 'exists' : 'created';
          $this->prepareEvent('room.'.$eventSuffix, static::$roomList[$roomID], 'ee:prefix');
        }
      }

      // Si on est sur un utilisateur
      if(!empty($a->find('span'))) {

        $userInfos = explode(',',str_replace(['ouvrirMenuUtilisateur(',')','"'],'', $a->onclick));
        $userID = $userInfos[0];

        $eventPrefix = 'user';

        preg_match("~\((.*)\)$~i", trim($a->children(1)->plaintext), $userAway);

        // ID du membre
        static::$userList[$userID]['id'] = $userID;

        // Pseudo du membre
        static::$userList[$userID]['name'] = $this->decode($userInfos[1]);

        // Pseudo (autocompletion) du membre
        static::$userList[$userID]['completion'] = str_replace(' ', '_', $this->decode($userInfos[1]));

        // Niveau du compte
        static::$userList[$userID]['level'] = $this->getUserLevel($a);

        // Est autorisé à parlé/intervenant
        static::$userList[$userID]['voice'] = (bool) !empty($a->find('span.icone-voix'));

        // Refuse les MP
        static::$userList[$userID]['nopm'] = (bool) !empty($a->find('span.icone-nopv'));

        // Est AFK
        static::$userList[$userID]['afk'] = (bool) !empty($a->find('span.icone-absent'));

        // Couleur d'écriture
        static::$userList[$userID]['color'] = trim(str_replace(['color:'],'', $a->children(1)->style));

        // Statut
        static::$userList[$userID]['away'] = !empty($userAway[1]) ? $this->decode($userAway[1]) : '';

        // ID du salon
        static::$userList[$userID]['room'] = $roomID;

        // Si on est sur le compte du bot
        if(strtolower(static::$userList[$userID]['name']) == strtolower($cfg->get('username'))) {

          // On met les infos à dispo de $bot->whoami
          static::$botInfos = static::$userList[$userID];

          // Si l'event concerne le bot lui même on émet self.* sinon on laisse le préfixe user
          $eventPrefix = 'self';

        }

        // Si l'utilisateur est un autre bot on émet bot.*
        $eventPrefix = $eventPrefix != 'self' && static::$userList[$userID]['level'] == 2 ? 'bot' : $eventPrefix;

        // Si l'utilisateur était déjà connecté lors du dernier passage
        if(!empty(static::$_userList[$userID])) {

          // Event changement d'away de l'utilisateur
          if(static::$_userList[$userID]['away'] != static::$userList[$userID]['away']) {
            $this->prepareEvent($eventPrefix.'.away', array_merge(static::$userList[$userID], ['_away' => static::$_userList[$userID]['away']]), 'ee:prefix');
          }

          // Event changement de couleur de l'utilisateur
          if(static::$_userList[$userID]['color'] != static::$userList[$userID]['color']) {
            $this->prepareEvent($eventPrefix.'.color', array_merge(static::$userList[$userID], ['_color' => static::$_userList[$userID]['color']]), 'ee:prefix');
          }

          // Event ajout voix
          if(static::$_userList[$userID]['voice'] != static::$userList[$userID]['voice']) {
            $this->prepareEvent($eventPrefix.'.voice', static::$userList[$userID], 'ee:prefix');
          }

          // Event changement niveau
          if(static::$_userList[$userID]['level'] != static::$userList[$userID]['level']) {
            $this->prepareEvent($eventPrefix.'.level', array_merge(static::$userList[$userID], ['_level' => static::$_userList[$userID]['level']]), 'ee:prefix');
          }

          // Event changement état nopm
          if(static::$_userList[$userID]['nopm'] != static::$userList[$userID]['nopm']) {
            $this->prepareEvent($eventPrefix.'.nopm', static::$userList[$userID], 'ee:prefix');
          }

          // Event changement état disponible
          if(static::$_userList[$userID]['afk'] != static::$userList[$userID]['afk']) {
            $this->prepareEvent($eventPrefix.'.afk', static::$userList[$userID], 'ee:prefix');
          }

          // Event changement salon
          if(static::$_userList[$userID]['room'] != static::$userList[$userID]['room']) {
            $this->prepareEvent($eventPrefix.'.join', array_merge(static::$userList[$userID], ['_room' => static::$_userList[$userID]['room']]), 'ee:prefix');
          }

        } else {
          $eventSuffix = $init ? 'online' : 'connected';
          $this->prepareEvent($eventPrefix.'.'.$eventSuffix, static::$userList[$userID], 'ee:prefix');
        }

      }
    }

    // On extrait les salons qui sont détruits
    $rdiff = array_diff_key(static::$_roomList, static::$roomList);

    // Si des salons sont détruits
    if(!empty($rdiff)) {

      foreach($rdiff as $ri => $roomData) {

        // Event destruction de salon
        $this->prepareEvent('room.destroyed', $roomData);
      }

    }

    // On extrait les membres qui sont deconnectés
    $udiff = array_diff_key(static::$_userList, static::$userList);

    // Si des membres se sont déconnectés
    if(!empty($udiff)) {


      foreach($udiff as $userID => $userData) {

        // On regarde si c'est un membre ou un autre robot
        $eventPrefix = $userData['level'] == 2 ? 'bot':'user';

        // Event déconnexion du membre
        $this->prepareEvent($eventPrefix.'.disconnected', $userData, 'ee:prefix');

      }
    }

  }

  private function parseRoomMessage($anochat) {

    $shdom = new SHDOM();

    $html = $shdom->loadFromFile($anochat);

    $rmsg = $html->find('div.phrase');

    foreach($rmsg as $msg) {

      // Si on est sur la notif de connexion/changement de salon on skip
      if(!$msg->id) {
        continue;
      }

      $msgID = (int) str_replace('msg', '', $msg->id);

      $content = $this->decode($msg->children(2)->plaintext);

      /*
        msg.received	 Message normal reçu en salon	Testing
        msg.hl	         HL reçu en salon	Testing
        msg.bot	         Message normal/privé reçu d'un autre bot	Testing
        msg.sent	     Message envoyé par le bot	Testing

        notice.sent      /notice envoyée
        notice.received  /notice reçue
      */

      // On détermine le type de message
      $msgType = self::$msgType[$msg->children(2)->{'class'}];

      // Par défaut on met les valeurs des messages système
      $eventPrefix = 'msg';
      $eventSuffix = 'received';
      $author = -1;
      $hl = [];

      // Si c'est un message normal
      if($msgType == 'normal') {

        // On grab les infos du message (author, hl, msg)
        $msgInfos = $this->getMessageInfos($msg->children(2)->plaintext);

        $content = $msgInfos['msg'];

        $hl = $msgInfos['hl'];

        // Si le bot est l'envoyeur
        if($msgInfos['author'] == self::$botInfos['name']) {

          $eventSuffix = 'sent';
          $author = self::$botInfos['id'];

        } else {

          $eventSuffix = 'received';
          $author = $this->getUserIdByName($msgInfos['author']);

          // Si le bot est dans la liste des HL
          if(in_array(strtolower(self::$botInfos['name']), array_map('strtolower', $msgInfos['hl']))) {
            $eventSuffix = 'hl';
          }

        }

      }

      // Si on est sur un /notice
      if($msgType == 'notice') {

        // On grab les infos du message (author, hl, msg)
        $msgInfos = $this->getMessageInfos($msg->children(3)->plaintext);
        $content = $msgInfos['msg'];

        $eventPrefix = 'notice';

        if($msgInfos['author'] == self::$botInfos['name']) {
          $eventSuffix = 'sent';
        } else {
          $eventSuffix = 'received';
        }

        $author = $this->getUserIdByName($msgInfos['author']);
      }

      $this->prepareEvent($eventPrefix.'.'.$eventSuffix, [
        'author'    => $author,
        'hl'        => $hl,
        'type'      => $msgType,
        'msgID'     => $msgID,
        'msg'       => $content,
        'code'      => $this->getCode($html, $msgID),
        'quote'     => $this->getQuote($html, $msgID),
        'upload'    => $this->getUpload($html, $msgID, 2),
        'answer'    => isset($msg->{'data-rep'}) ? (int) str_replace('msg', '', $msg->{'data-rep'}) : 0,
        'timestamp' => strtotime(date('Y-m-d').' '.trim($msg->children(1)->plaintext).date(':s'))
      ], 'ee:prefix');

    }

  }

  /**
   * Parse Private Message - Traite les messages privés
   * @param array $anochat
   * return null
   */
  private function parsePrivateMessage(array $anochat) /*: void */ {

    $shdom = new SHDOM();

    foreach($anochat as $pm) {

      $html = $shdom->loadFromFile($pm->html);

      $pmContent = $html->find('div.phrase');

      $pmID = (int) str_replace('msg', '', $pmContent[0]->id);

      $content = $pmContent[0]->children(1)->plaintext;

      // On détermine le préfixe
      $eventPrefix = $pm->pseudo == '@' ? '@' : 'pm';

      if('normal' == $msgType = self::$msgType[$pmContent[0]->children(1)->{'class'}]) {

        $msgInfos = $this->getMessageInfos($pmContent[0]->children(1)->plaintext);

        // On grab le pseudo de celui qui envoie le message
        $userName = $msgInfos['author'];

        // Comme on à pas le HL en MP on récupère la chaine sans traitement
        $content = $msgInfos['str'];

        // Si le bot est l'envoyeur
        if($userName == self::$botInfos['name']) {

          $eventSuffix = 'sent';
          $author = self::$botInfos['id'];
          $dest = $pm->id;

        } else {

          $eventSuffix = 'received';
          $author = $this->getUserIdByName($userName);
          $dest = self::$botInfos['id'];

        }

      } else {

        $eventSuffix = 'received';
        $author = -1;
        $dest = $pm->id;

      }

      $this->prepareEvent($eventPrefix.'.'.$eventSuffix, [
        'author'    => $author,
        'dest'      => $dest,
        'type'      => self::$msgType[$pmContent[0]->children(1)->{'class'}],
        'msgID'     => $pmID,
        'msg'       => $content,
        'code'      => $this->getCode($html, $pmID),
        'quote'     => $this->getQuote($html, $pmID),
        'upload'    => $this->getUpload($html, $pmID, 1),
        'answer'    => isset($pmContent->{'data-rep'}) ? (int) str_replace('msg', '', $pmContent->{'data-rep'}) : 0,
        'timestamp' => strtotime(date('Y-m-d').' '.trim($pmContent[0]->children(0)->plaintext).date(':s'))
      ], 'ee:prefix');

    }

  }

  /**
   * Parse subst - Traite les subsitutions (messages modérés, supprimés, restaurés)
   *
   * @param array $anochat Données de substitution
   * @return void
   */
  private function parseSubst(array $anochat) /*: void*/ {

    $shdom = new SHDOM();

    foreach($anochat as $subst) {

      $html = $shdom->loadFromFile($subst->html);

      $span = $html->find('span');

      $content = $this->decode($span[0]->plaintext);

      // Si on est sur un message modéré ou supprimé
      if($span[0]->{'attr'}['class'] == 'supprime') {

        preg_match("~^message\s(supprimé|modéré)~i", $content, $action);

        // On détermine quelle action à été effectuée
        $act = self::$msgSubst[$action[1]];

      } else {

        // Sinon on est sur un message restauré
        $act = self::$msgSubst['restauré'];

      }

      if($msg = $span[1]->plaintext) {
        preg_match("~^\[(.*)\]\:\s(.*)~i", trim($msg), $infos);
      }

      if(count($infos) == 3) {
        $this->prepareEvent('subst.'.$act, ['id' => $msg->idMessage, 'msg' => $infos[2], 'username' => $infos[1]], 'ee:prefix');
      } else {
        $this->prepareEvent('subst.'.$act, ['id' => $msg->idMessage], 'ee:prefix');
      }
    }

  }

  /**
   * Prépare les events pour être synchrone avec les données connues du bot
   */
  private function prepareEvent(string $name, array $data, $prefix) {

    static::$eventList[] = ['name' => $name, 'data' => $data];

  }

  /**
   * Execute toutes les events préparées
   */
  private function execEvent() {

    foreach(static::$eventList as $e) {

      $this->bot->emit($e['name'], $e['data'], 'ee:prefix');

    }

    static::$eventList = [];
  }

  /**
   * getUserLevel - Retourne le niveau de l'utilisateur
   *
   * @param SHDOMN $user
   * @return int Niveau de l'utilisateur
   */
  private function getUserLevel(SHDOMN $user) : int {

    if(!empty($user->find('span.icone-niv4')))
      return self::LEVEL_ADMIN;

    if(!empty($user->find('span.icone-niv3')))
      return self::LEVEL_MODERATOR;

    if(!empty($user->find('span.icone-niv2')))
      return self::LEVEL_BOT;

    return self::LEVEL_MEMBER;
  }

  /**
   * decode - Retourne la chaine avec le charset UTF-8 et correctement formatée
   *
   * @param string $str Chaine d'entrée
   * @return string Chaine formatée
   */
  private function decode(string $str) : string {
    return mb_convert_encoding(htmlspecialchars_decode($str, ENT_QUOTES), 'UTF-8');
  }

  /**
   * getQuote - Extrait la citation ou son url
   * @param SHDOMN $html contenant le code HTML à analyser
   * @return string Citation, vide si aucun résultat
   */
  private function getQuote($html, $msgid) : string {

    // Si on à un élément quote
    if($quote = $html->find('div.msg'.$msgid.'x')) {
      return $quote[0]->plaintext;
    }

    return '';
  }

  /**
   * getCode - Extrait le code ou son url
   * @param SHDOMN $html Instance HTML à analyser
   * @param int $msgid ID du message
   * @return string Code reçu, vide si aucun résultat
   */
  private function getCode($html, $msgid) : string {

    // Si on à un élément quote
    if($code = $html->find('pre.msg'.$msgid.'x')) {
      return $code[0]->innertext;
    }

    return '';
  }

  /**
   * getUpload - Extrait les uploads
   * @param SHDOMN $html contenant le code HTML à analyser
   * @return string URL de l'upload, vide si aucun résultat
   */
  private function getUpload($html, int $msgid, int $offset) : string {

    $msg = $html->find("div#msg$msgid");
    $el = $msg[0]->children($offset)->find('a');

    foreach($el as $a) {

      // Si on à un upload qui n'est ni une quote, ni un code (upload/*/*)
      if(preg_match("~upload/([[:alnum:]]+)/(.*)~", $a->href)) {

        return $a->href;
      }

    }

    return '';
  }

  /**
   * getUserIdByName - Renvoi l'ID de l'utilisateur ayant le pseudo renseigné
   *
   * @param string $name pseudo du membre
   * @return int ID du membre (0 = echec)
   */
  private function getUserIdByName($name) : int {
    return array_search($name, array_column(self::$userList, 'name', 'id'));
  }


  private function getUserLevelById(int $uid) : int {
    return array_search($name, array_column(self::$userList, 'name', 'id'));
  }

  private function getMessageInfos(string $str) : array {

    $msgInfos = [];

    if(substr(trim($str), 0, 1) != '*') {

      preg_match("~^\[([-_\w\d\s]+)\]:\s((?:[-_\w\d\s]+\>\s+)*)(.*)$~u", $this->decode($str), $data);

      $msgInfos['author'] = $data[1];

      $msgInfos['hl'] = array_slice(array_map('trim',$hl = explode('> ', $data[2])), 0, count($hl) -1);

      $msgInfos['msg'] = trim($data[3]);

      $msgInfos['str'] = trim($data[2].$data[3]);

    } else {

      $_str = substr(trim($str), 2, strlen($str));
      $data = explode(' ', $_str);

      foreach(self::$userList as $i => $user) {

        if(strpos($_str, $user['name']) === 0) {

          $msgInfos['author'] = $user['name'];

          $msgInfos['msg'] = trim(substr($_str, 0, strlen($user['name'])));

          break;
        }

      }

      $msgInfos['hl'] = [];

      $msgInfos['str'] = $str;

    }

    return $msgInfos;
  }

}
