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
use Hyphenator\Cache\ArrayCache;
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
     * @var CacheInterface
     */
    protected $wordCache = null;

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

    /**
     * @var array
     */
    protected $dictionary = array();

    /**
     * @var string
     */
    protected $hyphen = '&shy;';

    /**
     * @var int
     */
    protected $leftmin = 2;

    /**
     * @var int
     */
    protected $rightmin = 2;

    /**
     * @var int
     */
    protected $charmin = 2;

    /**
     * @var int
     */
    protected $charmax = 10;

    /**
     * @var array
     */
    protected $excludeTags = array("code", "pre", "script", "style");

    /**
     * Constructor
     * @param null $patternsPath
     * @param Cache\CacheInterface $cache
     */
    public function __construct($patternsPath = null, CacheInterface $cache = null)
    {
        if ($patternsPath) {
            $this->patternsPath = realpath($patternsPath);
        } else {
            $this->patternsPath = realpath(dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'patterns');
        }

        $this->cache = $cache;

        $this->wordCache = new ArrayCache();
    }

    /**
     * Set the longest pattern (numbers don't count!)
     * @param $charmax
     */
    public function setCharmax($charmax)
    {
        $this->charmax = $charmax;
    }

    /**
     * Set the shortes pattern (numbers don't count!)
     * @param $charmin
     */
    public function setCharmin($charmin)
    {
        $this->charmin = $charmin;
    }

    /**
     * Set an array of excluded tags (if working on html, not plain text)
     * @param $excludeTags
     */
    public function setExcludeTags(array $excludeTags)
    {
        $this->excludeTags = $excludeTags;
    }

    /**
     * Set the hyphen Character (for debugging)
     * @param $hyphen
     */
    public function setHyphen($hyphen)
    {
        $this->hyphen = $hyphen;
    }

    /**
     * Set the minimum of chars to remain on the old line
     * @param $leftmin
     */
    public function setLeftmin($leftmin)
    {
        $this->leftmin = $leftmin;
    }

    /**
     * Set the minimum of chars to go on the new line
     * @param $rightmin
     */
    public function setRightmin($rightmin)
    {
        $this->rightmin = $rightmin;
    }

    /**
     * Register pattern for the chosen language
     * @param $language
     */
    public function registerPatterns($language)
    {
        $language = strtolower($language);
        $this->patternsRegistry = $language;
    }

    /**
     * Loads patterns file and converts it
     * @param $language
     * @throws Exception\PatternNotFoundException
     */
    protected function loadPattern($language)
    {
        $filename = $this->patternsPath . DIRECTORY_SEPARATOR . $language . '.php';

        $cacheKey = $this->getCachePrefix() . md5('_patterns_' . $language);

        if ((!$this->cache) || (!$this->patterns = $this->cache->fetch($cacheKey))) {
            $patterns = @include $filename;

            if (false === $patterns) {
                throw new PatternNotFoundException('Cannot load pattern from file ' . $filename);
            }

            $this->patterns = $this->_convertPatterns($patterns);

            if ($this->cache) {
                $this->cache->store($cacheKey, $this->patterns);
            }
        }
    }

    /**
     * Returns a cache prefix to be prepended to cache identifiers
     * @return string
     */
    protected function getCachePrefix()
    {
        if (null === $this->cachePrefix) {
            $this->cachePrefix = 'hyphenator_' . md5($this->patternsRegistry) . '_';
        }

        return $this->cachePrefix;
    }

    /**
     * Return the unique cache identifier for the given text
     * @param $text
     * @return string
     */
    protected function getCacheKey($text)
    {
        return $this->getCachePrefix() . md5($text);
    }

    /**
     * Hyphenates the given text
     * @param $text
     * @return string
     * @throws Exception\NoPatternLoadedException
     */
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

    /**
     * Hyphenator engine
     * @param $text
     * @return string
     */
    protected function engine($text)
    {
        $word = "";
        $tag = "";
        $tagCount = 0;
        $output = array();
        $wordDelimiter = "<>\t\n\r\0\x0B !\"§$%&/()=?….,;:-–_„”«»‘’'/\\‹›()[]{}*+´`^|©℗®™℠¹²³";
        $text = $text . " ";

        for ($i = 0; $i < mb_strlen($text); $i++) {
            $char = mb_substr($text, $i, 1);
            if (mb_strpos($wordDelimiter, $char) === false && $tag == "") {
                $word .= $char;
            } else {
                if ($word != "") {
                    $output[] = $this->_hyphenateWordWrapper($word);
                    $word = "";
                }
                if ($tag != "" || $char == "<") $tag .= $char;
                if ($tag != "" && $char == ">") {
                    $tagName = (mb_strpos($tag, " ")) ? mb_substr($tag, 1, mb_strpos($tag, " ") - 1) : mb_substr($tag, 1, mb_strpos($tag, ">") - 1);
                    if ($tagCount == 0 && in_array(mb_strtolower($tagName), $this->excludeTags)) {
                        $tagCount = 1;
                    } else if ($tagCount == 0 || mb_strtolower(mb_substr($tag, -mb_strlen($tagName) - 3)) == '</' . mb_strtolower($tagName) . '>') {
                        $output[] = $tag;
                        $tag = '';
                        $tagCount = 0;
                    }
                }
                if ($tag == "" && $char != "<" && $char != ">") $output[] = $char;
            }
        }

        $text = join('', $output);
        return substr($text, 0, strlen($text) - 1);
    }

    /**
     * Converts patterns into usable format
     * @param $patterns
     * @return array
     */
    private function _convertPatterns($patterns)
    {
        $patterns = mb_split(' ', $patterns);
        $convertedPatterns = array();

        for ($i = 0; $i < count($patterns); $i++) {
            $value = $patterns[$i];
            $convertedPatterns[preg_replace('/[0-9]/', '', $value)] = $value;
        }

        return $convertedPatterns;
    }

    /**
     * Splits mb string into array of characters
     * @param $string
     * @return array
     */
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

    private function _hyphenateWordWrapper($word)
    {
        if (!$hypenated = $this->wordCache->fetch(mb_strtolower($word))) {
            $hypenated = $this->_hyphenateWord($word);
            $this->wordCache->store(mb_strtolower($word), $hypenated);
        }

        return $hypenated;
    }

    /**
     * Hyphenates a single word
     * @param $word
     * @return string
     */
    private function _hyphenateWord($word)
    {
        if (mb_strlen($word) < $this->charmin) return $word;
        if (mb_strpos($word, $this->hyphen) !== false) return $word;

        if (isset($this->dictionary[mb_strtolower($word)])) return $this->dictionary[mb_strtolower($word)];

        $inputText = '_' . $word . '_';
        $inputTextLength = mb_strlen($inputText);
        $arrayOfCharacters = $this->_mb_split_chars($inputText);
        $inputText = mb_strtolower($inputText);
        $hyphenatedInputText = array();
        $arrayOfNumbers = array('0' => true, '1' => true, '2' => true, '3' => true, '4' => true, '5' => true, '6' => true, '7' => true, '8' => true, '9' => true);

        for ($position = 0; $position <= ($inputTextLength - $this->charmin); $position++) {
            $maxwins = min(($inputTextLength - $position), $this->charmax);

            for ($win = $this->charmin; $win <= $maxwins; $win++) {
                if (isset($this->patterns[mb_substr($inputText, $position, $win)])) {
                    $pattern = $this->patterns[mb_substr($inputText, $position, $win)];
                    $digits = 1;
                    $pattern_length = mb_strlen($pattern);

                    for ($i = 0; $i < $pattern_length; $i++) {
                        $char = $pattern[$i];
                        if (isset($arrayOfNumbers[$char])) {
                            $zero = ($i == 0) ? $position - 1 : $position + $i - $digits;
                            if (!isset($hyphenatedInputText[$zero]) || $hyphenatedInputText[$zero] != $char) $hyphenatedInputText[$zero] = $char;
                            $digits++;
                        }
                    }
                }
            }
        }

        $inserted = 0;
        for ($i = $this->leftmin; $i <= (mb_strlen($word) - $this->rightmin); $i++) {
            if (isset($hyphenatedInputText[$i]) && $hyphenatedInputText[$i] % 2 != 0) {
                array_splice($arrayOfCharacters, $i + $inserted + 1, 0, $this->hyphen);
                $inserted++;
            }
        }

        return implode('', array_slice($arrayOfCharacters, 1, -1));
    }
}