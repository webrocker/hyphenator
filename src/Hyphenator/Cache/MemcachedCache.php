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
use Hyphenator\Exception\ExtensionException;

class MemcachedCache implements CacheInterface
{
    /**
     * @var \Memcache
     */
    protected $connection = null;

    public function __construct(\Memcache $connection = null)
    {
        if (!extension_loaded('memcache')) {
            throw new MissingExtensionException('PHP extension memcache ist not loaded. You cannot use this cache.');
        }

        if (!$connection) {
            $this->connection = new \Memcache;
            if (!$this->connection->connect('localhost', 11211)) {
                throw new ExtensionException('Cannot connect to memcache on localhost:11211');
            }
        }
    }

    public function fetch($cacheKey)
    {
        return $this->connection->get($cacheKey);
    }

    public function store($cacheKey, $data)
    {
        return $this->connection->set($cacheKey, $data);
    }
}