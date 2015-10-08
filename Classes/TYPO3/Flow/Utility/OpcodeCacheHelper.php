<?php
namespace TYPO3\Flow\Utility;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * This class contains a helper to clear PHP Opcode Caches, auto-detecting the current opcache system in use.
 *
 * It has been inspired by the corresponding functionality in TYPO3 CMS (OpcodeCacheUtility.php), especially the cache-
 * invalidate functions.
 */
class OpcodeCacheHelper
{
    /**
     * Contains callback functions for all active Opcode caches which can be used to flush a file.
     *
     * @var array<\Closure>
     */
    protected static $clearCacheCallbacks = null;

    /**
     * Initialize the ClearCache-Callbacks
     *
     * @return void
     */
    protected static function initialize()
    {
        self::$clearCacheCallbacks = array();

        // Zend OpCache (built in by default since PHP 5.5) - http://php.net/manual/de/book.opcache.php
        if (extension_loaded('Zend OPcache') && ini_get('opcache.enable') === '1') {
            self::$clearCacheCallbacks[] = function ($absolutePathAndFilename) {
                if ($absolutePathAndFilename !== null && function_exists('opcache_invalidate')) {
                    opcache_invalidate($absolutePathAndFilename);
                } else {
                    opcache_reset();
                }
            };
        }

        // WinCache - http://www.php.net/manual/de/book.wincache.php
        if (extension_loaded('wincache') && ini_get('wincache.ocenabled') === '1') {
            self::$clearCacheCallbacks[] = function ($absolutePathAndFilename) {
                if ($absolutePathAndFilename !== null) {
                    wincache_refresh_if_changed(array($absolutePathAndFilename));
                } else {
                    // Refresh everything!
                    wincache_refresh_if_changed();
                }
            };
        }

        // XCache - http://xcache.lighttpd.net/
        // Supported in version >= 3.0.1
        if (extension_loaded('xcache')) {
            self::$clearCacheCallbacks[] = function ($absolutePathAndFilename) {
                // XCache can only be fully cleared.
                if (!ini_get('xcache.admin.enable_auth')) {
                    xcache_clear_cache(XC_TYPE_PHP);
                }
            };
        }
    }

    /**
     * Clear a PHP file from all active cache files. Also supports to flush the cache completely, if called without parameter.
     *
     * @param string $absolutePathAndFilename Absolute path towards the PHP file to clear.
     * @return void
     */
    public static function clearAllActive($absolutePathAndFilename = null)
    {
        if (self::$clearCacheCallbacks === null) {
            self::initialize();
        }
        foreach (self::$clearCacheCallbacks as $clearCacheCallback) {
            $clearCacheCallback($absolutePathAndFilename);
        }
    }
}
