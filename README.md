hyphenator
==========

Hyphenator is an object-oriented and cache-enabled port of phpHyphenator(http://phphyphenator.yellowgreen.de/) which is a PHP port of the JavaScript Hyphenator by Mathias Nater(http://code.google.com/p/hyphenator/).

Installation
------------

Just add `webrocker/hyphenator` to Your `composer.json` and run `composer.phar update` to add Hyphenator to Your project.

Usage
-----

see `sample.php`

Performance
-----------

Consider using `MemcachedCache` or `ApcCache` to improve performance. When a cache is present, Hyphenator will cache
calls to the `hyphenate()` method and only translate a text if needed. Hyphenator will also cache the conversion of
language patterns after including, which is CPU-heavy.