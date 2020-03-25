<?php
class ModelCatalogCategory extends Model {
	public function getCategory($category_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.category_id = '" . (int)$category_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1'");

		return $query->row;
	}

	public function getCategories($parent_id = 0, $get_total=false) {
		$sql_get_total = $get_total ? ',
						(SELECT COUNT(p2c.product_id) FROM '.DB_PREFIX.'product_to_category AS p2c
						 LEFT JOIN '.DB_PREFIX.'product AS p USING(product_id)
						 WHERE p2c.category_id = c.category_id AND p.quantity>0 AND p.status=1) AS total' : '';
		$sql = 'SELECT c.category_id AS category_id, cd.name as name, c.image AS image'.$sql_get_total.'
					FROM '.DB_PREFIX.'category AS c
					LEFT JOIN '.DB_PREFIX.'category_description AS cd USING(category_id)
					WHERE c.parent_id='.(int)$parent_id.'
						AND c.status=1
					ORDER BY c.sort_order ASC, cd.name ASC';
		$data = $this->db->query($sql)->rows;
		
		return $data;
	}
	
	public function getCategoriesTree($parent_id=0, $depth=0) {
		$cache_name = 'category.tree'
						. '.' . (int)$parent_id
						. '.' . (int)$depth;
		$data = $this->cache->get($cache_name);
		
		if (empty($data)) {
			$data = $this->_getCategoriesTree($parent_id, $depth);
			$this->cache->set($cache_name, $data);
		}
		
		return $data;
	}
	
	private function _getCategoriesTree($parent_id=0, $depth=0, $path='') {
		$data = $this->getCategories($parent_id, true);
		foreach ($data as &$category) {
			$category_path = empty($path) ? $category['category_id'] : $path.'_'.$category['category_id'];
			$category['href'] = $this->url->link(
				'product/category',
				'path=' . $category_path
			);
			
			if ($depth>0) {
				$category['children'] = $this->_getCategoriesTree($category['category_id'], $depth-1, $category_path);
			}
		}
		return $data;
	}
	
	public function getCategoriesById(array $ids) {
		foreach($ids as &$id) { $id = (int)$id; }
		$sql = 'SELECT c.category_id AS category_id, cd.name AS name
					FROM '.DB_PREFIX.'category AS c
					LEFT JOIN '.DB_PREFIX.'category_description AS cd USING(category_id)
					WHERE c.category_id IN(' . implode(', ', $ids) . ')
					ORDER BY FIELD(c.category_id, ' . implode(', ', $ids) . ')';
		return $this->db->query($sql)->rows;
	}

	public function getCategoryFilters($category_id) {
		$implode = array();

		$query = $this->db->query("SELECT filter_id FROM " . DB_PREFIX . "category_filter WHERE category_id = '" . (int)$category_id . "'");

		foreach ($query->rows as $result) {
			$implode[] = (int)$result['filter_id'];
		}

		$filter_group_data = array();

		if ($implode) {
			$filter_group_query = $this->db->query("SELECT DISTINCT f.filter_group_id, fgd.name, fg.sort_order FROM " . DB_PREFIX . "filter f LEFT JOIN " . DB_PREFIX . "filter_group fg ON (f.filter_group_id = fg.filter_group_id) LEFT JOIN " . DB_PREFIX . "filter_group_description fgd ON (fg.filter_group_id = fgd.filter_group_id) WHERE f.filter_id IN (" . implode(',', $implode) . ") AND fgd.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY f.filter_group_id ORDER BY fg.sort_order, LCASE(fgd.name)");

			foreach ($filter_group_query->rows as $filter_group) {
				$filter_data = array();

				$filter_query = $this->db->query("SELECT DISTINCT f.filter_id, fd.name FROM " . DB_PREFIX . "filter f LEFT JOIN " . DB_PREFIX . "filter_description fd ON (f.filter_id = fd.filter_id) WHERE f.filter_id IN (" . implode(',', $implode) . ") AND f.filter_group_id = '" . (int)$filter_group['filter_group_id'] . "' AND fd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY f.sort_order, LCASE(fd.name)");

				foreach ($filter_query->rows as $filter) {
					$filter_data[] = array(
						'filter_id' => $filter['filter_id'],
						'name'      => $filter['name']
					);
				}

				if ($filter_data) {
					$filter_group_data[] = array(
						'filter_group_id' => $filter_group['filter_group_id'],
						'name'            => $filter_group['name'],
						'filter'          => $filter_data
					);
				}
			}
		}

		return $filter_group_data;
	}

	public function getCategoryLayoutId($category_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_to_layout WHERE category_id = '" . (int)$category_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return (int)$query->row['layout_id'];
		} else {
			return 0;
		}
	}

	public function getTotalCategoriesByCategoryId($parent_id = 0) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.parent_id = '" . (int)$parent_id . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1'");

		return $query->row['total'];
	}
}