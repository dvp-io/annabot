<?php
namespace SimpleHTMLDOM;

class Helper {

  const HDOM_TYPE_ELEMENT       = 1;
  const HDOM_TYPE_COMMENT       = 2;
  const HDOM_TYPE_TEXT          = 3;
  const HDOM_TYPE_ENDTAG        = 4;
  const HDOM_TYPE_ROOT          = 5;
  const HDOM_TYPE_UNKNOWN       = 6;
  const HDOM_QUOTE_DOUBLE       = 0;
  const HDOM_QUOTE_SINGLE       = 1;
  const HDOM_QUOTE_NO           = 3;
  const HDOM_INFO_BEGIN         = 0;
  const HDOM_INFO_END           = 1;
  const HDOM_INFO_QUOTE         = 2;
  const HDOM_INFO_SPACE         = 3;
  const HDOM_INFO_TEXT          = 4;
  const HDOM_INFO_INNER         = 5;
  const HDOM_INFO_OUTER         = 6;
  const HDOM_INFO_ENDSPACE      = 7;
  const DEFAULT_TARGET_CHARSET  = 'UTF-8';
  const DEFAULT_BR_TEXT         = "\r\n";
  const DEFAULT_SPAN_TEXT       = " ";
  const MAX_FILE_SIZE           = 600000;

// helper functions
// -----------------------------------------------------------------------------
// get html dom from file
// $maxlen is defined in the code as PHP_STREAM_COPY_ALL which is defined as -1.
  public function loadFromFile($url, $use_include_path = false, $context = null, $offset = -1, $maxLen = -1, $lowercase = true, $forceTagsClosed = true, $target_charset = self::DEFAULT_TARGET_CHARSET, $stripRN = true, $defaultBRText = self::DEFAULT_BR_TEXT, $defaultSpanText = self::DEFAULT_SPAN_TEXT) {
	// We DO force the tags to be terminated.
	$dom = new SimpleHTMLDOM(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
	// For sourceforge users: uncomment the next line and comment the retreive_url_contents line 2 lines down if it is not already done.
	$contents = $url; ///file_get_contents($url, $use_include_path, $context, $offset);
	// Paperg - use our own mechanism for getting the contents as we want to control the timeout.
	//$contents = retrieve_url_contents($url);
	if (empty($contents) || strlen($contents) > self::MAX_FILE_SIZE) {
		return false;
	}
	// The second parameter can force the selectors to all be lowercase.
	$dom->load($contents, $lowercase, $stripRN);
	return $dom;
  }

// get html dom from string
  function loadFromString($str, $lowercase = true, $forceTagsClosed = true, $target_charset = self::DEFAULT_TARGET_CHARSET, $stripRN = true, $defaultBRText = self::DEFAULT_BR_TEXT, $defaultSpanText = self::DEFAULT_SPAN_TEXT) {
	$dom = new SimpleHTMLDOM(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
	if(empty($str) || strlen($str) > self::MAX_FILE_SIZE) {
		$dom->clear();
		return false;
	}
	$dom->load($str, $lowercase, $stripRN);
	return $dom;
  }

  // dump html dom tree
  function dumpHTML($node, $show_attr = true, $deep=0) {
	$node->dump($node);
  }
}
