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
 * Hyphenator Cache
 *
 * @category   Hyphenator
 * @package    Hyphenator
 * @copyright  Copyright (c) 2010-2012 Web Rocker (http://web-rocker.de)
 * @license    http://web-rocker.de/projekte/new-bsd-license/     New BSD License
 */
namespace Hyphenator\Cache;

use Hyphenator\Exception\MissingExtensionException;

class ApcCache implements CacheInterface
{
    public function __construct()
    {
        if (!extension_loaded('apc')) {
            throw new MissingExtensionException('PHP extension apc ist not loaded. You cannot use this cache.');
        }
    }

    public function fetch($cacheKey)
    {
        return apc_fetch($cacheKey);
    }

    public function store($cacheKey, $data)
    {
        return apc_store($cacheKey, $data);
    }
}