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
 * Hyphenator
 *
 * @category   Hyphenator
 * @package    Hyphenator
 * @copyright  Copyright (c) 2010-2012 Web Rocker (http://web-rocker.de)
 * @license    http://web-rocker.de/projekte/new-bsd-license/     New BSD License
 */
namespace Hyphenator;

include_once "vendor/autoload.php";

// use Memcache (localhost:11211)
$core = new Core(
    dirname(__FILE__) . DIRECTORY_SEPARATOR . 'patterns',
    new Cache\MemcachedCache()
);

/*
// use custom Memcache handle
$handle = new \Memcache();
$handle->connect('localhost', 11211);

$core = new Core(
    dirname(__FILE__) . DIRECTORY_SEPARATOR . 'patterns',
    new Cache\MemcachedCache($handle)
);
*/

/*
// use APC
$core = new Core(
    dirname(__FILE__) . DIRECTORY_SEPARATOR . 'patterns',
    new Cache\ApcCache()
);
*/
/*
// use no cache
$core = new Core(
    dirname(__FILE__) . DIRECTORY_SEPARATOR . 'patterns'
);
*/
$core->registerPatterns('de');

/*
 * For example purposes we override the hyphen
 */
$core->setHyphen('|');

echo $core->hyphenate('Ein RIESEN Haufen von Ausdrucken liegt auf dem Boden. Und tatsaechlich, sein
Dokument liegt ganz oben auf. Ich breite es ueber dem Haufen aus und spruehe
grosszuegig unser Spezialfleckenwasser in die Gegend. Dann fahre ich den
schweren Bandwagen ein paar Mal darueber und klemme es zum kroenenden
Abschluss vier, fuenf Mal in die schwere Safetuere ein, wo wir die Backup-
Baender aufbewahren sollten. Huebsch. Ich schleiche zurueck in den Druckerraum und suche die Tonerkassette, die
wir fuer spezielle Faelle aufbewaren - die mit den dicken schwarzen Streifen
in der Mitte und den blassen Raendern.');

