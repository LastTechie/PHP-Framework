<?php

/**
 *   This is the base class for Models. Usually, models don't share too much
 *   with eachother. Most implementations will be adding methods onto this 
 *   ckass. This class just provides helper methods for common functions.
*/
abstract class Model
{
        
        /**
         *   This variable is the cache object.
         */
	protected $_cache = null;
        
        
	/**
	 *   This varaible is the database object
	 */
	protected $_db = null;
        
        
	/**
	 *   This variable will store local, instance-specific cached data 
         *   for each model. This data is generally treated as canonical.
	 */
	protected $_localCacheData = array();
        
        
	/**
	 *   This variable is the standard approach to caching other model 
         *   objects for the lifetime of the model.
	 */
	protected $_modelCache = array();
        
        
	/**
	 *   Constructor. Use create() statically unless you know what you're doing.
	 */
	public function __construct()
	{
	}
        
        
        /**
	 *   This varaible is the factory method to get the named model. The class
         *   must exist or be autoloadable or an exception will be thrown.
	 */
	public static function create($class)
	{
		$createClass = Application::resolveDynamicClass($class, 'model');
                
		if (!$createClass)
		{
			throw new Exception("Invalid model '$class' specified");
		}

		return new $createClass;
	}
        
        
	/**
	 *   This function will get the named entry from the local cache.
	 */
	protected function _getLocalCacheData($name)
	{
		return isset($this->_localCacheData[$name]) ? $this->_localCacheData[$name] : false;
	}
        
        
	/**
	 *   This function will get the specified model object from the 
         *   cache. If it does not exist, it will be instantiated.
	 */
	public function getModelFromCache($class)
	{
		if (!isset($this->_modelCache[$class]))
		{
			$this->_modelCache[$class] = Model::create($class);
		}

		return $this->_modelCache[$class];
	}
        
        
	/**
	 *   This function is the helper method to get the database object.
	 */
	protected function _getDb()
	{
		if ($this->_db === null)
		{
			$this->_db = Application::getDb();
		}

		return $this->_db;
	}
        
        
	/**
	 *   This function fetches results from the database with each row keyed 
         *   according to preference. The 'key' parameter provides the column name 
         *   with which to key the result.
         *   
         *   For example, calling fetchAllKeyed('SELECT item_id, title, date FROM table', 'item_id')
	 *   would result in an array keyed by item_id:
	 *   [$itemId] => array('item_id' => $itemId, 'title' => $title, 'date' => $date)
	 *
	 *   Note that the specified key must exist in the query result, or it will be ignored.
	 */
	public function fetchAllKeyed($sql, $key, $bind = array(), $nullPrefix = '')
	{
		$results = array();
		$i = 0;

		$stmt = $this->_getDb()->query($sql, $bind, Zend_Db::FETCH_ASSOC);
		while ($row = $stmt->fetch())
		{
			$i++;
			$results[(isset($row[$key]) ? $row[$key] : $nullPrefix . $i)] = $row;
		}

		return $results;
	}
        
        
	/**
	 *   This function spplies a limit clause to the provided query if a limit value is
         *   specified. If the limit value is 0 or less, no clause is applied.
	 */
	public function limitQueryResults($query, $limit, $offset = 0)
	{
		if ($limit > 0)
		{
			if ($offset < 0)
			{
				$offset = 0;
			}
			return $this->_getDb()->limit($query, $limit, $offset);
		}
		else
		{
			return $query;
		}
	}
        
        
	/**
	 *   This function will add a join to the set of fetch options. Join should be 
         *   one of the constants.
	 */
	public function addFetchOptionJoin(array &$fetchOptions, $join)
	{
		if (isset($fetchOptions['join']))
		{
			$fetchOptions['join'] |= $join;
		}
		else
		{
			$fetchOptions['join'] = $join;
		}
	}

        
		if (XenForo_Application::isRegistered('contentTypes'))
		{
			$contentTypes = XenForo_Application::get('contentTypes');
		}
		else
		{
			$contentTypes = XenForo_Model::create('XenForo_Model_ContentType')->getContentTypesForCache();
			XenForo_Application::set('contentTypes', $contentTypes);
		}

		$output = array();
		foreach ($contentTypes AS $contentType => $fields)
		{
			if (isset($fields[$fieldName]))
			{
				$output[$contentType] = $fields[$fieldName];
			}
		}

		return $output;
	}
        
        
	/**
	 *   This function will ensure that a valid cut-off operator is passed.
	 */
	public function assertValidCutOffOperator($operator, $allowBetween = false)
	{
		switch ($operator)
		{
			case '<':
			case '<=':
			case '=':
			case '>':
			case '>=':
				break;

			case '>=<':
				if ($allowBetween)
				{
					return;
				}
				//  The break missing intentionally

			default:
				throw new XenForo_Exception('Invalid cut off operator.');
		}
	}
}
