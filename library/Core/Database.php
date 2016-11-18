<?php

/**
 *   This class is the database helper method. This class is to help support
 *   nested transactions.
 */
 
class Database
{
        
	/**
	 *   This varaible is the save point for each of the database objects.
	 */
	protected static $_savepoint = array();
        
        
	/**
	 *   This variable is a constant for representing the initial transaction 
         *   for the web application or framework.
	 */
	const INITIAL_TRANSACTION = 'INITIAL_TRANSACTION';
        
        
	/**
	 *   This function will start a new transaction. If there is one already
         *   going on, then it will create a savepoint instead.
	 */
	public static function beginTransaction(Zend_Db_Adapter_Abstract $database = null)
	{
		if ($database === null)
		{
			$database = Application::getDb();
		}

		$objectId = spl_object_hash($database);
                
		if (!isset(self::$_savepoint[$objectId]))
		{
			self::$_savepoint[$objectId] = array();
		}

		if (sizeof(self::$_savepoint[$objectId]) < 1)
		{
			$database->beginTransaction();
			array_push(self::$_savepoint[$objectId], self::INITIAL_TRANSACTION);
		}
		else
		{
			$savepointName = 'xf' . md5(uniqid());
			self::_execQuery($database, 'SAVEPOINT ' . $savepointName);
			array_push(self::$_savepoint[$objectId], $savepointName);
		}

	}
        
        
	/**
	 *   This function will return true if we are currently in an active transaction. It
         *   will apply only if the transactions were managed by this class only.
	 */
	public static function inTransaction(Zend_Db_Adapter_Abstract $database = null)
	{
		if ($database === null)
		{
			$database = Application::getDb();
		}
		$objectId = spl_object_hash($database);

		return !empty(self::$_savepoint[$objectId]);
	}
        
        
	/**
	 *   This function will commit the current savepoint or the main transaction.
	 */
	public static function commit(Zend_Db_Adapter_Abstract $database = null)
	{
		if ($database === null)
		{
			$database = Application::getDb();
		}
                
		$objectId = spl_object_hash($database);

		if (empty(self::$_savepoint[$objectId]))
		{
			//  There is no log of the transaction.
                        //  We will try to commit anyway.
			$database->commit();
		}
		else
		{
			$savepointName = array_pop(self::$_savepoint[$objectId]);
			if ($savepointName == self::INITIAL_TRANSACTION)
			{
				$database->commit();
			}
			else
			{
				self::_execQuery($database, 'RELEASE SAVEPOINT ' . $savepointName);
			}
		}
	}
        
        
	public static function ping(Zend_Db_Adapter_Abstract $database = null)
	{
		if ($database === null)
		{
			$database = Application::getDb();
		}

		try
		{
			return $database->fetchOne('SELECT 1') ? true : false;
		}
		catch (Zend_Db_Exception $e)
		{
			return false;
		}
	}

	protected static function _execQuery(Zend_Db_Adapter_Abstract $database, $query)
	{
		if ($database instanceof Zend_Db_Adapter_Mysqli)
		{
			$database->getConnection()->query($query);
		}
		else if ($database instanceof Zend_Db_Adapter_Pdo_Mysql)
		{
			$database->getConnection()->exec($query);
		}
	}
}
