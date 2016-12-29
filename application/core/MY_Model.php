<?php
class MY_Model extends CI_Model {
	function __construct () {
		parent::__construct();
	}
	
}

class Entity_Model extends MY_Model {
	public $main_table;
	public $id_key = 'id';

	public $search_keys = array();

	var $relations;

	function __construct () {
		parent::__construct();
	}

	private function filter($query = array(), $options = array()) {
		if (isset($query) && count($query)) {
			$this->db->where($query);
		}
		
		if (isset($options['in'])) {
			foreach ($options['in'] as $key => $in) {
				if (count($in)) {
					$this->db->where_in($key, $in);
				}
			}
		}

		if (isset($options['query'])) {
			foreach ($this->search_keys as $key) {
				$this->db->or_like($key, $options['query']);
			}
		}

		if (isset($options['like'])) {
			foreach ($options['like'] as $key => $value) {
				$this->db->like($key, $value);
			}
		}
	}
	
	function get_one($query = array(), $options = array()) {
		$options['limit'] = 1;
		$result = $this->get_all($query, $options);
		if (count($result)) {
			return $result[0];
		}
		return NULL;
	}

	function count_all($query = array(), $options = array()) {
		$this->filter($query, $options);

		$count = $this->db->select('COUNT(*) as count')->get($this->main_table)->first_row()->count;
		log_message('debug', $this->db->last_query());
		return $count;
	}

	function get_all($query = array(), $options = array()) {
		if (isset($options['columns'])) {
			$this->db->select(implode(',', $options['columns']));
			$columns = $options['columns'];
		}

		$this->filter($query, $options);
		
		if (isset($options['limit'])) {
			$offset = isset($options['offset']) ? $options['offset'] : 0;
			$this->db->limit($options['limit'], $offset);
		}
		if (isset($options['sort'])) {
			foreach ($options['sort'] as $column => $order) {
				$this->db->order_by($column, $order);
			}
		}
		if (isset($options['group'])) {
			$this->db->group_by($options['group']);
		}

		$db_query = $this->db->get($this->main_table);

		$result = $db_query->result_array();
		$length = $db_query->num_rows();

		// 在主结果集查询结束后再另行查询并拼装join集合
		if ($length) {
			if (isset($options['join'])) {
				// => array('foreignKey' => array('ForeignTable', 'foreignTableKey'))
				foreach ($options['join'] as $table => $on) {
					$key = $on[0];
					// 防止筛选过列后结果集中没有要join的列
					// 没有针对AS处理，会有一定风险
					if (!isset($columns) || in_array($key, $columns)) {
						$ons = array_map(function ($item) use ($key) {
							return isset($item[$key]) ? $item[$key] : NULL;
						}, $result);
						$ons = array_filter($ons, function ($item) {
							return $item !== NULL;
						});
						$ons = array_unique($ons);

						if (count($ons)) {
							$this->db->where_in($on[1], $ons);
		
							$join = $this->db->get($table)->result_array();
							$join_map = array();
							foreach ($join as $index => $item) {
								$join_map[$item[$on[1]]] = $item;
							}

							foreach ($result as $index => $item) {
								if (isset($item[$key])) {
									$foreign = $item[$key];
									if (isset($join_map[$foreign])) {
										$item[preg_replace('/Id$/', '', $key)] = $join_map[$foreign];
										$result[$index] = $item;
									}
								}
							}
						}
	
					}
	
				}
			}

			// join 解决一对一和一对多，fetch 解决多对多
			if (isset($options['fetch'])) {
				$id_key = $this->id_key;
				$ids = array_map(function ($item) use ($id_key) {
					return isset($item[$id_key]) ? $item[$id_key] : NULL;
				}, $result);

				$map = array();
				foreach ($result as $item) {
					$map[$item[$this->id_key]] = $item;
				}

				$result = array();

				$relations = $this->read_relations($ids, $options['fetch']);

				foreach ($this->relations as $table => $key) {
					$data_key = $options['fetch'][$table] == 1 ? preg_replace('/Id$/', '', $key) : $key;
					if (!isset($relations[$data_key])) {
						continue;
					}
					
					// array('timeRange' => array(
					// 	'<categoryId>' => array(
					// 		array(),
					// 		array()
					// 	)
					// ))
					foreach ($relations[$data_key] as $index => $items) {
						$map[$index][$data_key.'[]'] = $items;
					}

					foreach ($map as $index => $item) {
						if (!isset($item[$data_key.'[]'])) {
							$item[$data_key.'[]'] = array();
						}
						array_push($result, $item);
					}
				}
			}
		}

		return $result;
	}

