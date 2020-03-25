<?php
class ControllerCommonFooterMenu extends Controller {
	public function index() {
		$this->load->model('catalog/category');

		$data['categories'] = $this->model_catalog_category->getCategoriesTree(0, 0);

		return $this->load->view('common/footer_menu', $data);
	}
}
