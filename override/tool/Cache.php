<?php

namespace tool;
use Utils;
/**
 * @override
 * will obscure Greg's class
 * 
 */
class Cache
{

	const SUPERSHORT			= 60;					//  1 minute
	const VERYSHORT				= 300;				//  5 minutes
	const SHORT						= 600;				// 10 minutes
	const MEDIUM					= 900;				// 15 minutes
	const LONG						= 1800;				// 30 minutes
	const VERYLONG				= 3600;				//  1 hour
	const SUPERLONG				= 86400;			//  1 day
	const DEFAULT_GROUP		= 'default';
	const DEFAULT_KEY			= 'default';	// We should never have to rely on the default key
	const DEFAULT_EXPIRE	= 900;				// 15 minutes

	static protected $cache = array();
	static protected $cache_group;
	static protected $cache_key;
	static protected $cache_expire;
	static protected $cache_db = false;

	/**
	 * Sets up Object Cache Global and assigns it.
	 *
	 * @global $object_cache Generic Object Cache
	 */
	static function init()
	{
	    Utils::log(__METHOD__ . " FAKE_CACHE");
		/*static::$cache_group = self::DEFAULT_GROUP;
		static::$cache_key = self::DEFAULT_KEY;
		static::$cache_expire = self::DEFAULT_EXPIRE;
		static::$cache_db = false;
		static::$cache = new \tool\Cache\CacheWrapper('SESSION');*/
	}

	/**
	 * Validate data used to add/get/remove/delete items to the cache.
	 * Here we provide the missing info if needed.
	 *
	 * @param array $options Various options [group|key|data|expire]
	 * @return false|true
	 */
	static function valid($options = array())
	{
	    Utils::log(__METHOD__ . " FAKE_CACHE");
		/*if(!isset($options['group']) || (isset($options['group']) && (empty($options['group']) || ($options['group'] == null))))
		{
			if (!static::isDBQuery())
				static::$cache_group = static::DEFAULT_GROUP;
		}
		else
			static::$cache_group = trim($options['group']);

		// I hope the following never happens, because that would means trying to overwrite the same value all the time
		if(!isset($options['key']) || (isset($options['key']) && (empty($options['key']) || ($options['key'] == null))))
			static::$cache_key = static::DEFAULT_KEY;
		else
			static::$cache_key = trim($options['key']);

		if(!isset($options['expire']) || (isset($options['expire']) && (empty($options['expire']) || ($options['expire'] == null))))
			static::$cache_expire = static::DEFAULT_EXPIRE;
		else
			static::$cache_expire = intval($options['expire']);*/

		return true;
	}

	/**
	 * Reset defaults.
	 * reset all cache values.
	 *
	 * @param array $options Various options [group|key|data|expire]
	 * @return false|true
	 */
	static function reset_vars()
	{
		/*static::$cache_group = self::DEFAULT_GROUP;
		static::$cache_key = self::DEFAULT_KEY;
		static::$cache_expire = self::DEFAULT_EXPIRE;
		static::$cache_db = false;*/
	}

		/**
	 * Utility function to determine whether a key exists in the cache.
	 *
	 * @param string $group Where the cache contents are grouped
	 * @param int|string $key What the contents in the cache are called
	 */
	static function exists($options = array())
	{
		/*if(static::valid($options))
		{
			return static::$cache->exists(static::$cache_group, static::$cache_key);
		}
		else*/
			return false;
	}

/**
	 * Adds data to the cache, if the cache key doesn't already exist.
	 *
	 * @uses $object_cache Generic Object Cache
	 * @see CacheWrapper->set()
	 *
	 * @param string $group The group to add the cache to
	 * @param int|string $key The cache key to use for retrieval later
	 * @param mixed $data The data to add to the cache store
	 * @param int $expire When the cache data should be expired
	 * @return unknown
	 */
	static function add($options = array())
	{
	    Utils::log(__METHOD__ . " FAKE_CACHE");
		/*if(static::valid($options))
		{
			$toreturn = static::$cache->add(static::$cache_group, static::$cache_key, $options['data'], static::$cache_expire);
			static::reset_vars();
			return $toreturn;
		}
		else
			return false;*/
	}

	/**
	 * Replaces the contents of the cache with new data.
	 *
	 * @uses $object_cache Generic Object Cache
	 * @see CacheWrapper->replace()
	 *
	 * @param string $group Where to group the cache contents
	 * @param int|string $key What to call the contents in the cache
	 * @param mixed $data The contents to store in the cache
	 * @param int $expire When to expire the cache contents
	 * @return bool False if cache key and group already exist, true on success
	 */
	static function replace($options = array())
	{
	    Utils::log(__METHOD__ . " FAKE_CACHE");
		/*if(static::valid($options))
		{
			$toreturn = static::$cache->replace(static::$cache_group, static::$cache_key, $options['data'], static::$cache_expire);
			static::reset_vars();
			return $toreturn;
		}
		else
			return false;*/
	}

