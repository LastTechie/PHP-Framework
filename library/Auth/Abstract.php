<?php

abstract class Authentication_Abstract
{
        
	/**
	 *   This variable sets the default Salt Length
	 */
	const DEFAULT_SALT_LENGTH = 32;
        
        
        /**
	 *   This function will Generate a psuedo-random string of the specified length.
	 */
	public static function generateRandomString($length, $raw = false)
	{
		$mixInternal = false;

		while (strlen(self::$_randomData) < $length)
		{
			if (function_exists('openssl_random_pseudo_bytes')
				&& (substr(PHP_OS, 0, 3) != 'WIN' || version_compare(phpversion(), '5.3.4', '>='))
			)
			{
				self::$_randomData .= openssl_random_pseudo_bytes($length);
				$mixInternal = true;
			}
			else if (function_exists('mcrypt_create_iv') && version_compare(phpversion(), '5.3.0', '>='))
			{
				self::$_randomData .= mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
				$mixInternal = true;
			}
			else if (substr(PHP_OS, 0, 3) != 'WIN'
				&& @file_exists('/dev/urandom') && @is_readable('/dev/urandom')
				&& $fp = @fopen('/dev/urandom', 'r')
			)
			{
				if (function_exists('stream_set_read_buffer'))
				{
					stream_set_read_buffer($fp, 0);
				}

				self::$_randomData .= fread($fp, $length);
				fclose($fp);
				$mixInternal = true;
			}
			else
			{
				self::$_randomData .= self::generateInternalRandomValue();
			}
		}

		$return = substr(self::$_randomData, 0, $length);
		self::$_randomData = substr(self::$_randomData, $length);

		//  Tehre has been situations where duplicates may be read...
                //  We are are going to mix in another source.
		if ($mixInternal)
		{
			$final = '';
			foreach (str_split($return, 16) AS $i => $part)
			{
				$internal = uniqid(mt_rand());
				if ($i % 2 == 0)
				{
					$final .= md5($part . $internal, true);
				}
				else
				{
					$final .= md5($internal . $part, true);
				}
			}

			$return = substr($final, 0, $length);
		}

		if ($raw)
		{
			return $return;
		}

		// modified base64 to be more URL safe (roughly in rfc4648)
		return substr(strtr(base64_encode($return), array(
			'=' => '',
			"\r" => '',
			"\n" => '',
			'+' => '-',
			'/' => '_'
		)), 0, $length);
	}
        
        
        /**
	 *   This function will generate a random number using internal methods only.
	 *
	 * @return string
	 */
	public static function generateInternalRandomValue()
	{
		if (!self::$_randomState)
		{
			self::$_randomState = md5(memory_get_usage() . getmypid()
				. serialize($_ENV) . serialize($_SERVER) . mt_rand() . microtime(), true);
		}

		$data = md5(uniqid(mt_rand(), true) . memory_get_usage() . microtime() . self::$_randomState, true);
		self::$_randomState = substr($data, 0, 8);

		return $data;
	}
        
        
	/**
	 *   This function will initialize data for the authentication object.
	 */
	abstract public function setData($data);
        
        
	/**
	 * This function will perform authentication against the given password
	 */
	abstract public function authenticate($userId, $password);
        
        
	/**
	 *   This function will generate new authentication data for the given password
	 */
	abstract public function generate($password);
        
        
	/**
	 *   This function will generate an arbtirary length salt
	 */
	public static function generateSalt($length = null)
	{
		if (!$length)
		{
			$length = self::DEFAULT_SALT_LENGTH;
		}

		return $generateRandomString($length);
	}
}
