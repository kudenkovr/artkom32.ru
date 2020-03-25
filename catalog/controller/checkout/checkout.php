<?php
class ControllerCheckoutCheckout extends Controller {
	
	public function index() {
		// Validate cart has products and has stock.
		if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
			$this->response->redirect($this->url->link('checkout/cart'));
		}
		

		// Validate minimum quantity requirements.
		$data['products'] = $this->cart->getProducts();
		$this->load->model('catalog/product');
		$data['totals'] = array(1=>array('text'=>0));
		foreach ($data['products'] as &$product) {
			$product_total = 0;

			foreach ($data['products'] as $product_2) {
				if ($product_2['product_id'] == $product['product_id']) {
					$product_total += $product_2['quantity'];
				}
			}

			if ($product['minimum'] > $product_total) {
				$this->response->redirect($this->url->link('checkout/cart'));
			}
			
			$product['thumb'] = $this->model_catalog_product->getThumb($product, $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));
			
			$data['totals'][1]['text'] += $product['total'];
		}

		$this->load->language('checkout/checkout');

		$this->document->setTitle($this->language->get('heading_title'));
		
		

		$data['breadcrumbs'] = array(
			array(
				'name' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home')
			),
			array(
				'name' => $this->language->get('text_cart'),
				'href' => $this->url->link('checkout/cart')
			),
			array(
				'name' => $this->language->get('heading_title'),
				'href' => null
			)
		);
		$data['breadcrumbs_view'] = $this->load->view('common/breadcrumbs', $data);
		
		
		if (isset($this->session->data['error'])) {
			$data['error_warning'] = $this->session->data['error'];
			unset($this->session->data['error']);
		} else {
			$data['error_warning'] = '';
		}
		
		
		$data['logged'] = $this->customer->isLogged();
		if ($data['logged']) {
			$data['firstname'] = $this->customer->getFirstname();
			$data['lastname'] = $this->customer->getLastname();
			$data['email'] = $this->customer->getEmail();
			$data['telephone'] = $this->customer->getTelephone();
		}

		if (isset($this->session->data['account'])) {
			$data['account'] = $this->session->data['account'];
		} else {
			$data['account'] = '';
		}
		
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('checkout/checkout', $data));
	}
	
	public function add() {
		$this->load->language('checkout/checkout');
		// Validate
		$json = array('error'=>array());
		if ((utf8_strlen(trim($this->request->post['firstname'])) < 1) || (utf8_strlen(trim($this->request->post['firstname'])) > 32)) {
			$json['error']['firstname'] = $this->language->get('error_firstname');
		}

		if ((utf8_strlen(trim($this->request->post['lastname'])) < 1) || (utf8_strlen(trim($this->request->post['lastname'])) > 32)) {
			$json['error']['lastname'] = $this->language->get('error_lastname');
		}

		if ((utf8_strlen($this->request->post['email']) > 96) || !filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
			$json['error']['email'] = $this->language->get('error_email');
		}

		if ((utf8_strlen($this->request->post['telephone']) < 3) || (utf8_strlen($this->request->post['telephone']) > 32)) {
			$json['error']['telephone'] = $this->language->get('error_telephone');
		}
		
		if (!empty($json['error'])) {
			
		} else {
			$data['invoice_prefix'] = 'AC-2020-00';
			$data['store_id'] = 0;
			$data['store_name'] = 'Магазин компьютерной техники "АртКом"';
			$data['store_url'] = '/';
			$data['customer_id'] = 0;
			$data['customer_group_id'] = 0;
			$data['firstname'] = $this->request->post['firstname'];
			$data['lastname'] = $this->request->post['lastname'];
			$data['email'] = $this->request->post['email'];
			$data['telephone'] = $this->request->post['telephone'];
			$data['payment_firstname'] = '';
			$data['payment_lastname'] = '';
			$data['payment_company'] = '';
			$data['payment_address_1'] = '';
			$data['payment_address_2'] = '';
			$data['payment_city'] = '';
			$data['payment_postcode'] = '';
			$data['payment_country'] = '';
			$data['payment_country_id'] = 176;
			$data['payment_zone'] = 'Брянская область';
			$data['payment_zone_id'] = 54;
			$data['payment_address_format'] = '';
			$data['payment_method'] = '';
			$data['payment_code'] = '';
			$data['shipping_firstname'] = '';
			$data['shipping_lastname'] = '';
			$data['shipping_company'] = '';
			$data['shipping_address_1'] = '';
			$data['shipping_address_2'] = '';
			$data['shipping_city'] = '';
			$data['shipping_postcode'] = '';
			$data['shipping_country'] = '';
			$data['shipping_country_id'] = 176;
			$data['shipping_zone'] = '';
			$data['shipping_zone_id'] = 54;
			$data['shipping_address_format'] = '';
			$data['shipping_method'] = '';
			$data['shipping_code'] = '';
			$data['comment'] = '';
			$data['total'] = 0;
			$data['affiliate_id'] = 0;
			$data['commission'] = 0;
			$data['marketing_id'] = 0;
			$data['tracking'] = '';
			$data['language_id'] = 1;
			$data['currency_id'] = 1;
			$data['currency_code'] = 'RUB';
			$data['currency_value'] = 1;
			$data['ip'] = $_SERVER['REMOTE_ADDR'];
			$data['forwarded_ip'] = $_SERVER['REMOTE_ADDR'];
			$data['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
			$data['accept_language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
			
			
			
			// Validate minimum quantity requirements.
			$data['products'] = $this->cart->getProducts();
			foreach ($data['products'] as &$product) {
				$product_total = 0;

				foreach ($data['products'] as $product_2) {
					if ($product_2['product_id'] == $product['product_id']) {
						$product_total += $product_2['quantity'];
					}
				}

				if ($product['minimum'] > $product_total) {
					$json['redirect'] = $this->url->link('checkout/cart');
					$this->response->setOutput(json_encode($json));
					return true;
				}
				
				$product['tax'] = 0;
				$data['total'] += $product['total'];
			}
			
			$this->load->model('checkout/order');
			
			$this->session->data['order_id'] = $this->model_checkout_order->addOrder($data);
			$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 1);
			
			$this->cart->clear();
			
			$json['redirect'] = $this->url->link('checkout/success', '&order_id='.$this->session->data['order_id']);
		}
		
		$this->response->setOutput(json_encode($json));
	}
}