	/**
	 * Retrieves the cache contents from the cache by key and group.
	 *
	 * @uses $object_cache Generic Object Cache
	 * @see CacheWrapper->get()
	 *
	 * @param string $group Where the cache contents are grouped
	 * @param int|string $key What the contents in the cache are called
	 * @return bool|mixed False on failure to retrieve contents or the cache
	 * 		contents on success
	 */
	static function get($options = array())
	{
	    Utils::log(__METHOD__ . " FAKE_CACHE");
		/*if(static::valid($options))
		{
			$toreturn = static::$cache->get(static::$cache_group, static::$cache_key);
			static::reset_vars();
			return $toreturn;
		}
		else*/
			return false;
	}

	/**
	 * Removes the cache contents matching key and group.
	 *
	 * @uses $object_cache Generic Object Cache
	 * @see CacheWrapper->delete()
	 *
	 * @param string $group Where the cache contents are grouped
	 * @param int|string $key What the contents in the cache are called
	 * @return bool True on successful removal, false on failure
	 */
	static function delete($options = array())
	{
	    Utils::log(__METHOD__ . " FAKE_CACHE");
	    return true;
		/*if(static::valid($options))
		{
			$toreturn = static::$cache->delete(static::$cache_group, static::$cache_key);
			static::reset_vars();
			return $toreturn;
		}
		else
			return false;*/
	}

	/**
	 * Clears a group from the cache and write a new timestamp in the
	 * database so that anyone with obsolete data gets it removed.
	 *
	 * @uses $object_cache Generic Object Cache
	 * @see CacheWrapper->clear_group()
	 *
	 * @param string $group Where the cache contents are grouped
	 * @return bool True on successful removal, false on failure
	 */
	static function clear_group($group)
	{
	    Utils::log(__METHOD__ . " FAKE_CACHE");
		/*if (!is_array($group))
			$groupList = array($group);
		else
			$groupList = $group;
		if (!empty($groupList))
		{
			foreach($groupList AS $onegroup)
			{
				$success = static::$cache->clear_group($onegroup);
			}
		}
		else
			$success = false;
		return $success;*/
	    return true;
	}

	/**
	 * Removes all cache items.
	 *
	 * @uses $object_cache Generic Object Cache
	 * @see CacheWrapper->clear()
	 *
	 * @return bool Always returns true
	 */
	static function clear()
	{
	    Utils::log(__METHOD__ . " FAKE_CACHE");
		/*return static::$cache->clear();
		static::reset_vars();*/
	}

	/**
	 * Echoes the stats of the caching.
	 *
	 * Gives the cache hits, and cache misses. Also prints every cached group,
	 * key and the size of the data.
	 *
	 * @uses $object_cache Generic Object Cache
	 * @see CacheWrapper->stats()
	 */
	static function html_stats()
	{
	    Utils::log(__METHOD__ . " FAKE_CACHE");
		echo '';// static::$cache->html_stats();
	}

	/**
	 * Echoes the content of the cache.
	 *
	 * @uses $object_cache Generic Object Cache
	 * @see CacheWrapper->print_content()
	 */
	static function content()
	{
	    Utils::log(__METHOD__ . " FAKE_CACHE");
		echo '';// static::$cache->print_content();
	}

	/**
	 * Returns cache hits.
	 *
	 * @uses $object_cache Generic Object Cache
	 * @see CacheWrapper->stat()
	 */
	static function hits()
	{
	    Utils::log(__METHOD__ . " FAKE_CACHE");
		return '';// static::$cache->stat('hits', true);
	}

	/**
	 * Returns cache misses.
	 *
	 * @uses $object_cache Generic Object Cache
	 * @see CacheWrapper->stat()
	 */
	static function misses()
	{
		return '';// static::$cache->stat('misses', true);
	}

	/**
	 * Returns cache size.
	 *
	 * @uses $object_cache Generic Object Cache
	 * @see CacheWrapper->stat()
	 */
	static function size()
	{
	    Utils::log(__METHOD__ . " FAKE_CACHE");
		return 0;// static::$cache->stat('size', true);
	}

	static function isDBQuery()
	{
	    Utils::log(__METHOD__ . " FAKE_CACHE");
		return false;// static::$cache_db;
	}

	static function setQueryExpireGroup($seconds = 0, $group = 'database')
	{
	    Utils::log(__METHOD__ . " FAKE_CACHE");
		/*static::$cache_expire = intval($seconds);
		if(empty($group) || ($group === null) || ($group === false))
			$group = 'database';
		static::$cache_group = trim((string) $group);
		static::$cache_db = true;*/
		//\Utils::log('Setting Query Cache for group: '.static::$cache_group.' and expire:'.static::$cache_expire, true);
	}

}
