<?php
class ControllerCommonMenu extends Controller {
	public function index() {
		$this->load->language('common/menu');

		// Menu
		$this->load->model('catalog/category');
		
		$data = array();
		$data['categories'] = $this->model_catalog_category->getCategoriesTree(0, 3);

		return $this->load->view('common/menu', $data);
	}
}
