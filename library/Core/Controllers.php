<?php

/**
 *   This is the general class for controllers. Controllers implement method named
 *   ActionX with no arguments. These will be called by the dispatcher. They should
 *   return the object by responseReroute(), responseError(), and responseView().
 *   
 *   All reponses will take paramaters that will be passed to the container view.
 *   An exmaple of this is a two-phase view, if there is one.
 */
abstract class Controller
{
        
	/**
	 *   This varaible is the request object.
	 */
	protected $_request;
        
        
	/**
	 *   This varaible is the response object.
	 */
	protected $_response;
        
        
	/**
	 *   This varaible is the route match object for this request.
	 */
	protected $_routeMatch;
        
        
	/**
	 *   This varaible is the input object.
	 */
	protected $_input;
        
        
	/**
	 *   This varaible is the standard approach to caching model objects
         *   for the lifetime of the controller.
	 */
	protected $_modelCache = array();
        
        
	/**
	 *   This varaible is the list of explicit changes to the view state. View
         *   state changes are specific to the dependency manager, but may include 
         *   things like changing the styleId.
	 */
	protected $_viewStateChanges = array();
        
        
	/**
	 *   This varaible is the container for various items that have been "executed"
         *   in one controller and shouldn't be executed again in this request.
	 */
	protected static $_executed = array();
        

	/**
	 *   Class Constructor
	 */
	public function __construct(Zend_Controller_Request_Http $request, 
                                    Zend_Controller_Response_Http $response, 
                                    RouteMatch $routeMatch)
	{
		$this->_request = $request;
		$this->_response = $response;
		$this->_routeMatch = $routeMatch;
		$this->_input = new Input($this->_request);
	}
        
        
	/**
	 *   This function will get the specified model object from the cache. If it 
         *   does not exist, it will be instantiated.
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
	 *   This function will get the request object.
	 */
	public function getRequest()
	{
		return $this->_request;
	}

	/**
	 *   This function will get the input object.
	 */
	public function getInput()
	{
		return $this->_input;
	}
        
        
	/**
	 *   This function will set a change to the view state.
	 */
	public function setViewStateChange($state, $data)
	{
		$this->_viewStateChanges[$state] = $data;
	}

	/**
	 *   This function will get all the view state changes.
	 */
	public function getViewStateChanges()
	{
		return $this->_viewStateChanges;
	}

	/**
	 *   This function will get the type of response that 
         *   has been requested.
	 */
	public function getResponseType()
	{
		return $this->_routeMatch->getResponseType();
	}

	/**
	 *   This function will get the route match for this request. This 
         *   can be modified to change the response type, and the major/minor
         *   sections that will be used to setup navigation.
	 */
	public function getRouteMatch()
	{
		return $this->_routeMatch;
	}
        
        
	/**
	 *   This function is called immediately before an action is dispatched.
	 */
	final public function preDispatch($action, $controllerName)
	{
		$this->_preDispatchFirst($action);

		$this->_setupSession($action);
		$this->_checkCsrf($action);
		$this->_handlePost($action);

		$this->_preDispatchType($action);
		$this->_preDispatch($action);

		Event::fire('controller_pre_dispatch', array($this, $action, $controllerName), $controllerName);
	}
        
        
	/**
	 *   This function is called immediately after an action is dispatched.
	 */
	final public function postDispatch($controllerResponse, $controllerName, $action)
	{
		$this->updateSession($controllerResponse, $controllerName, $action);
		$this->updateSessionActivity($controllerResponse, $controllerName, $action);
		$this->_postDispatchType($controllerResponse, $controllerName, $action);
		$this->_postDispatch($controllerResponse, $controllerName, $action);

		Event::fire('controller_post_dispatch', array($this, $controllerResponse, $controllerName, $action), $controllerName);
	}
        
        
	/**
	 *   This function is the controller response for when you want to reroute to 
         *   a different controller/action.
	 */
	public function responseReroute($controllerName, $action, array $containerParams = array())
	{
		if (is_object($controllerName))
		{
			$controllerName = get_class($controllerName);
		}

		$controllerResponse = new ControllerResponse_Reroute();
		$controllerResponse->controllerName = $controllerName;
		$controllerResponse->action = $action;
		$controllerResponse->containerParams = $containerParams;

		return $controllerResponse;
	}

		switch ($redirectType)
		{
			case XenForo_ControllerResponse_Redirect::RESOURCE_CREATED:
			case XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED:
			case XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL:
			case XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT:
			case XenForo_ControllerResponse_Redirect::SUCCESS:
				break;

			default:
				throw new XenForo_Exception('Unknown redirect type');
		}

