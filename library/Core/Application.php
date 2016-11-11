<?php
 
 
/**
 *   This is the base application class for a web application. This class will setup the
 *   the environment as needed and acts as the registry for the application. It can also
 *   broker to the autoload as well.
 */
 
 
class Application extends Zend_Registry
{
        
	/**
         *   This variable prints out the current printable and encoded version of
         *   the framework or web application. These are used for visual outputs while
         *   installing and/or upgrade.
	 */
	public static $version   = '1.0.0';
	public static $versionID = 01000000;
        
        
	/**
	 *   This variable tells us the current jQuery version currently in use.
	 */
	public static $jQueryVersion = '1.11.0';
        
        
	/**
	 *   This variable is the path to applications root directory. We can use
         *   it to find specific directories.
	 */
	protected $_rootDir = '.';
        
        
	/**
	 *   This variable will store whether the framework or web application
         *   has been initialized yet.
	 */
	protected $_initialized = false;
        
        
	/*
	 *   This variable is for un-used lazy loaders for the registry. When a
         *   lazy loader is called, it is removed from the list. Key is the index
         *   and value is an array:
         *
	 *    0 => callback
	 *    1 => array of arguments
	 */
	protected $_lazyLoaders = array();
        
        
	/**
	 *   This variable will allow the framework or web application handle PHP 
         *   errors, warnings, and notices that come up will be handled by our 
         *   error handler. Otherwise, they will be deferred to any previously
	 *   registered handler. Usually PHP's default.
	 */
	protected static $_handlePhpErrors = true;
        
        
	/**
	 *   This variable will control whether the application is in debug mode.
	 */
	protected static $_debug;
        
        
	/**
	 *   This varaible will be a cache of random data when it is generated.
	 */
	protected static $_randomData = '';
        
        
	/**
	 *   This variable is a unix timestamp representing the current webserver 
         *   date and time. This should be used whenever 'now' needs to be referred to.
	 */
	public static $currentTime = 0;
        
        
	/**
	 *   Are we currently using SSL Certicates?
	 */
	public static $_ssl = false;
        
        
	/**
         *   This variable is the path to images, avatars, and other items. This path also must
         *   web accessable and server writable.
	 */
	public static $externalDataPath = 'data';
        
        
	/**
	 * This variable stores the URL to the location where Javascript directories are located.
	 * It can also be absolute or relative.
	 *
	 * @var string
	 */
	public static $javascriptURL = 'data/js';
        
        
	/**
	 *   This variable/array will provides some configuration options to the initialization process.
	 */
	protected static $_initSettings = array(
		'undoMagicQuotes' => true,
		'setMemoryLimit' => true,
		'resetOutputBuffering' => true
	);
        
        
	/**
	 *   This function will begin the application. This causes the environment to be setup as necessary.
	 */
	public function startApp($rootDir = '.')
	{
		if ($this->_initialized)
		{
			return;
		}

		if (!defined('PHP_VERSION_ID'))
		{
			$version = explode('.', PHP_VERSION);
			define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
		}

		if (self::$_initSettings['undoMagicQuotes'] && function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc())
		{
			self::undoMagicQuotes($_GET);
			self::undoMagicQuotes($_POST);
			self::undoMagicQuotes($_COOKIE);
			self::undoMagicQuotes($_REQUEST);
		}
                
		if (function_exists('get_magic_quotes_runtime') && get_magic_quotes_runtime())
		{
			@set_magic_quotes_runtime(false);
		}

		if (self::$_initSettings['setMemoryLimit'])
		{
			self::setMemoryLimit(64 * 1024 * 1024);
		}

		ignore_user_abort(true);

		if (self::$_initSettings['resetOutputBuffering'])
		{
			@ini_set('output_buffering', false);
			@ini_set('zlib.output_compression', 0);

			//  Reference:  http://bugs.php.net/bug.php?id=36514
			if (!@ini_get('output_handler'))
			{
				$level = ob_get_level();
                                
				while ($level)
				{
					@ob_end_clean();
					$newLevel = ob_get_level();
                                        
					if ($newLevel >= $level)
					{
						break;
					}
                                        
					$level = $newLevel;
				}
			}
		}

		error_reporting(E_ALL | E_STRICT & ~8192);
		set_error_handler(array('Application', 'handlePhpError'));
		set_exception_handler(array('Application', 'handleException'));
		register_shutdown_function(array('Application', 'handleFatalError'));

		date_default_timezone_set('UTC');

		self::$currentTime = time();

		self::$host = (empty($_SERVER['HTTP_HOST']) ? '' : $_SERVER['HTTP_HOST']);

		self::$_ssl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
                
		$this->_rootDir = $rootDir;

		self::$_ssl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');

		$this->_initialized = true;
	}
