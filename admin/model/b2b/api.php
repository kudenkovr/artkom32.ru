<?php

class ModelB2bApi extends Model {
	public $login		= 'user32';
	public $password	= 'user32';

	public $exludeCategories = array(34, 39, 53, 869, 1108, 1117, 1145, 1192, 1216, 1355, 2004,
									 2005, 2028, 7469, 7856, 7873, 8008, 8217, 8236, 8267, 8325);

	public $categories = array();
	public $categoriesTree = array();



	public function auth($login=null, $password=null) {
		$login	= empty($login) ? $this->login    : $login;
		$password	= empty($password) ? $this->password : $password;
		$ch = curl_init("https://b2b.i-t-p.pro/api/2");

		$dataAuth = array("request" => array(
							"method" => "login",
							"model" => "auth" ,
							"module" => "quickfox"
							),
					  "data" => array(
							"login" => $login,
							"password" => $password
							)
						);
		$dataAuthString = json_encode($dataAuth);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $dataAuthString);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Length: ' . strlen($dataAuthString)
		));
		$result = curl_exec($ch);
		curl_close ($ch);

		$resAuth = json_decode($result, true);
		
		return $resAuth;
	}

	public function getCategories($session) {
		if (empty($this->categories)) {
			$ch = curl_init("https://b2b.i-t-p.pro/download/catalog/json/catalog_tree.json");
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Cookie: session=' . $session )
			);
			$result = curl_exec($ch);
			curl_close ($ch);
			if (empty($result)) return false;
			$result = json_decode($result, true);
			$this->_getCategoryList($result);
		}
		return $this->categories;
	}

	private function _getCategoryList($categoryList, $parent_id=0) {
		foreach ($categoryList as $category) {
			if ( !in_array($category['id'], $this->exludeCategories) ) {
				$this->categories[$category['id']] = $this->_getCategory($category, $parent_id);
				if (!empty($category['childrens'])) {
					$childrens = $this->_getCategoryList($category['childrens'], $category['id']);
				}
			}
		}
	}

	private function _getCategory($category, $parent_id) {
		return array(
			'category_id'			=> (string)$category['id'],
			'image'					=> '',
			'parent_id'				=> (string)$parent_id,
			'top'					=> (($parent_id>0) ? '0' : '1'),
			'column'				=> '0',
			'sort_order'			=> '0',
			'status'				=> '1',
			'date_added'			=> date("Y-m-d H:i:s"),
			'date_modified'			=> date("Y-m-d H:i:s"),

			'category_description'	=> array(1 => array(
				'category_id'			=> (string)$category['id'],
				'language_id'			=> '1',
				'name'					=> $category['name'],
				'description'			=> '',
				'meta_title'			=> $category['name'],
				'meta_description'		=> '',
				'meta_keyword'			=> ''
			)),
			'category_store'		=> array('0'),
		);
	}

	public function loadStatic($session) {
		$cache_name = 'api.product.static';
		$data = $this->cache->get($cache_name);

		if (empty($data)) {
			$ch = curl_init("https://b2b.i-t-p.pro/download/catalog/json/products.json");
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Cookie: session=' . $session )
			);
			$result = curl_exec($ch);
			curl_close ($ch);
			
			$data = json_decode($result, true);

			// if (!empty($data) && isset($data['success']) && $data['success']) {
				$this->cache->set($cache_name, $data);
			// }
		}

		return $data;
	}

	public function loadPrices($session) {
		$cache_name = 'api.product.prices';
		$data = $this->cache->get($cache_name);

		if (empty($data)) {
			$ch = curl_init("https://b2b.i-t-p.pro/api/2");
			$data = array("request" => array(
								"method" => "get_active_products",
								"model"  => "client_api",
								"module" => "platform"
								),
								"session" => $session
							);

			$dataString = json_encode($data);

			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Length: ' . strlen($dataString)
			));
			$result = curl_exec($ch);
			curl_close ($ch);
			
			$data = json_decode($result, true);
			
			if (!empty($data) && isset($data['success']) && $data['success']) {
				$data = array_column($data['data']['products'], null, 'sku');
				$this->cache->set($cache_name, $data);
			}
		}

		return $data;
	}

	public function updateCategories($categories=array()) {
		$cache_name = 'api.category.parents';
		if (!empty($categories)) {
			// Update categories from API
			$this->cache->delete($cache_name);
		} else {
			$data = $this->cache->get($cache_name);
		}

		if (empty($data)) {
			$sql = 'SELECT c.category_id as id,
							(
								SELECT GROUP_CONCAT(cp.path_id) FROM '.DB_PREFIX.'category_path AS cp
								WHERE cp.category_id=c.category_id
								ORDER BY cp.path_id
							) AS parents
						FROM '.DB_PREFIX.'category AS c
						WHERE c.status=1
						ORDER BY c.category_id';
			$categories = $this->db->query($sql)->rows;
			$data = array_column($categories, 'parents', 'id');

			$this->cache->set($cache_name, $data);
		}

		return $data;
	}

	public function updateManufacturers($static) {
		$manufacturers = array();

		// INSERT IGNORE all vendors
		foreach ($static as $product) {
			$vendor = isset($product['vendor']) ? trim($product['vendor']) : '';
			$lvendor = mb_strtolower($vendor);
			if (!in_array($lvendor, $manufacturers)) {
				if (empty($sql)) {
					$sql = 'INSERT IGNORE INTO '.DB_PREFIX.'manufacturer (name, image, sort_order) VALUES ';
				} else {
					$sql .= ', ';
				}
				$manufacturers[] = $lvendor;
				$sql .= '("'.$this->db->escape($vendor).'", "", 10)';
			}
		}

		$this->db->query($sql);

		// Get manufacturers array
		$sql = 'SELECT manufacturer_id, LCASE(name) AS name FROM '.DB_PREFIX.'manufacturer ORDER by name';
		$manufacturers = array_column($this->db->query($sql)->rows, 'manufacturer_id', 'name');

		// Add all manufacturers to store 0
		$sql = '';
		foreach ($manufacturers as $id) {
			if (empty($sql)) {
				$sql = 'INSERT IGNORE INTO '.DB_PREFIX.'manufacturer_to_store(manufacturer_id, store_id) VALUES';
			} else {
				$sql .= ',';
			}
			$sql .= ' ('.$id.', 0)';
		}
		return $manufacturers;
	}
	
	private function _calcPrice($price) {
		if		($price == 0)	$tax = 0;
		elseif	($price < 20)	$tax = $price * 0.3;
		elseif	($price < 150)	$tax = 50;
		elseif	($price < 500)	$tax = 100;
		elseif	($price < 1500)	$tax = 150;
		elseif	($price < 5000)	$tax = $price * 0.1;
		else $tax = $price * 0.07;
		return ceil( ($price+$tax)/10 )*10;
	}

	public function updateProducts($categories, $manufacturers, $static, $prices) {
		// Get all sku
		$sql = 'SELECT sku, product_id AS id FROM '.DB_PREFIX.'product';
		$inserted_sku = array_column($this->db->query($sql)->rows, 'id', 'sku');

		$counter = array(
			'max'  => 10000,
			'insert'  => 0,
			'update'  => 0
		);
		$total = array(
			'static' => count($static),
			'insert' => 0,
			'update' => 0
		);
		$sql_ins_p = $sql_upd_p = $sql_ins_pd = $sql_ins_p2s = $sql_ins_p2c = '';
		foreach($static as $i => &$product) {
			if ( isset($categories[$product['category']]) ) {
				$sku = (int) $product['sku'];
				$model = isset($product['part']) ? $product['part']: '';
				$vendor = isset($product['vendor']) ? trim($product['vendor']) : '';
				$weight = isset($product['weight']) ? (float)$product['weight']: 0;
				$description = isset($product['name']) ? trim($product['name']) : '';
				if (1) { $name = $description; }
				else {
					$name = !empty($vendor) ? $vendor.' ' : '';
					$name .= $model;
					$name = trim($name);
				}
				$meta_title = $name;
				
				if (!empty($vendor) && isset($manufacturers[mb_strtolower($vendor)])) {
					$manufacturer_id = $manufacturers[mb_strtolower($vendor)];
				} else {
					$manufacturer_id = 0;
				}
				
				if (isset($prices[$sku])) {
					$price = $this->_calcPrice((float)$prices[$sku]['price']);
					$quantity = $price>0 ? strlen($prices[$sku]['qty']) * 10 : 0;
				} else {
					$price = $quantity = 0;
				}
				
				
				// Insert
				if ( !isset($inserted_sku[$sku]) ) {
					if (empty($sql_ins_p)) {
						$sql_ins_p  = 'INSERT IGNORE INTO '.DB_PREFIX.'product'.PHP_EOL
									.'	(model, sku, upc, ean, jan, isbn, mpn,'.PHP_EOL
									.'	location, quantity, stock_status_id,'.PHP_EOL
									.'	manufacturer_id, shipping,'.PHP_EOL
									.'	price, tax_class_id,'.PHP_EOL
									.'	date_available, weight, weight_class_id, length_class_id,'.PHP_EOL
									.'	minimum, sort_order, status, date_added, date_modified)'.PHP_EOL
									.'VALUES'.PHP_EOL;
						
						$sql_ins_pd = 'INSERT IGNORE INTO '.DB_PREFIX.'product_description'.PHP_EOL
									.'	(product_id, language_id,'.PHP_EOL
									.'	name, description, tag,'.PHP_EOL
									.'	meta_title, meta_description, meta_keyword)'.PHP_EOL
									.'VALUES'.PHP_EOL;
						
						$sql_ins_p2s= 'INSERT IGNORE INTO '.DB_PREFIX.'product_to_store'.PHP_EOL
									.'	(product_id, store_id)'.PHP_EOL
									.'VALUES'.PHP_EOL;
						
						$sql_ins_p2c= 'INSERT IGNORE INTO '.DB_PREFIX.'product_to_category'.PHP_EOL
									.'	(product_id, category_id)'.PHP_EOL
									.'VALUES'.PHP_EOL;
									
					} else {
						$sql_ins_p  .= ','.PHP_EOL;
						$sql_ins_pd .= ','.PHP_EOL;
						$sql_ins_p2s.= ','.PHP_EOL;
						$sql_ins_p2c.= ','.PHP_EOL;
					}


					$sql_ins_p .= '	("'.$this->db->escape($model).'", "'.$sku.'", "", "", "", "", "",'.PHP_EOL
									.'		"", '.$quantity.', 7,'.PHP_EOL
									.'		'.(int)$manufacturer_id.', 0,'.PHP_EOL
									.'		'.(float)$price.', 0,'.PHP_EOL
									.'		NOW(), '.(float)$weight.', 1, 1,'.PHP_EOL
									.'		1, 10, 1, NOW(), NOW())';
					
					$sql_get_id = '(SELECT p.product_id FROM '.DB_PREFIX.'product AS p WHERE sku="'.$sku.'" LIMIT 1)';
					
					$sql_ins_pd .= '	('.PHP_EOL
									.'		'.$sql_get_id.', 1,'.PHP_EOL
									.'		"'.$this->db->escape($name).'", "'.$this->db->escape($description).'", "",'.PHP_EOL
									.'		"'.$this->db->escape($meta_title).'", "", "")';
					
					$sql_ins_p2s.= '	('.$sql_get_id.', 0)';
					
					$parents = explode(',', $categories[$product['category']]);
					foreach ($parents as &$parent) {
						$parent = '	('.$sql_get_id.', '.(int)$parent.')';
					}
					$sql_ins_p2c.= implode(','.PHP_EOL, $parents);

					$counter['insert']++;
				}
				// Update
				else {
					$sql_price1 = $price>0 ? ' price = "'.$price.'",' : '';
					$sql_price2 = $price>0 ? 'price != "'.$price.'" OR ' : '';
					$sql_upd_p = 'UPDATE '.DB_PREFIX.'product SET'
									.$sql_price1
									.' quantity = '.$quantity.','
									.' date_modified = NOW()'
								.' WHERE'
									.' sku="'.$sku.'"'
									.' AND status = 1'
									.' AND ('
										.$sql_price2
										.'quantity != '.$quantity
									. ')';

					$this->db->query($sql_upd_p);
					$total['update'] += $this->db->countAffected();
				}
			}

			// Insert new part and Reset counter
			if ($counter['insert'] == $counter['max'] || ($i==($total['static']-1) && !empty($sql_ins_p))) {
				$this->db->query($sql_ins_p);
				$total['insert'] += $this->db->countAffected();
				$this->db->query($sql_ins_pd);
				$this->db->query($sql_ins_p2s);
				$this->db->query($sql_ins_p2c);
				
				$counter['insert'] = 0;
				$sql_ins_p = '';
			}
		}
		return $total;
	}
}













