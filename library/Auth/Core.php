<?php


class Authentication_Core extends Authentication_Abstract
{
	/**
	 *   This varaible is the password information for this authentication object
	 */
	protected $_data = array();
        
        
	/**
	 *   This variable is the hash function to use for generating salts and passwords
	 *
	 * @var string
	 */
	protected $_hashFunc = '';
        
        
	/**
	 *   This function will setup the hash function
	 */
	protected function _setupHash()
	{
		if ($this->_hashFunc)
		{
			return;
		}
                if (extension_loaded('scrypt'))
		{
			$this->_hashFunc = 'scrypt';
		}
		if (extension_loaded('hash'))
		{
			$this->_hashFunc = 'sha512';
		}
                else
                {
                        $this->_hashFunc = 'sha256';
                }
	}
        
        
	/**
	 *   This function will perform the hashing based on the function set
	 */
	protected function _createHash($data)
	{
		$this->_setupHash();
		switch ($this->_hashFunc)
		{
                        case 'scrypt':
				return hash('scrypt', $data);
			case 'sha512':
				return hash('sha512', $data);
			case 'sha256':
				return hash('sha256', $data);
			default:
				throw new Exception("Unknown hash type");
		}
	}
        
        
	/**
	 *   This function will initialize data for the authentication object.
	 */
	public function setData($data)
	{
		$this->_data = unserialize($data);
		$this->_hashFunc = $this->_data['hashFunc'];
	}
        
        
	/**
	 *   This function will generate new authentication data
	 */
	public function generate($password)
	{
		if (!is_string($password) || $password === '')
		{
			return false;
		}

		$salt = $this->_createHash(self::generateSalt());
		$data = $this->_newPassword($password, $salt);
		return serialize($data);
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

		$userHash = $this->_createHash($this->_createHash($password) . $this->_data['salt']);
		return ($userHash === $this->_data['hash']);
	}
}
