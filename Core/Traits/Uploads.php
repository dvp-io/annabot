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

trait Uploads {

  /**
   * Envoi de fichier sur le serveur
   */
  public function uploadFile(string $file, int $targetID, bool $show = false) {

    $args = [];
    $args['fichier'] = new \CURLFile($file, mime_content_type($file));
    $args['image'] = $show === true ? 1 : 0;
    $args['MAX_FILE_SIZE'] = 4194304;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL,             $this->_host.'/envoi?s='.$this->_session.'&c='.$targetID);
    curl_setopt($ch, CURLOPT_HTTPHEADER,      ["Content-Type:multipart/form-data"]);
    curl_setopt($ch, CURLOPT_POST,            true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,      $args);
    curl_setopt($ch, CURLOPT_USERAGENT,       $this->_useragent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,  true);
    curl_setopt($ch, CURLOPT_AUTOREFERER,     true);
    curl_setopt($ch, CURLOPT_FORBID_REUSE,    true);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT,   true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,  false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  false);
    curl_setopt($ch, CURLOPT_VERBOSE,         true);


    $result = curl_exec($ch);

    //var_dump($result);
  }

  /**
   * Alias pour simplifier l'envoi d'images dans une conversation
   */
  public function uploadImage(string $filePath, int $targetID) {
    $this->uploadFile($filePath, $targetID, true);
  }

}
