<?php
namespace Elgg\Cache;

/**
 * WARNING: API IN FLUX. DO NOT USE DIRECTLY.
 *
 * @access private
 *
 * @package    Elgg.Core
 * @subpackage Cache
 * @since      1.10.0
 */
class SimpleCache {
	
	/**
	 * Global Elgg configuration
	 * 
	 * @var \stdClass
	 */
	private $CONFIG;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $CONFIG;
		$this->CONFIG = $CONFIG;
	}

	/**
	 * Registers a view to simple cache.
	 *
	 * Simple cache is a caching mechanism that saves the output of
	 * a view and its extensions into a file.  If the view is called
	 * by the {@link engine/handlers/cache_handler.php} file, the Elgg
	 * engine will not be loaded and the contents of the view will returned
	 * from file.
	 *
	 * @warning Simple cached views must take no parameters and return
	 * the same content no matter who is logged in.
	 *
	 * @param string $view_name View name
	 *
	 * @return void
	 * @see elgg_get_simplecache_url()
	 */
	function registerView($view_name) {
		elgg_register_external_view($view_name, true);
	}
	
	/**
	 * Get the URL for the cached view.
	 * 
	 * Recommended usage is to just pass the entire view name as the first and only arg:
	 *
	 * ```
	 * $blog_js = $simpleCache->getUrl('js/blog/save_draft.js');
	 * $favicon = $simpleCache->getUrl('favicon.ico');
	 * ```
	 * 
	 * For backwards compatibility with older versions of Elgg, you can also pass
	 * "js" or "css" as the first arg, with the rest of the view name as the second arg:
	 *
	 * ```
	 * $blog_js = $simpleCache->getUrl('js', 'blog/save_draft.js');
	 * ```
	 * 
	 * Note that with this older approach, there is no way to access cached views
	 * outside of the js/ or css/ folders.
	 *
	 * This automatically registers the view with Elgg's simplecache.
	 * 
	 * @param string $view    The full view name
	 * @param string $subview If the first arg is "css" or "js", the rest of the view name
	 * 
	 * @return string
	 */
	function getUrl($view, $subview = '') {
		// handle `getUrl('js', 'js/blog/save_draft.js')`
		if (($view === 'js' || $view === 'css') && 0 === strpos($subview, $view . '/')) {
			$view = $subview;
			$subview = '';
		}
		
		// handle `getUrl('js', 'blog/save_draft.js')`
		if (!empty($subview)) {
			$view = "$view/$subview";
			$subview = '';
		}
	
		// should be normalized to canonical form by now: `getUrl('js/blog/save_draft.js')`
		elgg_register_simplecache_view($view);
		return $this->getRoot() . $view;
	}
	
	/**
	 * Get the base url for simple cache requests
	 * 
	 * @return string The simplecache root url for the current viewtype.
	 * @access private
	 */
	function getRoot() {
		$viewtype = elgg_get_viewtype();
		if (elgg_is_simplecache_enabled()) {
			// stored in datalist as 'simplecache_lastupdate'
			$lastcache = (int)_elgg_services()->config->get('lastcache');
		} else {
			$lastcache = 0;
		}
	
		return elgg_normalize_url("/cache/$lastcache/$viewtype/");
	}
	
	/**
	 * Is simple cache enabled
	 *
	 * @return bool
	 */
	function isEnabled() {
		return (bool) _elgg_services()->config->get('simplecache_enabled');
	}
	
	/**
	 * Enables the simple cache.
	 *
	 * @see elgg_register_simplecache_view()
	 * @return void
	 */
	function enable() {
		_elgg_services()->datalist->set('simplecache_enabled', 1);
		_elgg_services()->config->set('simplecache_enabled', 1);
		elgg_invalidate_simplecache();
	}
	
	/**
	 * Disables the simple cache.
	 *
	 * @warning Simplecache is also purged when disabled.
	 *
	 * @see elgg_register_simplecache_view()
	 * @return void
	 */
	function disable() {
		if (_elgg_services()->config->get('simplecache_enabled')) {
			_elgg_services()->datalist->set('simplecache_enabled', 0);
			_elgg_services()->config->set('simplecache_enabled', 0);
	
			// purge simple cache
			_elgg_rmdir(_elgg_services()->config->getDataPath() . "views_simplecache");
		}
	}
	
	/**
	 * Deletes all cached views in the simplecache and sets the lastcache and
	 * lastupdate time to 0 for every valid viewtype.
	 *
	 * @return bool
	 */
	function invalidate() {
		
	
		if (!isset($this->CONFIG->views->simplecache) || !is_array($this->CONFIG->views->simplecache)) {
			return false;
		}
	
		_elgg_rmdir("{$this->CONFIG->dataroot}views_simplecache");
		mkdir("{$this->CONFIG->dataroot}views_simplecache");
	
		$time = time();
		_elgg_services()->datalist->set("simplecache_lastupdate", $time);
		$this->CONFIG->lastcache = $time;
	
		return true;
	}

	/**
	 * Set up $CONFIG appropriately on engine boot.
	 *
	 * @return void
	 */
	function init() {
		

		if (!defined('UPGRADING') && empty($this->CONFIG->lastcache)) {
			$this->CONFIG->lastcache = (int)_elgg_services()->datalist->get('simplecache_lastupdate');
		}
	}	
}