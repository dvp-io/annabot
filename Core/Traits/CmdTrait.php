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

trait Cmd {

  private $_commands = [];

  /**
   * Enregistre une /commande et l'active dans le salon du bot
   *
   * @param string $name Nom de la commande
   * @param int $level Niveau minimum requis pour avoir accès à la commande
   * @param bool $autoCompleted Active l'auto-completion côté client
   * @param string $noManland Variable secrète pour désactiver la création d'un manuel. x_x = désactivé
   * @return true Bang enregistré avec succès
   */
  public function registerCmd(string $name, int $level, bool $autCompleted, $noManland = '') {

    if(!isset($this->_commands[$name]))
      $this->commands[$name] = ['lvl' => $level, 'out' => $output];


  }


}
