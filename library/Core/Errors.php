<?php

/**
 *   This class is a helper class for handling exceptional error 
 *   conditions. The handlers here are assumed to be for fatal errors.
 */
abstract class Error
{
        
	public static function errorPageHeaders()
	{
		@header('Content-Type: text/html; charset=utf-8', true, 500);
		@header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
		@header('Cache-control: private, max-age=0, no-cache, must-revalidate');
		@header('Pragma: no-cache');
	}
        
        
	public static function noControllerResponse(Zend_Controller_Request_Http $request)
	{
		self::errorPageHeaders();

		if (Application::debugMode())
		{
			echo 'Failed to get controller response and reroute to error handler ('
				. $routeMatch->getControllerName() . '::action' . $routeMatch->getAction() . ')';

			if ($request->getParam('_exception'))
			{
				echo self::getExceptionTrace($request->getParam('_exception'));
			}
		}
		else
		{
			echo self::_getPhrasedTextIfPossible(
				'An unexpected error occurred. Please try again later.',
				'unexpected_error_occurred'
			);
		}
	}
        
        
	/**
	 *   This function will log a debugging message into the server error logs,
         *   provided that debug mode is currently being used.
	 */
	public static function debug($message)
	{
		if (!Application::debugMode())
		{
			return;
		}

		$args = func_get_args();

		self::logException(
			new Exception(call_user_func_array('sprintf', $args)),
			false
		);
	}

	/**
	 *   This function will log an error message into the server error log.
         *   The arguments are identical of sprintf.
	 */
	public static function logError($message)
	{
		if (!Application::debugMode())
		{
			return;
		}

		$args = func_get_args();

		self::logException(
			new Exception(call_user_func_array('sprintf', $args)),
			false
		);
	}
		$isValidArg = ($e instanceof Exception || $e instanceof Throwable);
		if (!$isValidArg)
		{
			throw new Exception("getExceptionTrace requires an Exception or a Throwable");
		}

		$cwd = str_replace('\\', '/', getcwd());

		if (PHP_SAPI == 'cli')
		{
			$file = str_replace("$cwd/library/", '', $e->getFile());
			$trace = str_replace("$cwd/library/", '', $e->getTraceAsString());

			return PHP_EOL . "An exception occurred: {$e->getMessage()} in {$file} on line {$e->getLine()}" . PHP_EOL . $trace . PHP_EOL;
		}

		$traceHtml = '';

		foreach ($e->getTrace() AS $traceEntry)
		{
			$function = (isset($traceEntry['class']) ? $traceEntry['class'] . $traceEntry['type'] : '') . $traceEntry['function'];
			if (isset($traceEntry['file']))
			{
				$file = str_replace("$cwd/library/", '', str_replace('\\', '/', $traceEntry['file']));
			}
			else
			{
				$file = '';
			}
			$traceHtml .= "\t<li><b class=\"function\">" . htmlspecialchars($function) . "()</b>" . (isset($traceEntry['file']) && isset($traceEntry['line']) ? ' <span class="shade">in</span> <b class="file">' . $file . "</b> <span class=\"shade\">at line</span> <b class=\"line\">$traceEntry[line]</b>" : '') . "</li>\n";
		}

		$message = htmlspecialchars($e->getMessage());
		$file = htmlspecialchars($e->getFile());
		$line = $e->getLine();

		return "<p>An exception occurred: $message in $file on line $line</p><ol>$traceHtml</ol>";
	}
}
