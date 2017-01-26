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

trait Bang {

  protected $bangs = [];

  /**
   * Détermine si l'entrée est un bang
   *
   * @param string $msg Message reçu
   * @return false Ce n'est pas un bang
   * @return array Retourne le bang et la liste des arguments reçus
   */
  public function getBang(array $msg) {

    preg_match("~^\!([[:alnum:]]+)\s(.*)$~i", $entry, $m);

    // Si c'est pas un bang
    if(empty($m)) {
      return false;
    }

    $_m = explode(' ', trim($m[2]));

    /*foreach($_m as $_t) {
      if(!empty($this->getUserByName()))

    }*/

    // Si c'est un bang on retourne le nom et la liste des arguments
    return [
      'name' => trim($m[1]),
      'targets' => '',
      'args' => explode(' ', trim($m[2])),
      'str'  => $m[0]
    ];
  }

  /**
   * Enregistre et active un bang sur l'instance du bot
   *
   * @param string $name Nom du bang
   * @param int $level Niveau minimum requis pour avoir accès au bang
   * @param string $mode Mode du bang (room|private)
   * @param string $noManland Variable secrète pour désactiver la création d'un manuel. x_x = désactivé
   * @return true Bang enregistré avec succès
   */
  public function registerBang(string $name, int $level, string $mode, string $noManLand = '') {

    $cfg = Config::getInstance();
    $logger = new Logger();

    if(isset($this->bangs[$name])) {
      throw new Exception('Bang "!'.$name.'" is already taken, choose another name');
    }

    if($cfg->get('debugEvents')) {
      $logger->printLog('Bang !'.$name.' registered', 's');
    }

    if($noManLand !== 'x_x') {
      $this->registerMan($name, $level);
    }

    $this->bangs[$name] = ['level' => $level, 'mode' => $mode];

    return true;
  }

  /**
   * Détermine si le bang existe et si le niveau requis est respecté
   *
   * @param string $name Nom du bang
   * @param int $level Niveau de compte de l'invoqueur
   * @param string $mode Mode de réception
   * @return true Succès
   * @return false Echec
   */
  private function isRegisteredBang(string $name, int $level, string $mode) {

    // Si le bang n'existe pas
    if(!isset($this->bangs[$name])) {
      return false;
    }

    // Si le niveau est trop bas
    if(!isset($this->bangs[$name]['level']) || $this->bangs['level'] > $level) {
      return false;
    }

    // Si le mode est incorrect
    if(!isset($this->bangs[$name]['mode']) || $this->bangs['mode'] != $mode) {
      return false;
    }

    return true;
  }

  public function emitBang(array $msg, string $name, string $mode) {

    //if($this->isRegisteredBang)

  }

}
