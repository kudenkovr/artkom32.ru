<?php
class ControllerCommonSearch extends Controller {
	public function index() {
		$this->load->language('common/search');

		$data['text_search'] = $this->language->get('text_search');

		$data['search'] = isset($this->request->get['search']) ? $this->request->get['search'] : '';
		if (!empty($this->request->get['path']) && !empty($this->request->get['route']) && $this->request->get['route']=='product/category') {
			$path = explode('_', $this->request->get['path']);
			$data['category_id'] = array_pop($path);
			$data['text_search'] = $this->language->get('text_category_search');
		}
		

		return $this->load->view('common/search', $data);
	}
}