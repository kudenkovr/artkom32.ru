<?php
class ControllerProductSearch extends Controller {
	public function index() {
		$this->load->language('product/search');
		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$data = array();
		
		$keys_get = array(
			'search' => '',
			'category_id' => 0,
			'sort' => 'p.price',
			'order' => 'ASC',
			'page' => 1,
			'limit' => $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit')
		);
		
// Get search params
		$filter_data = array();
		foreach ($keys_get as $key => $default) {
			if (isset($this->request->get[$key])) $filter_data[$key] = $this->request->get[$key];
			$$key = isset($this->request->get[$key]) ? $this->request->get[$key] : $default;
		}
		
// Set heading title
		$data['heading_title'] = $this->language->get('heading_title');
		if (!empty($filter_data['search'])) {
			$data['heading_title'] = $this->language->get('heading_title') .  ' - ' . $search;
		}
		$this->document->setTitle($data['heading_title']);
		
// Breadcrumbs
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array( 'name' => 'Главная', 'href' => '/' );
		$data['breadcrumbs'][] = array('name' => $data['heading_title']);
		
		if (!empty($filter_data['search'])) {
// Get products
			$filter_data['start'] = ($page-1)*$limit;
			$filter_data['limit'] = $limit;
			
			$product_total = $this->model_catalog_product->searchProductsTotal($filter_data);
			$data['products'] = $this->model_catalog_product->searchProducts($filter_data);
			
			$search_url = '';
			foreach ($keys_get as $key => $default) {
				if (!empty($$key)) $search_url .= '&' . $key .'='. $$key;
			}
			
			foreach ($data['products'] as &$product) {
				if (empty($product['image'])) $product['image'] = 'placeholder.png';
				$product['thumb'] = $this->model_tool_image->resize($product['image'],
								$this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'),
								$this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height')
							);
				$product['price'] = number_format($product['price'], 0, '.', ' ');
				$product['href'] = '?route=product/product' . $search_url . '&product_id=' . $product['product_id'];
				$product['description'] = trim(strip_tags(html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8')));
			}
			
			$pagination = new Pagination();
			$pagination->total = $product_total;
			$pagination->page = $page;
			$pagination->limit = $limit;
			$page = '{page}';
			$pagination_url = '';
			foreach ($keys_get as $key => $default) {
				if (!empty($$key)) $pagination_url .= '&' . $key .'='. $$key;
			}
			$pagination->url = $this->url->link('product/search', $pagination_url);
			$data['pagination'] = $pagination->render();
		}
		
		$data['search'] = $search;
		$data['category_id'] = $category_id;

		$data['sort'] = $sort;
		$data['order'] = $order;
		$data['limit'] = $limit;

		$data['footer'] = $this->load->controller('common/footer');
		$data['menu'] = $this->load->controller('common/menu');
		$data['header'] = $this->load->controller('common/header');
		
		$data['view_breadcrumbs'] = $this->load->view('common/breadcrumbs', $data);
		
		if (empty($data['products'])) $data['text_error'] = 'По запросу "'.$filter_data['search'].'" ничего не найдено.';
		$data['products_list'] = $this->load->view('product/products_list', $data);

		$this->response->setOutput($this->load->view('product/search', $data));
	}
}
