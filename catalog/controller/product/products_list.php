<?php

class ControllerProductProductsList extends Controller {

	public function index() {
		$this->load->language('product/category');
		$this->load->model('catalog/product');
		$this->load->model('catalog/category');

		
		if (isset($this->request->post['type'])) {
			$types_list = array('list', 'line', 'tile');
			$this->session->data['list_type'] = $type = in_array($this->request->post['type'], $types_list)
				? $this->request->post['type']
				: (isset($this->session->data['list_type']) ? $this->session->data['list_type'] : 'list');
			$this->response->setOutput(json_encode(array('type'=>$type, 'types_list'=>$types_list)));
		}

		elseif (!empty($this->request->get['path'])) {
			$parts = explode('_', $this->request->get['path']);
			$category_id = (int)array_pop($parts);

			$childs = $this->model_catalog_category->getCategories($category_id, true);

			if (isset($this->request->get['all']) || count($childs) < 2) {
				return $this->getProductsList($category_id);
			} else {
				return $this->getChildsList($childs);
			}
		}

		else {
			$this->request->get['path'] = '';
			return $this->getProductsList(0);
		}
	}

	public function getProductsList($category_id) {
		$filter = array(
			'page' => (isset($this->request->get['page']) ? $this->request->get['page'] : 1),
			'limit' => (isset($this->session->data['list_type']) && $this->session->data['list_type']=='list') ? 50
							: $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit'),
			'sort' => array(
				'p.price' => 'ASC'
			)
		);

		$data['products'] = $this->model_catalog_product->getProductsFromCategory($category_id, $filter);
		foreach ($data['products'] as &$product) {
			$image_w = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width');
			$image_h = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height');
			$product['thumb'] = $this->model_catalog_product->getThumb($product, $image_w, $image_h);

			$product['price'] = number_format($product['price'], 0, '.', ' ');
			$product['special'] = null;
			$product['href'] = $this->url->link('product/product',
									'path=' . $this->request->get['path']
									. '&product_id=' . $product['product_id']);
		}

		$pagination = new Pagination();
		$pagination->total = $this->model_catalog_product->getTotalProductsFromCategory($category_id, $filter);
		$pagination->page = $filter['page'];
		$pagination->limit = $filter['limit'];
		$all = isset($this->request->get['all']) ? '&all' : '';
		if ($category_id) {
			$pagination->url = $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&page={page}' . $all);
		} else {
			$pagination->url = '?page={page}';
		}
		$data['pagination'] = $pagination->render();

		if (!isset($this->session->data['list_type'])) $this->session->data['list_type'] = 'list';
		$data['type'] = $this->session->data['list_type'];

		return $this->load->view('product/products_list', $data);
	}

	public function getChildsList($childs) {
		foreach ($childs as &$child) {
			$image_w = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width');
			$image_h = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height');
			$this->load->model('tool/image');
			$child['thumb'] = $this->model_tool_image->resize($child['image'], $image_w, $image_h);

			$child['href'] = $this->url->link('product/category',
									'path=' . $this->request->get['path']
									. '_' . $child['category_id']);
		}
		
		$data = array(
			'categories' => $childs,
			'href_all' => $this->url->link('product/category',
									'path=' . $this->request->get['path']
									. '&all')
		);
		
		return $this->load->view('product/child_categories_list', $data);
	}
}