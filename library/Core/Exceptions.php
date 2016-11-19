<?php

/**
 *   This is a basic exception handler class for the framework and/or
 *   web application. It has support for throwing errors that are for
 *   users. It can even throw multiple messages together in one whole
 *   exception for the user.
 */
class Exception extends Exception
{
        
	protected $_Printable = false;
	protected $_messages = null;
        
        
	/**
	 *   This is the class constructor.
	 */
	public function __construct($message, $userPrintable = false)
	{
		$this->_Printable = (boolean)$userPrintable;

		if (is_array($message) && count($message) > 0)
		{
			$this->_messages = $message;
			$message = reset($message);
		}

		parent::__construct($message);
	}
        
        
	/**
	 *   This function will determine whether the exception is printable.
	 */
	public function isUserPrintable()
	{
		return $this->_Printable;
	}
        
        
	/**
         *   This function will get all messages that are apart of the exception. If 
         *   there is a non-empty array that is passed to the constructor, then this 
         *   will return an array. By default, it will be a string.
	 */
	public function getMessage()
	{
		if (is_array($this->_messages))
		{
			return $this->_messages;
		}
		else
		{
			return $this->getMessage();
		}
	}
}
