<?php

class ControllerB2bApi extends Controller {

	public function index() {
		$login		= B2B_LOGIN;
		$password	= B2B_PASSWORD;
		
		$this->load->model('b2b/api');
		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('catalog/manufacturer');
		
		$time =  array();
		$session = $this->model_b2b_api->auth($login, $password);
		/* add timestamp */ $this->_timestamp('Start products loading', $time);
		
		if (!empty($session) && isset($session['success']) && $session['success']) {
			$session = $session['session'];
			
			$prices = $this->model_b2b_api->loadPrices($session);
			/* add timestamp */ $this->_timestamp('Load products Prices', $time);
			
			$static = $this->model_b2b_api->loadStatic($session);
			/* add timestamp */ $this->_timestamp('Load products Static', $time);
			
			// !!! load categories
			$categories = array();
			
			if (!isset($prices['error']) && !isset($static['error']) && !isset($categories['error'])) {
				$manufacturers = $this->model_b2b_api->updateManufacturers($static);
				/* add timestamp */ $this->_timestamp('Update Manufacturers', $time);
				
				$categories = $this->model_b2b_api->updateCategories($categories);
				/* add timestamp */ $this->_timestamp('Update Categories', $time);
				
				$output = $this->model_b2b_api->updateProducts($categories, $manufacturers, $static, $prices);
				/* add timestamp */ $this->_timestamp('Update Products', $time);
				
				$this->cache->delete('product');
			} else {
				$output = array('error');
				foreach (array('prices', 'static', 'categories') as $var) {
					if (isset($$var['error'])) {
						$output['error'][$var] = $$var;
					}
				}
			}
			
		} else {
			$output = array('error'=>array('session' => $session));
		}
		
		$output['time'] =& $time;
		$this->response->setOutput(json_encode($output));
	}

	private function _timestamp($name, &$ts=array()) {
		$data = array('name' => $name);
		$data['time']= round(microtime(true), 2);
		$data['total'] = empty($ts) ? 0 : round($data['time'] - $ts[0]['time'], 2);
		$data['delta'] = empty($ts) ? 0 : round($data['time'] - $ts[count($ts)-1]['time'], 2);
		
		$ts[] = $data;
	}
	
	public function error($msg='Data not loaded') {
		$output = array('error' => $msg);
		
		$this->response->setOutput(json_encode($output));
	}
}