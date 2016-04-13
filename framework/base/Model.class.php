<?php

namespace wuyuan\base;

use wuyuan\db\Connection;

/**
 * wuyuan 模型父类.
 * 主从模式时, 执行到事务类型的操作时, 必须先开启事务, 才能保证是用的同一个主数据库连接, 除非只有一个主服务器.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class Model {
	
	/**
	 * 数据库连接管理类对象.
	 * 
	 * @var \wuyuan\db\Connection
	 */
	protected $conn = NULL;
	
	/**
	 * 表前缀.
	 * 
	 * @var string
	 */
	private $_tablePrefix = '';

	/**
	 * 根据模型名计算出的表名.
	 * 
	 * @var string
	 */
	private $_tableName = '';
	
	/**
	 * 当前的模型类名.
	 * 
	 * @var string
	 */
	private $_className = '';
	
	/**
	 * 完整的表名.
	 * 
	 * @var string
	 */
	protected $fullTableName = '';
	
	/**
	 * 表名分隔符.
	 * 
	 * @var string
	 */
	protected $tableSplit = '_';
	
	/**
	 * insert 或 update 时过滤的字段列表.
	 * 
	 * @var array
	 */
	protected $autoFilterField = [];
	
	/**
	 * 是否执行字段过滤.
	 * 
	 * @var boolean
	 */
	private $_isExecFilterField = TRUE;
	
	/**
	 * 主数据库连接.
	 * 用于主从模式时, 记录开启事务的主数据库连接.
	 * 
	 * @var \wuyuan\db\Db
	 */
	private $_transLinkId = NULL;
	
	/**
	 * 使用数据库连接的其它配置项标记.
	 * 
	 * @var boolean 
	 */
	protected $useDbOtherConfig = FALSE;
	
	/**
	 * SQL 语句选项.
	 * 
	 * @var array
	 */
	private $_opts = [
		'alias' => '',			// 当前模型表别名.
		'distinct' => FALSE,	// 值为 boolean.
		'field' => [],			// 值为 string|array.
		'table' => '',			// 值为 string|array.
		'join' => '',			// 值为 string|array.
		'data' => [],			// 值为 array.
		'bindData' => [],		// 值为 array, 如 ':name' => 'xiaowang', 绑定参数值.
		'where' => '',			// 值为 string.
		'group' => '',			// 值为 string|array.
		'having' => '',			// 值为 string.
		'order' => '',			// 值为 string|array.
		'limit' => '',			// 值为 string|integer|array.
		'union' => [],			// 值为 array, ['SQL', TRUE|FALSE], 第 2 个参数为 TRUE 用于标识  UNION ALL.
		'comment' => ''			// 值为 string.
	];
	
	/**
	 * 获取本类的单例对象.
	 * 
	 * @access public
	 * @return \wuyuan\Model
	 */
	public static function getInstance() {
		static $ins = NULL;
		
		if(NULL === $ins) {
			$ins = new static();
		}
		
		return $ins;
	}
	
	/**
	 * 指定模型的连接对象.
	 * 通过重写该方法让模型使用指定的数据库连接 Db 对象.
	 * 数据库连接优先级: $this->_transLinkId > seecialDbLink > $this->useDbOtherConfig(TRUE) > 默认连接.
	 * 
	 * @access protected
	 * @return \wuyuan\db\Db|NULL
	 */
	protected function specialDbLink() {
		return NULL;
	}
	
	/**
	 * 获取数据库连接对象.
	 * 数据库连接优先级: $this->_transLinkId > seecialDbLink > $this->useDbOtherConfig(TRUE) > 默认连接.
	 * 
	 * @access protected
	 * @param string $name 配置名称, 默认 NULL 表示随机(主从模式时始终是随机的), 每一组配置可以给一个命名键名.
	 * @param integer $type 连接配置类型, 默认 \wuyuan\db\Connection::DB_TYPE_MASTER, DB_TYPE_SLAVE, DB_TYPE_OTHER.
	 * @return \wuyuan\db\Db 返回数据库驱动对象, 出错时抛异常.
	 */
	final protected function getDbLink($name = NULL, $type = Connection::DB_TYPE_MASTER) {
		// 开启事务的连接.
		if($this->_transLinkId instanceof \wuyuan\db\Db) {
			return $this->_transLinkId;
		}
		
		// 强制指定的连接.
		$link = $this->specialDbLink();
		if($link instanceof \wuyuan\db\Db) {
			return $link;
		}
		
		// 使用其它配置连接.
		if($this->useDbOtherConfig) {
			return $this->conn->getDbLink($name, Connection::DB_TYPE_OTHER);
		}
		
		return $this->conn->getDbLink($name, $type);
	}

	/**
	 * 设置 insert 或 update 时的过滤字段.
	 * 
	 * @access public
	 * @param array $fields 过滤的字段列表.
	 * @return \wuyuan\base\Model
	 */
	final public function setFilterField(array $fields) {
		$this->autoFilterField = $fields;
		return $this;
	}
	
	/**
	 * 设置是否自动进行字段过滤.
	 * 
	 * @access public
	 * @param boolean $isExecFilterField
	 * @return \wuyuan\base\Model
	 */
	final public function setExecFilterField($isExecFilterField) {
		$this->_isExecFilterField = (boolean)$isExecFilterField;
		return $this;
	}

	/**
	 * 自动过滤字段.
	 * 
	 * @access private
	 * @param array $data 待过滤的数据.
	 * @return array
	 */
	private function _autoFilterField(array $data) {
		if(empty($this->autoFilterField)) {
			return $data;
		}
		
		foreach($data as $k => $v) {
			if(is_array($v)) {
				$data[$k] = $this->_autoFilterField($v);
			} elseif(in_array($k, $this->autoFilterField, TRUE)) {
				unset($data[$k]);
			}
		}
		
		return $data;
	}
	
	/**
	 * 执行有数据返回的查询.
	 * 
	 * @access public
	 * @param string $strSql SQL 语句.
	 * @return array 成功返回 array, 出错时抛异常.
	 */
	final public function query($strSql) {
		/* @var \wuyuan\base\IDbDriver $link */
		$link = $this->getDbLink(NULL, Connection::DB_TYPE_SLAVE);
		$bindParams = [];
		if(!empty($this->_opts['bindData'])) {
			$bindParams = $link->parseBindData($this->_opts['bindData']);
			$this->resetOpts();
		}
		if(!empty($bindParams)) {
			$strSql = str_replace(array_keys($bindParams), array_values($bindParams), $strSql);
		}
		
		$strSql = $this->resolveTableName($strSql);
		if($link->query($strSql)) {
			$result = $link->fetchAll();
			$link->freeResult();
			return $result;
		}
	}
	
	/**
	 * 执行无数据返回的查询.
	 *
	 * @access public
	 * @param string $strSql SQL 语句.
	 * @return boolean 成功返回 TRUE, 出错抛异常.
	 */
	final public function execute($strSql) {
		/* @var \wuyuan\base\IDbDriver $link */
		$link = $this->getDbLink(NULL, Connection::DB_TYPE_MASTER);
		$bindParams = [];
		if(!empty($this->_opts['bindData'])) {
			$bindParams = $link->parseBindData($this->_opts['bindData']);
			$this->resetOpts();
		}
		if(!empty($bindParams)) {
			$strSql = str_replace(array_keys($bindParams), array_values($bindParams), $strSql);
		}
		
		$strSql = $this->resolveTableName($strSql);
		return $link->execute($strSql);
	}
	
	/**
	 * 开启事务.
	 * 
	 * @access public
	 * @return boolean 成功返回 TRUE, 出错时抛异常.
	 */
	final public function startTransaction() {
		/* @var \wuyuan\base\IDbDriver $link */
		$this->_transLinkId = $link = $this->getDbLink(NULL, Connection::DB_TYPE_MASTER);
		return $link->startTransaction();
	}
	
	/**
	 * 回滚事务.
	 * 
	 * @access public
	 * @return boolean 成功返回 TRUE, 出错时抛异常.
	 */
	final public function rollback() {
		/* @var \wuyuan\base\IDbDriver $link */
		$link = $this->getDbLink(NULL, Connection::DB_TYPE_MASTER);
		$res = $link->rollback();
		$this->_transLinkId = NULL;
		return $res;
	}
	
	/**
	 * 提交事务.
	 *
	 * @access public
	 * @return boolean 成功返回 TRUE, 出错时抛异常.
	 */
	final public function commit() {
		/* @var \wuyuan\base\IDbDriver $link */
		$link = $this->getDbLink(NULL, Connection::DB_TYPE_MASTER);
		$res = $link->commit();
		$this->_transLinkId = NULL;
		return $res;
	}
	
	/**
	 * 查询一条.
	 * 
	 * @access public
	 * @return array 成功返回 array, 出错抛异常.
	 */
	final public function find() {
		if(empty($this->_opts['table'])) {
			$this->_opts['table'] = $this->tableName();
			if(!empty($this->_opts['alias'])) {
				$this->_opts['table'] = $this->_opts['table'] . ' AS ' . $this->_opts['alias'];
			}
		}
		
		$this->_opts['limit'] = 1;
		$link = $this->getDbLink(NULL, Connection::DB_TYPE_SLAVE);
		$result = $link->autoSelect($this->_opts, FALSE);
		$this->resetOpts();
		return isset($result[0]) ? $result[0] : [];
	}
	
	/**
	 * 查询多条.
	 * 
	 * @access public
	 * @param string $returnSql 返回 SQL 语句, 默认 FALSE.
	 * @return array 成功返回 array, 出错抛异常.
	 */
	final public function select($returnSql = FALSE) {
		if(empty($this->_opts['table'])) {
			$this->_opts['table'] = $this->tableName();
			if(!empty($this->_opts['alias'])) {
				$this->_opts['table'] = $this->_opts['table'] . ' AS ' . $this->_opts['alias'];
			}
		}
		
		$opts = $this->_opts;
		$this->resetOpts();
		$link = $this->getDbLink(NULL, Connection::DB_TYPE_SLAVE);
		return $link->autoSelect($opts, $returnSql);
	}
	
	/**
	 * 插入记录.
	 * 
	 * @access public
	 * @return boolean 成功返回 TRUE, 出错抛异常.
	 */
	final public function insert() {
		if(empty($this->_opts['table'])) {
			$this->_opts['table'] = $this->tableName();
			if(!empty($this->_opts['alias'])) {
				$this->_opts['table'] = $this->_opts['table'] . ' AS ' . $this->_opts['alias'];
			}
		}
		
		$opts = $this->_opts;
		$this->resetOpts();
		$link = $this->getDbLink(NULL, Connection::DB_TYPE_MASTER);
		return $link->autoInsert($opts);
	}
	
	/**
	 * 更新记录.
	 * 
	 * @access public
	 * @return boolean 成功返回 TRUE, 出错时抛异常.
	 */
	final public function update() {
		if(empty($this->_opts['table'])) {
			$this->_opts['table'] = $this->tableName();
			if(!empty($this->_opts['alias'])) {
				$this->_opts['table'] = $this->_opts['table'] . ' AS ' . $this->_opts['alias'];
			}
		}
		
		$opts = $this->_opts;
		$this->resetOpts();
		$link = $this->getDbLink(NULL, Connection::DB_TYPE_MASTER);
		return $link->autoUpdate($opts);
	}
	
	/**
	 * 删除记录.
	 * 
	 * @access public
	 * @return boolean 成功返回 TRUE, 出错时抛异常.
	 */
	final public function delete() {
		if(empty($this->_opts['table'])) {
			$this->_opts['table'] = $this->tableName();
			if(!empty($this->_opts['alias'])) {
				$this->_opts['table'] = $this->_opts['table'] . ' AS ' . $this->_opts['alias'];
			}
		}
		
		$opts = $this->_opts;
		$this->resetOpts();
		$link = $this->getDbLink(NULL, Connection::DB_TYPE_MASTER);
		return $link->autoDelete($opts);
	}
	
	/**
	 * 统计总记录数.
	 * 
	 * @access public
	 * @return integer 成功返回 integer, 出错时抛异常.
	 */
	final public function count() {
		if(empty($this->_opts['table'])) {
			$this->_opts['table'] = $this->tableName();
			if(!empty($this->_opts['alias'])) {
				$this->_opts['table'] = $this->_opts['table'] . ' AS ' . $this->_opts['alias'];
			}
		}
		
		$this->_opts['field'] = 'COUNT(*) AS _wy_tmp_count_';
		$link = $this->getDbLink(NULL, Connection::DB_TYPE_SLAVE);
		$result = $link->autoSelect($this->_opts);
		$this->resetOpts();
		if(empty($result)) {
			return 0;
		}
		
		return (integer)$result[0]['_wy_tmp_count_'];
	}
	
	/**
	 * 最近一次操作的 SQL 语句.
	 * 
	 * @access public
	 * @return string
	 */
	final public function lastSql() {
		return $this->conn->lastDb()->lastSql();
	}
	
	/**
	 * 最近一次 INSERT 操作自增长 ID.
	 * 
	 * @access public
	 * @return integer
	 */
	final public function lastInsertId() {
		return $this->conn->lastDb()->lastInsertId();
	}
	
	/**
	 * 最近一次操作受影响的行数.
	 * 
	 * @access public
	 * @return integer
	 */
	final public function affectedRows() {
		return $this->conn->lastDb()->affectedRows();
	}
	
	/**
	 * 重置选项.
	 * 
	 * @access private
	 * @return void
	 */
	private function resetOpts() {
		$this->_opts = [
			'alias' => '',			// 当前模型表别名.
			'distinct' => FALSE,	// 值为 boolean.
			'field' => [],			// 值为 string|array.
			'table' => '',			// 值为 string|array.
			'join' => '',			// 值为 string|array.
			'data' => [],			// 值为 array.
			'bindData' => [],		// 值为 array, 如 ':name' => 'xiaowang', 绑定参数值.
			'where' => '',			// 值为 string.
			'group' => '',			// 值为 string|array.
			'having' => '',			// 值为 string.
			'order' => '',			// 值为 string|array.
			'limit' => '',			// 值为 string|integer|array.
			'union' => [],			// 值为 array, ['SQL', TRUE|FALSE], 第 2 个参数为 TRUE 用于标识  UNION ALL.
			'comment' => ''			// 值为 string.
		];
	}
	
	/**
	 * 处理表名.
	 * 即将 {__表名__} 的字符串替换成带前缀的表名
	 * 
	 * @access protected
	 * @param string $str 待处理的字符串.
	 * @return string
	 */
	protected function resolveTableName($str) {
		$pattern = '/\{__(.+?)__\}/';
		if(preg_match($pattern, $str)) {
			return preg_replace($pattern, $this->_tablePrefix . '$1', $str);
		}
		
		return $str;
	}
	
	/**
	 * 获取完整的表名.
	 *
	 * @access public
	 * @return string
	 */
	protected function tableName() {
		return empty($this->fullTableName) ? $this->_tablePrefix . $this->_tableName : $this->fullTableName;
	}
	
	/**
	 * 获取表前缀.
	 * 
	 * @access public
	 * @return string
	 */
	final public function tablePrefix() {
		return $this->_tablePrefix;
	}
	
	/**
	 * 当前模型表的别名.
	 * 
	 * @access public
	 * @param string $param 别名.
	 * @return \wuyuan\base\Model
	 */
	final public function alias($param) {
		$this->_opts['alias'] = $param;
		return $this;
	}
	
	/**
	 * 绑定参数.
	 * 
	 * @access public
	 * @param string|array $name 为数组批量绑定, 格式为: [':名称' => 值]; 为字符串, 可以调用多次;
	 * @param mixed $value 默认 NULL.
	 * @return \wuyuan\base\Model
	 */
	final public function bindParam($name, $value = NULL) {
		if(is_string($name)) {
			$this->_opts['bindData'][$name] = $value;
		} else {
			$this->_opts['bindData'] = $name;			
		}
		
		return $this;
	}
	
	/**
	 * FIELD.
	 * 
	 * @access public
	 * @param string|array $param 字段列表, 多个字段 string 时用逗号分隔.
	 * @return \wuyuan\base\Model
	 */
	final public function field($param) {
		$this->_opts['field'] = $param;
		return $this;
	}
	
	/**
	 * TABLE.
	 * 表名可以使用 {__表名__}, 会自动加上表前缀.
	 *
	 * @access public
	 * @param string|array $param 完整表名, array 表示多个表名.
	 * @return \wuyuan\base\Model
	 */
	final public function table($param) {
		if (is_array($param)) {
			foreach($param as & $v) {
				$v = $this->resolveTableName($v);
			}

			unset($v);
			$this->_opts['table'] = $param;
		} else {
			$this->_opts['table'] = $this->resolveTableName($param);
		}

		return $this;
	}
	
	/**
	 * WHERE.
	 * 
	 * @access public
	 * @param string $param 条件字符串.
	 * @return \wuyuan\base\Model
	 */
	final public function where($param) {
		$this->_opts['where'] = $param;
		return $this;
	}
	
	/**
	 * GROUP.
	 * 
	 * @access public
	 * @param string|array $param 分组字符串, array 表示多个.
	 * @return \wuyuan\base\Model
	 */
	final public function group($param) {
		$this->_opts['group'] = $param;
		return $this;
	}
	
	/**
	 * HAVING.
	 * 
	 * @access public
	 * @param string $param 条件字符串.
	 * @return \wuyuan\base\Model
	 */
	final public function having($param) {
		$this->_opts['having'] = $param;
		return $this;
	}
	
	/**
	 * ORDER.
	 * 
	 * @access pubilc
	 * @param string|array $param 排序字符串, array 表示多个.
	 * @return \wuyuan\base\Model
	 */
	final public function order($param) {
		$this->_opts['order'] = $param;
		return $this;
	}
	
	/**
	 * LIMIT.
	 * 
	 * @access public
	 * @param string|integer|array $param array 时, 最多接收 2 个元素.
	 * @return \wuyuan\base\Model
	 */
	final public function limit($param) {
		$this->_opts['limit'] = $param;
		return $this;
	}
	
	/**
	 * 分页参数.
	 * 使用此方法自动计算分页的 LIMIT.
	 * 
	 * @access public
	 * @param integer $page 当前页码, 默认 1.
	 * @param integer $rows 每页显示的条数, 默认 20.
	 * @return \wuyuan\base\Model
	 */
	final public function page($page = 1, $rows = 20) {
		$this->_opts['limit'] = ($page - 1) * $rows . ',' . $rows;
		return $this;
	}


	/**
	 * JOIN.
	 * 表名可以使用 {__表名__}, 会自动加上表前缀.
	 * 
	 * @access public
	 * @param string|array $param JOIN 查询字符串, array 表示多个.
	 * @return \wuyuan\base\Model
	 */
	final public function join($param) {
		if(is_array($param)) {
			foreach($param as & $v) {
				$v = $this->resolveTableName($v);
			}
			
			unset($v);
			$this->_opts['join'] = $param;
		} else {
			$this->_opts['join'][] = $this->resolveTableName($param);
		}
		
		return $this;
	}
	
	/**
	 * UNION.
	 * 
	 * @access public
	 * @param string|array $param 如果是数组, 每一条 union 语句[union, $all].
	 * @param string $all TRUE 显示全部行, 默认 FALSE.
	 * @return \wuyuan\base\Model
	 */
	final public function union($param, $all = FALSE) {
		if(is_array($param)) {
			foreach($param as & $v) {
				$v[0] = $this->resolveTableName($v[0]);
				// 处理 union 表名自动补齐前缀.
				if(!isset($v[1])) {
					$v[1] = FALSE;
				}
			}
			
			unset($v);
			$this->_opts['union'] = $param;
		} else {
			$param = $this->resolveTableName($param);
			$this->_opts['union'][] = [$param, $all];
		}
		
		return $this;
	}
	
	/**
	 * Data.
	 * 
	 * 
	 * @access pubilc
	 * @param array $param 处理的数据.
	 * @return \wuyuan\base\Model
	 */
	final public function data(array $param) {
		if($this->_isExecFilterField) {
			$param = $this->_autoFilterField($param);
		}
		
		$this->_opts['data'] = $param;
		return $this;
	}
	
	/**
	 * SELECT DISTINCT.
	 * 
	 * @access public
	 * @param boolean $param TRUE 去掉重复值.
	 * @return \wuyuan\base\Model
	 */
	final public function distinct($param) {
		if($param) {
			$this->_opts['distinct'] = TRUE;
		} else {
			$this->_opts['distinct'] = FALSE;
		}
		
		return $this;
	}
	
	/**
	 * SQL 注释.
	 * 
	 * @access public
	 * @param string $param 注释内容.
	 * @return \wuyuan\base\Model
	 */
	final public function comment($param) {
		$this->_opts['comment'] = $param;
		return $this;
	}
	
	/**
	 * 构造方法.
	 * 
	 * @access public
	 * @param string $tableName 表名, 默认 NULL, 表示模型名计算表名.
	 * @param array $configs 数据库连接配置项, 默认 [], 表示使用配置文件中的配置.
	 * @return void
	 */
	public function __construct($tableName = NULL, array $configs = []) {
		if(empty($configs)) {
			$configs = Config::get('wuyuan\db\Connection');
		}
		
		$this->conn = Connection::getInstance($configs);
		$this->_tablePrefix = $configs['tablePrefix'];
		if(NULL === $tableName) {
			// 计算表名.
			$this->_className = get_class($this);
			$modelSuffix = Config::get('model_suffix');
			$modelName = substr(substr($this->_className, strrpos($this->_className, '\\') + 1), 0, -strlen($modelSuffix));
			// 将 UserDetail 转换成 user_detail.
			$tableName = trim(preg_replace('/[A-Z]/', $this->tableSplit . '$0', $modelName), $this->tableSplit);
		}
		
		$this->_tableName = strtolower($tableName);
	}
	
	/**
	 * 析构方法.
	 * 
	 * @access public
	 * @return void
	 */
	public function __destruct() {
		$this->conn = NULL;
	}
	
}
