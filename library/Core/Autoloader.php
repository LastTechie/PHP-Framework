<?php

/**
 *   This is a variable that must be made available for any other web application or
 *   framework class to be included.
 */
define('AUTOLOADER_SETUP', true);


/**
 *   This is the base autoloader class. This must be the first class loaded and/or setup
 *   as the web application/registry. It highly depends on it for loading classes.
 */
class Autoloader
{
        
	/**
	 *   This variable is the instance manager.
	 */
	protected static $_instance;
        
        
	/**
	 *   This variable is the base path to directory containing the 
         *   web application or framwork's library.
	 */
	protected $_rootDir = '.';
        
        
	/**
	 *   This bariable stores whether the autoloader has officially been
         *   setup yet. Default, no it hasn't.
	 */
	protected $_setup = false;
        
        
	/**
	 * Protected constructor.
	 */
	protected function __construct()
	{
	}
        
        
	/**
	 *   This function will setup the autoloader. This will cause the environment
         *   to be setup properly.
	 */
	public function setupAutoloader($rootDir)
	{
		if ($this->_setup)
		{
			return;
		}

		$this->_rootDir = $rootDir;
		$this->_setupAutoloader();

		$this->_setup = true;
	}
        
        
	/**
	 *   This is the internal method that actually applies to the autoloader for
         *   external usage. See setupAutoloader()
	 */
	protected function _setupAutoloader()
	{
		if (@ini_get('open_basedir'))
		{
			//  Many servers don't seem to set include_path correctly with open_basedir.
                        //  So don't use it here.
			set_include_path($this->_rootDir . PATH_SEPARATOR . '.');
		}
		else
		{
			set_include_path($this->_rootDir . PATH_SEPARATOR . '.' . PATH_SEPARATOR . get_include_path());
		}

		//  require_once('Zend/Loader/Autoloader.php');
                //  require_once 'Zend/Loader/Autoloader.php';
                //  require_once 'PhalconPHP/Autoloader.php';
                
		$autoloader = Zend_Loader_Autoloader::getInstance();
		$autoloader->pushAutoloader(array($this, 'autoload'));
		spl_autoload_register(array($this, 'autoload'));
	}
        
        
	/**
	 *   This function will autoload a specified class.
	 */
	public function autoload($class)
	{
		if (class_exists($class, false) || interface_exists($class, false))
		{
			return true;
		}

		if (substr($class, 0, 5))
		{
			throw new Exception('Cannot load class. Load the class using the correct loader first.');
		}

		$filename = $this->autoloaderClassToFile($class);
                
		if (!$filename)
		{
			return false;
		}

		if (file_exists($filename))
		{
			include($filename);
			return (class_exists($class, false) || interface_exists($class, false));
		}

		return false;
	}

	/**
	 *   This function will resolve a class name to an autoload path.
	 */
	public function autoloaderClassToFile($class)
	{
		if (preg_match('#[^a-zA-Z0-9_\\\\]#', $class))
		{
			return false;
		}

		return $this->_rootDir . '/' . str_replace(array('_', '\\'), '/', $class) . '.php';
	}
        
        
	/**
	 *   This function will get the autoloader's root directory.
	 */
	public function getRootDir()
	{
		return $this->_rootDir;
	}
        
        
	/**
	 *   This function will get the autoloader instance.
	 *
	 * @return XenForo_Autoloader
	 */
	public static final function getInstance()
	{
		if (!self::$_instance)
		{
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}