	function get_all_map($query = array(), $options = array()) {
		$result = array();
		$data = $this->get_all($query, $options);

		foreach ($data as $item) {
			$result[$item[$this->id_key]] = $item;
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
			$this->db->where_in($this->main_table . '.'.$this->id_key, $in);
			$result = $this->db->get();
			return $result->result_array();
		} else {
			return array();
		}
	}
	
	function get($id, $query = array(), $options = array()) {
		$query = array_merge($query, array($this->id_key => $id));
		return $this->get_one($query, $options);
	}

	function create($entity, $returnAll = FALSE) {
		if ($this->db->insert($this->main_table, $entity) !== FALSE) {
			return $returnAll ? $this->get_one($entity) : ($this->db->insert_id() || $entity[$this->id_key]);
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
		return $this->db->where($this->id_key, $id)->update($this->main_table, $entity);
	}

	function update_batch($batch) {
		return $this->db->update_batch($this->main_table, $batch, $this->id_key) && TRUE;
	}

	function delete($id, $remove_relation = FALSE) {
		$success = $this->remove(array($this->id_key => $id));
		if ($remove_relation && $this->relations && count($this->relations)) {
			foreach ($this->relations as $table => $foreign_key) {
				$success = $success && $this->unlink($table, array('self' => $id));
			}
		}
		return $success;
	}

	function delete_batch($ids, $remove_relation = FALSE) {
		$success = $this->db->where_in($this->id_key, $ids)->delete($this->main_table);
		if ($remove_relation && $this->relations && count($this->relations)) {
			foreach ($this->relations as $table => $foreign_key) {
				$success = $success && $this->unlink_batch($table, $ids);
			}
		}

		return $success;
	}

	function remove($query) {
		$this->db->delete($this->main_table, $query);
	}

	/**
	 * 过滤关联外键输入数据
	 * $relative TURE返回外键数据，FALSE返回普通数据
	 */
	function filter_input($input, $relative = FALSE) {
		$relations = array();
		if ($this->relations){
			foreach ($this->relations as $table => $key) {
				if (isset($input[$key])) {
					$relations[$key] = $input[$key];
					unset($input[$key]);
				}
			}
		}

		return $relative ? $relations : $input;
	}

	function save($input, $id = NULL) {
		$success = FALSE;

		$data = $this->filter_input($input);

		$is_create = !$id;
		if ($is_create) {
			$id = $this->create($data);
			if ($id) {
				$success = TRUE;
			}
		} else {
			$success = $this->update($id, $data);
		}

		$success = $success && $this->write_relations($id, $input);

		return $is_create ? $id : $success;
	}

	function read_relations($ids, $tables) {
		$result = NULL;

		// [1,2,3]
		$ids = is_array($ids) ? $ids : array($ids);

		// var $relations = array(
		// 	'CategoryTimeRange' => 'timeRangeId'
		// );
		if ($this->relations) {
			$result = array();
			// categoryId
			$id_key = $this->id_key == 'id' ?
				lcfirst($this->main_table).ucfirst($this->id_key) :
				$this->id_key;

			foreach ($this->relations as $table => $key) {
				if (!isset($tables[$table])) {
					continue;
				}

				// timeRangeId
				$table_key = $key;

				// categoryId = [1,2,3]
				$this->db->where_in($id_key, $ids);

				// 'fetch' => array('ForeignTable' => not 1)
				// for 1 as fetch data, 0 for just ids
				if ($tables[$table]) {
					// 'fetch' => array('ForeignTable' => 1)
					// timeRange
					$table_key = preg_replace('/Id$/', '', $key);
					// TimeRange
					$foreign_table = ucfirst($table_key);
					// join TimeRange on TimeRange.id=CategoryTimeRange.timeRangeId
					$this->db->join($foreign_table, $foreign_table.'.id'.'='.$table.'.'.$key);
				}

				$data = $this->db->get($table)->result_array();

				$result[$table_key] = array();

				foreach ($data as $item) {
					$result[$table_key][$item[$id_key]][] = $tables[$table] ? $item : $item[$table_key];
				}
			}
		}

		return $result;
	}

	function write_relations($id, $data, $reserve = FALSE) {
		$success = TRUE;

		if ($this->relations) {
			if (!$reserve) {
				$this->unlink_id($id);
			}
			$relations = $this->filter_input($data, TRUE);
			$links = array();
			foreach ($this->relations as $table => $key) {
				if (isset($relations[$key])) {
					$links = is_array($relations[$key]) ? $relations[$key] : array($relations[$key]);

					$success = $this->link($table, array_map(function ($item) use ($id) {
						return array(
							'foreign' => $item,
							'self' => $id
						);
					}, $links)) && $success;
				}
			}
		}

		return $success;
	}

	function search_all($keyword, $query = array(), $options = array()) {
		$options['like'] = array();
		foreach ($this->search_keys as $key) {
			$options['like'][] = array($key => $keyword);
		}
		return $this->get_all($query, $options);
	}

	function link($table, $links) {
		if ($this->relations && isset($this->relations[$table])) {
			$relations = array();
			foreach ($links as $link) {
				$id_key = $this->id_key == 'id' ?
					lcfirst($this->main_table).ucfirst($this->id_key) :
					$this->id_key;
				$relation = array(
					$id_key => $link['self'],
					$this->relations[$table] => $link['foreign']
				);
				$query = $this->db->get_where($table, $relation);
				if (!$query->num_rows()) {
					array_push($relations, $relation);
				}
			}
			return $this->db->insert_batch($table, $relations);
		}

		return FALSE;
	}

	function unlink($table, $link) {
		if ($this->relations && count($this->relations)) {
			$relation = array();
			if (isset($link['self'])) {
				$relation[lcfirst($this->main_table).ucfirst($this->id_key)] = $link['self'];
			}
			if (isset($link['foreign'])) {
				$relation[$this->relations[$table]] = $link['foreign'];
			}
			return $this->db->delete($table, $relation);
		}

		return 0;
	}

	function unlink_id($id) {
		$success = FALSE;
		if ($this->relations && count($this->relations)) {
			$my_key = $this->id_key == 'id' ?
				lcfirst($this->main_table).ucfirst($this->id_key) :
				$this->id_key;
			foreach ($this->relations as $table => $key) {
				$success = $this->db->where($my_key, $id)->delete($table) && $success;
			}
		}
		return $success;
	}

	function unlink_batch($table, $ids) {
		if ($this->relations && count($this->relations)) {
			return $this->db->where_in(lcfirst($this->main_table).ucfirst($this->id_key), $ids)
				->delete($table);
		}

		return 0;
	}

	function is_linked($table, $link) {
		if ($this->relations && isset($this->relations[$table])) {
			$relation = array();
			if (isset($link['self'])) {
				$relation[lcfirst($this->main_table).ucfirst($this->id_key)] = $link['self'];
			}
			if (isset($link['foreign'])) {
				$relation[$this->relations[$table]] = $link['foreign'];
			}
			$query = $this->db->get_where($table, $relation);
			return $query->num_rows() > 0;
		}

		return FALSE;
	}

	function pagination(&$list, $options = array()) {
		$per_page = isset($options['perPage']) ? $options['perPage'] : 10;
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