		$controllerResponse = new XenForo_ControllerResponse_Redirect();
		$controllerResponse->redirectType = $redirectType;
		$controllerResponse->redirectTarget = $redirectTarget;
		$controllerResponse->redirectMessage = $redirectMessage;
		$controllerResponse->redirectParams = $redirectParams;

		return $controllerResponse;
	}

	/**
	 *   This function is the controller response for when you want to throw an error 
         *   and display it to the user.
	 */
	public function responseError($error, $responseCode = 200, array $containerParams = array())
	{
		$controllerResponse = new ControllerResponse_Error();
		$controllerResponse->errorText = $error;
		$controllerResponse->responseCode = $responseCode;
		$controllerResponse->containerParams = $containerParams;

		return $controllerResponse;
	}


	/**
	 *   This function will get the exception object for controller response-style 
         *   behavior. This object cannot be returned from the controller; an exception 
         *   must be thrown with it.
	 *
	 * This allows any type of controller response to be invoked via an exception.
	 */
	public function responseException(ControllerResponse_Abstract $controllerResponse, $responseCode = null)
	{
		if ($responseCode)
		{
			$controllerResponse->responseCode = $responseCode;
		}
		return new ControllerResponse_Exception($controllerResponse);
	}

	/**
	 *   This function is the controller response for when you want to output using a view class.
	 */
	public function responseView($viewName = '', $templateName = '', array $params = array(), array $containerParams = array())
	{
		$controllerResponse = new ControllerResponse_View();
		$controllerResponse->viewName = $viewName;
		$controllerResponse->templateName = $templateName;
		$controllerResponse->params = $params;
		$controllerResponse->containerParams = $containerParams;

		return $controllerResponse;
	}
        
        
	/**
	 *   This function will create the specified helper class. If no underscore is 
         *   present in the class name, "ControllerHelper_" is prefixed. Otherwise,
         *   a full class name is assumed.
	 */
	public function getHelper($class)
	{
		if (strpos($class, '_') === false)
		{
			$class = 'ControllerHelper_' . $class;
		}

		return new $class($this);
	}
        
        
	/**
	 *   This function will get a valid record or throws an exception.
	 */
	public function getRecordOrError($id, $model, $method)
	{
		$info = $model->$method($id);
		if (!$info)
		{
			throw $this->responseException();
		}

		return $info;
	}
		$redirect = $this->_input->filterSingle('redirect', XenForo_Input::STRING);
		if (!$redirect && $useReferrer)
		{
			$redirect = $this->_request->getServer('HTTP_X_AJAX_REFERER');
			if (!$redirect)
			{
				$redirect = $this->_request->getServer('HTTP_REFERER');
			}
		}

		if ($redirect)
		{
			$redirect = strval($redirect);
			if (strlen($redirect) && !preg_match('/./u', $redirect))
			{
				$redirect = utf8_strip($redirect);
			}

			if (strpos($redirect, "\n") === false && strpos($redirect, "\r") === false) {
				$fullRedirect = XenForo_Link::convertUriToAbsoluteUri($redirect, true);
				$redirectParts = @parse_url($fullRedirect);
				if ($redirectParts && !empty($redirectParts['host']))
				{
					$paths = XenForo_Application::get('requestPaths');
					$pageParts = @parse_url($paths['fullUri']);

					if ($pageParts && !empty($pageParts['host']) && $pageParts['host'] == $redirectParts['host'])
					{
						return $fullRedirect;
					}
				}
			}
		}

		if ($fallbackUrl === false)
		{
			if ($this instanceof XenForo_ControllerAdmin_Abstract)
			{
				$fallbackUrl = XenForo_Link::buildAdminLink('index');
			}
			else
			{
				$fallbackUrl = XenForo_Link::buildPublicLink('index');
			}
		}

		return $fallbackUrl;
	}
		if ($referer = $this->_request->getServer('HTTP_REFERER'))
		{
			$refererParts = @parse_url($referer);
			if ($refererParts && !empty($refererParts['host']))
			{
				$paths = XenForo_Application::get('requestPaths');
				$requestParts = @parse_url($paths['fullUri']);

				if ($requestParts && !empty($requestParts['host']))
				{
					if ($refererParts['host'] != $requestParts['host'])
					{
						// referer is not the same as request host
						return false;
					}
				}
			}
		}

		//  Either we have the same host and referer, or we just don't know...
		return true;
	}
}
