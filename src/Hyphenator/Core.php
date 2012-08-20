<?php
/**
 * a PHP Hyphenator
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://web-rocker.de/projekte/new-bsd-license/
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to community@web-rocker.de so we can send you a copy immediately.
 *
 * @category   Hyphenator
 * @package    Hyphenator
 * @copyright  Copyright (c) 2010-2012 Web Rocker (http://web-rocker.de)
 * @license    http://web-rocker.de/projekte/new-bsd-license/     New BSD License
 */

/**
 * Hyphenator Core
 *
 * @category   Hyphenator
 * @package    Hyphenator
 * @copyright  Copyright (c) 2010-2012 Web Rocker (http://web-rocker.de)
 * @license    http://web-rocker.de/projekte/new-bsd-license/     New BSD License
 */
namespace Hyphenator;

use Hyphenator\Cache\CacheInterface;
use Hyphenator\Exception\PatternNotFoundException;
use Hyphenator\Exception\NoPatternLoadedException;

class Core
{
    /**
     * @var string
     */
    protected $patternsPath = null;

    /**
     * @var CacheInterface
     */
    protected $cache = null;

    /**
     * @var string
     */
    protected $cachePrefix = null;

    /**
     * @var array
     */
    protected $patterns = array();

    /**
     * @var string
     */
    protected $patternsRegistry = null;

    protected $hyphen = '&shy;';

    protected $leftmin = 2;

    protected $rightmin = 2;

    protected $charmin = 2;

    protected $charmax = 10;

    protected $excludeTags = array("code", "pre", "script", "style");

    public function __construct($patternsPath = null, CacheInterface $cache = null)
    {
        if ($patternsPath) {
            $this->patternsPath = realpath($patternsPath);
        } else {
            $this->patternsPath = realpath(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'patterns');
        }

        $this->cache = $cache;
    }

    public function registerPatterns($language)
    {
        $language = strtolower($language);
        $this->patternsRegistry[$language] = $language;
    }

    protected function loadPattern($language)
    {
        $filename = $this->patternsPath . DIRECTORY_SEPARATOR . $language . '.php';

        $patterns = @include $filename;

        if (false === $patterns) {
            throw new PatternNotFoundException('Cannot load pattern from file ' . $filename);
        }

        $this->patterns[$language] = $this->_convertPatterns($patterns);
    }

    protected function getCachePrefix()
    {
        if (null === $this->cachePrefix) {
            $this->cachePrefix = 'hyphenator_' . md5($this->patternsRegistry) . '_';
        }

        return $this->cachePrefix;
    }

    protected function getCacheKey($text)
    {
        return $this->getCachePrefix() . md5($text);
    }

    public function hyphenate($text)
    {
        if (null === $this->patternsRegistry) {
            throw new NoPatternLoadedException('You need to load patterns with registerPatterns method.');
        }

        if (0 === count($this->patterns)) {
            $this->loadPattern($this->patternsRegistry);
        }

        if ($this->cache) {
            $cacheKey = $this->getCacheKey($text);
            if (!$hyphenatedText = $this->cache->fetch($cacheKey)) {
                $hyphenatedText = $this->engine($text);
                $this->cache->store($cacheKey, $hyphenatedText);
                return $hyphenatedText;
            }
        }

        return $this->engine($text);
    }

    protected function engine($text)
    {
        $word = "";
        $tag = "";
        $tag_jump = 0;
        $output = array();
        $word_boundaries = "<>\t\n\r\0\x0B !\"§$%&/()=?….,;:-–_„”«»‘’'/\\‹›()[]{}*+´`^|©℗®™℠¹²³";
        $text = $text . " ";

        for ($i = 0; $i < mb_strlen($text); $i++) {
            $char = mb_substr($text, $i, 1);
            if (mb_strpos($word_boundaries, $char) === false && $tag == "") {
                $word .= $char;
            } else {
                if ($word != "") {
                    $output[] = $this->_hyphenateWord($word);
                    $word = "";
                }
                if ($tag != "" || $char == "<") $tag .= $char;
                if ($tag != "" && $char == ">") {
                    $tag_name = (mb_strpos($tag, " ")) ? mb_substr($tag, 1, mb_strpos($tag, " ") - 1) : mb_substr($tag, 1, mb_strpos($tag, ">") - 1);
                    if ($tag_jump == 0 && in_array(mb_strtolower($tag_name), $this->excludeTags)) {
                        $tag_jump = 1;
                    } else if ($tag_jump == 0 || mb_strtolower(mb_substr($tag, -mb_strlen($tag_name) - 3)) == '</' . mb_strtolower($tag_name) . '>') {
                        $output[] = $tag;
                        $tag = '';
                        $tag_jump = 0;
                    }
                }
                if ($tag == "" && $char != "<" && $char != ">") $output[] = $char;
            }
        }

        $text = join('', $output);
        return substr($text, 0, strlen($text) - 1);
    }

    private function _convertPatterns($patterns)
    {
        $patterns = mb_split(' ', $patterns);
        $new_patterns = array();

        for ($i = 0; $i < count($patterns); $i++) {
            $value = $patterns[$i];
            $new_patterns[preg_replace('/[0-9]/', '', $value)] = $value;
        }

        return $new_patterns;
    }

    private function _mb_split_chars($string)
    {
        $array = array();
        $strlen = mb_strlen($string);

        while ($strlen) {
            $array[] = mb_substr($string, 0, 1, 'utf-8');
            $string = mb_substr($string, 1, $strlen, 'utf-8');
            $strlen = mb_strlen($string);
        }

        return $array;
    }

    private function _hyphenateWord($word)
    {
        if (mb_strlen($word) < $this->charmin) return $word;
        if (mb_strpos($word, $this->hyphen) !== false) return $word;

        //if (isset($GLOBALS['dictionary words'][mb_strtolower($word)])) return $GLOBALS['dictionary words'][mb_strtolower($word)];

        $text_word = '_' . $word . '_';
        $word_length = mb_strlen($text_word);
        $single_character = $this->_mb_split_chars($text_word);
        $text_word = mb_strtolower($text_word);
        $hyphenated_word = array();
        $numb3rs = array('0' => true, '1' => true, '2' => true, '3' => true, '4' => true, '5' => true, '6' => true, '7' => true, '8' => true, '9' => true);

        for ($position = 0; $position <= ($word_length - $this->charmin); $position++) {
            $maxwins = min(($word_length - $position), $this->charmax);

            for ($win = $this->charmin; $win <= $maxwins; $win++) {
                if (isset($this->patterns[mb_substr($text_word, $position, $win)])) {
                    $pattern = $this->patterns[mb_substr($text_word, $position, $win)];
                    $digits = 1;
                    $pattern_length = mb_strlen($pattern);

                    for ($i = 0; $i < $pattern_length; $i++) {
                        $char = $pattern[$i];
                        if (isset($numb3rs[$char])) {
                            $zero = ($i == 0) ? $position - 1 : $position + $i - $digits;
                            if (!isset($hyphenated_word[$zero]) || $hyphenated_word[$zero] != $char) $hyphenated_word[$zero] = $char;
                            $digits++;
                        }
                    }
                }
            }
        }

        $inserted = 0;
        for ($i = $this->leftmin; $i <= (mb_strlen($word) - $this->rightmin); $i++) {
            if (isset($hyphenated_word[$i]) && $hyphenated_word[$i] % 2 != 0) {
                array_splice($single_character, $i + $inserted + 1, 0, $this->hyphen);
                $inserted++;
            }
        }

        return implode('', array_slice($single_character, 1, -1));
    }
}