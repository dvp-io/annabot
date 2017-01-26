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
 * Ce fichier permet de mettre à disposition des modules toutes les commandes natives du chat sous forme de méthodes, il permet
 * également de masquer le contenu des validateurs pour éviter la propagation des commandes de modération
 * Se référer à la documentation pour connaitre l'ensemble des méthodes mises à disposition et voir les exemples d'utilisation
 *
 * NOTE DE PUBLICATION : Les commandes de modérations ont été retirées
 */
namespace Core\Commands;

use Core\Commands\Validator as CMD;
use Core\Logger as Logger;

/**
 *  Trait mettant à disposition moultes méthodes pour rendre les modules magiques
 */
trait Parser {

  /**
   * Envoie une alerte à la modération
   * @since 2.0.0
   * @param string $reason Motif de l'alerte
   * @example Modules\Example\CLI\Alert.php
   * @return string succès
   *         bool false echec
   */
  public function alert($reason) {
    if($cmd = CMD\Alert::getCmd($reason)) {
      return $this->sendCmd($cmd);
    }
    return false;
  }

  /**
   * Autorise ou interdit à un membre de MP le bot (mode +B requis)
   * @since 2.0.0
   * @param int $target ID du membre ciblé
   * @param bool $status Accepter/refuser
   * @return string succès
   *         bool false echec
   */
  public function allow(int $target, bool $value) {
    if($cmd = CMD\Allow::getCmd($reason)) {
      return $this->sendCmd($cmd);
    }
    return false;
  }

  /**
   * Change le statut du bot
   * @since 2.0.0
   * @param string $away Statut à afficher, laisser vide pour effacer le statut
   * @example Modules\Example\CLI.php 58 66
   * @return string succès
   *         false echec
   */
  public function away(string $away = '') {
    if($cmd = CMD\Away::getCmd($away)) {
      return $this->sendCmd($cmd);
    }
    return false;
  }

  /**
   * Change le statut du bot en mode indisponible
   * @param string $away Statut à afficher, laisser vide pour effacer le statut
   * @return string succès
   *         false echec
   */
  public function awayna(string $away = '') {
    if($cmd = CMD\Awayna::getCmd($away)) {
      return $this->sendCmd($cmd);
    }
    return false;
  }


  public function back() {
    return false;
  }


  public function backtime() {
    return false;
  }

  /**
   * Efface l'historique
   * @since 2.0.0
   * @param void
   * @return bool false Le bot n'est pas concerné
   */
  public function clear() {
    return false;
  }

  /**
   * Ferme un onglet de massage privé
   * @since 2.0.0
   * @param void
   * @return bool false Le bot n'est pas concerné
   */
  public function close() {
    return false;
  }

  /**
   * Change la couleur d'écriture du bot
   * @param string $color Code couleur hexadécimal #ABCDEF
   * @return string succès
   *         bool false Echec
   */
  public function color(string $color) {

    if($cmd = CMD\Color::getCmd($color)) {
      return $this->sendCmd($cmd);
    }

    return false;
  }

  /**
   * Supprime/modère un message
   * @since 2.0.0
   * @param int $msgid ID du message
   * @return string Succès
   *         bool false Echec
   */
  public function delete(int $msgid) {

    if($this->whoami()->level < self::LEVEL_BOT) {
      return false;
    }

    if($cmd = CMD\Delete::getCmd($msgid)) {
      return $this->sendCmd($cmd);
    }

    return false;
  }


  public function dice(int $d, int $y) {

    // Si le salon n'a pas le /DICE activé on renvoi un code d'erreur
    if(!$this->room($this->whoami()->room)->dice) {
      return false;
    }

    if($cmd = CMD\Dice::getCmd($d, $y)) {
      return $this->sendCmd($cmd);
    }

    return false;
  }

  /**
   * Envoi un message public aux utilisateurs ciblés
   * @param  array $targets Pseudos des membres ciblés
   * @param  string $message Message envoyé
   * @return string succès
   *         false echec
   */
  public function hl($targets, $message) {
    if($cmd = CMD\Hl::getCmd($targets, $message)) {
      return $this->sendCmd($cmd);
    }
    return false;
  }

  //public function invite() {}

  /**
   * Déplace le bot dans le salon souhaité
   * @param int $room ID du salon à rejoindre
   *        string $room Nom du salon à rejoindre
   * @return string succès
   *         false echec
   */
  public function join($room) {
    if($cmd = CMD\Join::getCmd($room)) {
      return $this->sendCmd($cmd);
    }
    return false;
  }

  //public function lock() {}

  /**
   * Permet d'envoyer un message à la première personne dans la conversation
   * @param string $message Message à la première personne
   * @return string succès
   *         false echec
   */
  public function me($message) {
    if($cmd = CMD\Me::getCmd($message)) {
      return $this->sendCmd($cmd);
    }
    return false;
  }

  /**
   * Envoie une notice au membre spécifié
   * @param int $target ID du membre ciblé
   *        string $target Pseudo du membre cible
   * @param string $message Message envoyé au membre
   * @return string succès
   *         false echec
   */
  public function notice($target, $message) {
    if($cmd = CMD\Notice::getCmd($target, $message)) {
      return $this->sendCmd($cmd);
    }
    return false;
  }

  /**
   * Déconnecte le bot du chat
   * @param string $reason Message affiché lors de la déconnexion+
   * @return string succès
   *         false echec
   */
  public function quit($reason = '') {
    if($cmd = CMD\Quit::getCmd($reason)) {
      return $this->sendCmd($cmd);
    }
    return false;
  }

  /**
   * Envoi la commande sans traitement
   * @since 2.0.0
   * @param string $cmd Commande à executer
   * @return string succès
   */
  public function say($message) {
    return $this->sendCmd($message);
  }

  /**
   * Envoie un MP au membre ciblé
   * @since 2.0.0
   * @param int $target ID du membre ciblé
   * @param string $message Message envoyé
   * @return string succès
   *         false echec
   */
  public function tell(int $target, $message) {
    if($cmd = CMD\Tell::getCmd($target, $message)) {
      return $this->sendCmd($cmd);
    }
    return false;
  }

  /**
   * Restaure un message supprimé/modéré
   * @since 2.0.0
   * @param int $msgid ID du message
   * @return string Succès
   *         bool false Echec
   */
  public function undelete(int $msgid) {
    if($cmd = CMD\Undelete::getCmd($msgid)) {
      return $this->sendCmd($cmd);
    }
    return false;
  }

}
