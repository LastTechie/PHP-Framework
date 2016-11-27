<?php
 
 
class Authentication_Core16 extends Authentication_Abstract
{
        
	/**
	 *   This variable is the password info for this authentication object
	 */
	protected $_data = array();
        
        
	/**
	 *   This function will initialize data for the authentication object.
	 */
	public function setData($data)
	{
		$this->_data = unserialize($data);
	}
        
        
	/**
	 *   This function will generate new authentication data
	 */
	public function generate($password)
	{
		$passwordHash = new PasswordHash();
		$output = array('hash' => $passwordHash->HashPassword($password));
		return serialize($output);
	}
        
        
	/**
	 *   This function will authenticate against the given password
	 */
	public function authenticate($userId, $password)
	{
		if (!is_string($password) || $password === '' || empty($this->_data))
		{
			return false;
		}

		$passwordHash = new PasswordHash();
		return $passwordHash->CheckPassword($password, $this->_data['hash']);
	}
}
