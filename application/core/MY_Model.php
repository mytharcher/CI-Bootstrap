<?php
class MY_Model extends CI_Model {
	function __construct () {
		parent::__construct();
	}
	
}

class Entity_Model extends MY_Model {
	var $main_table;
	var $relation_table;
	var $foreign_key;
	var $search_key = 'name';

	function __construct () {
		parent::__construct();
	}
	
	function get_one($query = array()) {
		$result = $this->get_all($query, array('limit' => 1));
		if (count($result)) {
			return $result[0];
		}
		return NULL;
	}

	function get_all($query = array(), $options = array()) {
		$this->db->where($query);
		if (isset($options['like'])) {
			foreach ($options['like'] as $key => $value) {
				$this->db->like($key, $value);
			}
		}
		if (isset($options['limit'])) {
			$offset = isset($options['offset']) ? $options['offset'] : 0;
			$this->db->limit($options['limit'], $offset);
		}
		if (isset($options['sort'])) {
			foreach ($options['sort'] as $column => $order) {
				$this->db->order_by($column, $order);
			}
		}
		$result = $this->db->get($this->main_table)->result_array();

		// 在主结果集查询结束后再另行查询并拼装join集合
		if (count($result) && isset($options['join'])) {
			// => array('ForeignTable', array('foreignKey', 'foreignTableKey'))
			foreach ($options['join'] as $table => $on) {
				$this->db->where_in($on[1], array_unique(array_map(function ($item) use ($on) {
					return $item[$on[0]];
				}, $result)));

				$join = $this->db->get($table)->result_array();
				$join_map = array();
				foreach ($join as $index => $item) {
					$join_map[$item[$on[1]]] = $item;
				}

				foreach ($result as $index => $item) {
					$foreign = $item[$on[0]];
					unset($item[$on[0]]);
					$item[lcfirst($table)] = $join_map[$foreign];
					$result[$index] = $item;
				}
			}
		}
		return $result;
	}

	function get_list($columns = '*', $query = array(), $options = array()) {
		$this->db->select($columns)
			->from($this->main_table)
			->where($query);

		if (isset($options['limit'])) {
			$offset = isset($options['offset']) ? $options['offset'] : 0;
			$this->db->limit($options['limit'], $offset);
		}
		$result = $this->db->get();
		return $result->result_array();
	}

	// $in是一个array($value1, $value2)的值数组
	function get_list_in($columns = '*', $in, $query = array(), $options = array()) {
		$this->db->select($columns)
			->from($this->main_table);
		if ($query && count($query)) {
			$this->db->where($query);
		}
		if (count($in)) {
			$this->db->where_in($this->main_table . '.id', $in);
			$result = $this->db->get();
			return $result->result_array();
		} else {
			return array();
		}
	}
	
	function get($id) {
		return $this->get_one(array('id' => $id));
	}

	function create($entity) {
		if ($this->db->insert($this->main_table, $entity) !== FALSE) {
			return $this->db->insert_id();
		}
		return FALSE;
	}

	function create_batch($batch) {
		if ($this->db->insert_batch($this->main_table, $batch) !== FALSE) {
			return $this->db->insert_id();
		}
		return FALSE;
	}

	function update($id, $entity) {
		return $this->db->where('id', $id)->update($this->main_table, $entity);
	}

	function update_batch($batch) {
		return $this->db->update_batch($this->main_table, $batch, 'id') && TRUE;
	}

	function delete($id) {
		$success = $this->db->delete($this->main_table, array('id' => $id));
		if ($this->relation_table) {
			$success = $success && $this->unlink(array('self' => $id));
		}
		return $success;
	}

	function get_relation($query, $full = FALSE) {
		if ($this->relation_table) {
			// use `index` for controller only load relation indexes
			$this->db->select('*, '.$this->main_table.'Id AS `index`')
				->from($this->relation_table);
			if ($full) {
				$this->db->join($this->main_table, $this->main_table.'Id = '.$this->main_table.'.id', 'left outer');
			}
			$result = $this->db->where($query)->get();
		} else {
			$result = $this->db->get_where($this->main_table, $query);
		}
		return $result->result_array();
	}

	// 根据foreign_id的值在关系表中查出记录
	function foreign($id, $full = FALSE) {
		return $this->get_relation(array($this->foreign_key => $id), $full);
	}

	// 根据主表id的值在
	function relative($id, $full = FALSE) {
		return $this->get_relation(array($this->main_table.'Id' => $id), $full);
	}

	// 根据给定的$ids数组中的id值从关系表中反查出foreign_id的值组
	function reverse_foreign($ids, $options = array()) {
		if ($this->relation_table) {
			$this->db->select($this->foreign_key)
				->distinct()
				->from($this->relation_table)
				->where_in($this->main_table.'Id', $ids);
			if (isset($options['limit'])) {
				$offset = isset($options['offset']) ? $options['offset'] : 0;
				$this->db->limit($options['limit'], $offset);
			}
			$result = $this->db->get();
			return $result->result_array();
		}
	}

	function search_all($keyword, $options = array()) {
		$options['like'] = array($this->search_key => $keyword);
		$result = $this->get_all(array(), $options);
		return $result;
	}

	function search_to_foreign($keyword, $options = array()) {
		$this->db->select($this->foreign_key)
			->from($this->relation_table)
			->join($this->main_table, $this->main_table.'Id = '.$this->main_table.'.id', 'inner')
			->like($this->main_table . '.' . $this->search_key, $keyword);

		if (isset($options['limit'])) {
			$offset = isset($options['offset']) ? $options['offset'] : 0;
			$this->db->limit($options['limit'], $offset);
		}
		if (isset($options['sort'])) {
			foreach ($options['sort'] as $column => $order) {
				$this->db->order_by($column, $order);
			}
		}
		$result = $this->db->get();
		return $result->result_array();
	}

	function link($link) {
		if ($this->relation_table) {
			$relation = array(
				$this->main_table.'Id' => $link['self'],
				$this->foreign_key => $link['foreign']
			);
			$query = $this->db->get_where($this->relation_table, $relation);
			if (!$query->num_rows()) {
				return $this->db->insert($this->relation_table, $relation);
			} else {
				return FALSE;
			}
		}

		return FALSE;
	}

	function unlink($link) {
		if ($this->relation_table) {
			$relation = array();
			if (isset($link['self'])) {
				$relation[$this->main_table.'Id'] = $link['self'];
			}
			if (isset($link['foreign'])) {
				$relation[$this->foreign_key] = $link['foreign'];
			}
			return $this->db->delete($this->relation_table, $relation);
		}

		return 0;
	}

	function is_linked($link) {
		if ($this->relation_table) {
			$relation = array();
			if (isset($link['self'])) {
				$relation[$this->main_table.'Id'] = $link['self'];
			}
			if (isset($link['foreign'])) {
				$relation[$this->foreign_key] = $link['foreign'];
			}
			$query = $this->db->get_where($this->relation_table, $relation);
			return $query->num_rows() > 0;
		}

		return FALSE;
	}

	function pagination(&$list, $options = array()) {
		$per_page = isset($options['per_page']) ? $options['per_page'] : 10;
		$offset = (isset($options['page']) ? $options['page'] : 0) * $per_page;
		$limit = isset($options['limit']) ? $options['limit'] : $per_page;
		$count = count($list);
		$total = ceil($count / $per_page);
		return array(
			'list' => array_slice($list, $offset, $limit),
			'total' => $total,
			'count' => $count
		);
	}
}