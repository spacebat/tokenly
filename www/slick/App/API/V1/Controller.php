<?php
class Slick_App_API_V1_Controller extends Slick_Core_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->args = explode('/', $_REQUEST['params']);	
		$this->model = new Slick_Core_Model;
	}
	
	/***
	 *  Attempts to load an endpoint and checks to make sure valid request method is used
	 * 
	 * */
	public function init()
	{


		if(!isset($this->args[0]) OR empty($this->args[0])){
			http_response_code(400);
			return array('error' => 'No endpoint defined');
		}
		
		$class = 'Slick_App_API_V1_'.ucfirst($this->args[0]).'_Controller';
		if(!class_exists($class)){
			http_response_code(400);
			return array('error' => 'Invalid endpoint');
		}
		
		$endpoint = new $class;
		$endpoint->useMethod = $_SERVER['REQUEST_METHOD'];
		if(isset($endpoint->methods) AND !in_array($_SERVER['REQUEST_METHOD'], $endpoint->methods)){
			http_response_code(400);
			return array('error' => 'Invalid request method', 'methods' => $endpoint->methods);
		}


		if(preg_match('#(application\/json|text\/json)#', @$_SERVER['CONTENT_TYPE'])){
			//parse json and load into data argument
			$getInput = file_get_contents('php://input');
			unset($_REQUEST['v']);
			unset($_REQUEST['params']);

			$getData = json_decode($getInput, true);
			$this->args['data'] = $_REQUEST;
			if(is_array($getData)){
				$this->args['data'] = array_merge($getData, $_REQUEST);
			}
		}
		elseif(preg_match('#(multipart\/form\-data)#', @$_SERVER['CONTENT_TYPE']) AND ($_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'PATCH')){
			unset($_REQUEST['v']);
			unset($_REQUEST['params']);
			$getData = parseRawInput();
			
			$this->args['data'] = $_REQUEST;
			if(is_array($getData)){
				$this->args['data'] = array_merge($getData, $_REQUEST);
			}
			
		}
		else{
			//load $_REQUEST into data argument
			unset($_REQUEST['v']);
			unset($_REQUEST['params']);
			$this->args['data'] = $_REQUEST;
			if(isset($this->args['data']['authKey'])){
				unset($this->args['data']['authKey']);
			}
		}
		if(isset($_SERVER['HTTP_X_AUTHENTICATION_KEY'])){
			$this->args['data']['authKey'] = $_SERVER['HTTP_X_AUTHENTICATION_KEY'];
		}
		
		$getSite = $this->model->get('sites', $_SERVER['HTTP_HOST'], array(), 'domain');
		if(!$getSite){
			return array('error' => 'Site config not found');
		}
		$this->args['data']['site'] = $getSite;
		$_SERVER['is_api'] = true;
		
		return $endpoint->init($this->args);	
	}
	
}

?>
