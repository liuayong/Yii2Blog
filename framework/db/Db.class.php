<?php

namespace wuyuan\db;

/**
 * wuyuan 数据库驱动抽象父类.
 * 主要提供驱动类的一些公用接口方法, 解析生成的 SQL 语句处理及安全过滤等. 内部默认实现针对 MySQL 语法
 * 解析, 其它的驱动类需要在驱动类上重写相关的解析方法以便为 Connection 提供统一的调用接口.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
abstract class Db {
	
	/**
	 * 主机名或 IP.
	 * 
	 * @var string
	 */
	protected $host = '127.0.0.1';
	
	/**
	 * 端口号.
	 * 
	 * @var string
	 */
	protected $port = '3306';
	
	/**
	 * 连接用户名.
	 * 
	 * @var string
	 */
	protected $userName = 'root';
	
	/**
	 * 连接用户密码.
	 * 
	 * @var string
	 */
	protected $passwd = NULL;
	
	/**
	 * 默认数据库名.
	 * 
	 * @var string
	 */
	protected $dbName = NULL;
	
	/**
	 * 连接字符集.
	 * 
	 * @var string
	 */
	protected $charset = 'utf8';
	
	/**
	 * 连接标识.
	 * 数据库底层连接对象.
	 * 
	 * @var \mysqli
	 */
	protected $linkId = NULL;
	
	/**
	 * 结果集对象.
	 * 底层数据库结果对象.
	 * 
	 * @var \mysqli_result
	 */
	protected $result = NULL;
	
	/**
	 * 最近一次操作所影响的行数.
	 * 
	 * @var integer
	 */
	protected $affectedRows = 0;
	
	/**
	 * 最近一次操作的 SQL 语句.
	 * 
	 * @var string
	 */
	protected $lastSql = NULL;
	
	/**
	 * 最近一次 INSERT 操作的自增长 ID.
	 * 
	 * @var integer
	 */
	protected $lastInsertId = 0;
	
	/**
	 * 关闭标记.
	 * 
	 * @var boolean
	 */
	protected $closed = FALSE;
	
	/**
	 * 事务开启标记.
	 * 
	 * @var boolean
	 */
	protected $transStarted = FALSE;
	
	/**
	 * 激活的连接配置项.
	 * 
	 * @var array
	 */
	protected $activeConfig = [];
	
	/**
	 * SQL 语句选项.
	 * 
	 * @var array $sqlOpts
	 */
	protected $sqlOpts = [
		'distinct' => FALSE,	// 值为 boolean.
		'field' => [],			// 值为 string|array.
		'table' => '',			// 值为 string|array.
		'join' => '',			// 值为 string|array.
		'data' => [],			// 值为 array.
		'bindData' => [],		// 值为 array, 如 ':name' => 'xiaowang'.
		'where' => '',			// 值为 string.
		'group' => '',			// 值为 string|array.
		'having' => '',			// 值为 string.
		'order' => '',			// 值为 string|array.
		'limit' => '',			// 值为 string|integer|array.
		'union' => [],			// 值为 array, ['SQL', TRUE|FALSE], 第 2 个参数为 TRUE 用于标识  UNION ALL.
		'comment' => ''			// 值为 string.
	];
	
	/**
	 * 解析 SQL 注释.
	 * 
	 * @access protected
	 * @param string $param 注释.
	 * @return string
	 */
	protected function parseComment($param) {
		return empty($param) ? '' : ' -- ' . $param;
	}
	
	/**
	 * 解析 UNION.
	 * 
	 * @access protected
	 * @param array $param 一维数组.
	 * @return string 为字符串原样返回, 为数组时返回用空格分隔后的字符串.
	 */
	protected function parseUnion(array $param) {
		$result = '';
		foreach($param as $v) {
			if(isset($v[1]) && $v[1]) {
				$result .= ' UNION ALL ' . $v[0];
			} else {
				$result .= ' UNION ' . $v[0];
			}
		}
		
		return trim($result);
	}
	
	/**
	 * 解析 LIMIT.
	 * 
	 * @access protected
	 * @param string|array $param 字符串或一维数组.
	 * @return string 返回 LIMIT 0 或 LIMIT 0,10 的字符串.
	 */
	protected function parseLimit($param) {
		if(empty($param)) {
			return '';
		}
		
		if(is_array($param)) {
			if(count($param) >= 2) {
				list($offset, $limit) = $param;
				$param = $offset . ',' . $limit;
			} else {
				$param = $param[0];
			}
		}
		
		return 'LIMIT ' . $param;
	}
	
	/**
	 * 解析 ORDER BY.
	 * 
	 * @access protected
	 * @param string|array $param 字符串或一维数组.
	 * @return string $param 为字符串原样返回, 为数组时返回用逗号分隔后的字符串.
	 */
	protected function parseOrder($param) {
		if(empty($param)) {
			return '';
		}
		
		return 'ORDER BY ' . (is_array($param) ? implode(',', $param) : $param);
	}
	
	/**
	 * 解析 GROUP BY.
	 *
	 * @access protected
	 * @param string|array $param 字符串或一维数组.
	 * @return string $param 为字符串原样返回, 为数组时返回用逗号分隔后的字符串.
	 */
	protected function parseGroup($param) {
		if(empty($param)) {
			return '';
		}
		
		return 'GROUP BY ' . (is_array($param) ? implode(',', $param) : $param);
	}
	
	/**
	 * 解析绑定的参数数据.
	 * 
	 * @access protected
	 * @param array $param 待处理的数据, [':name' => value] 键值对.
	 * @return array 返回经过 escape_string 转义后的 $param 参数数据.
	 */
	public function parseBindData(array $param) {
		if(empty($param)) {
			return [];
		}
		
		foreach($param as $k => & $v) {
			// 先把值用 escape_string 转义.
			if(is_string($v)) {
				try {
					$result = "'" . $this->quote($v) . "'";
					$v = $result;
				} catch(\Exception $e) {
					$v = "'". addslashes($v) ."'";
				}
			} elseif(is_bool($v)) {
				$v = $v ? 1 : 0;
			}
		}
		
		unset($v);
		return $param;
	}
	
	/**
	 * 解析数据.
	 * 将 PHP 的数据转换成数据库的数据格式.
	 * 
	 * @access protected
	 * @param array $param 待转换的数组.
	 * @return array
	 */
	protected function parseData(array $param) {
		foreach($param as $k => $v) {
			if(is_string($v) && 0 !== strncmp($v, 'exp(', 4)) {
				$param[$k] = "'" . $v . "'";
			} elseif(is_bool($v)) {
				$param[$k] = $v ? 1 : 0;
			} elseif(NULL === $v) {
				$param[$k] = 'null';
			} elseif(is_array($v)) {
				$param[$k] = $this->parseData($v);
			}
		}
			
		return $param;
	}
	
	/**
	 * 解析 JOIN.
	 *
	 * @access protected
	 * @param string|array $param 字符串或一维数组.
	 * @return string $param 为字符串原样返回, 为数组时返回用空格分隔后的字符串.
	 */
	protected function parseJoin($param) {
		if(empty($param)) {
			return '';
		}
		
		return is_array($param) ? implode(' ', $param) : $param;
	}

	/**
	 * 解析表名.
	 *
	 * @access protected
	 * @param string|array $param 字符串或一维数组.
	 * @return string $param 为字符串原样返回, 为数组时返回用逗号分隔后的字符串.
	 */
	protected function parseTable($param) {
		if(empty($param)) {
			return '';
		}
		
		return is_array($param) ? implode(',', $param) : $param;
	}

	/**
	 * 解析字段名.
	 *
	 * @access protected
	 * @param string|array $param 字符串或一维数组.
	 * @return array $param 为数组时, 原样返回; 为字符串时分解成数组返回.
	 */
	protected function parseField($param) {
		if(empty($param)) {
			return [];
		}
		
		return is_string($param) ? explode(',', $param) : $param;
	}
	
	/**
	 * 解析 SELECT DISTINCT.
	 * 
	 * @access protected
	 * @param boolean $param 为 DISTINCT, 默认 FALSE.
	 * @return string $param 为 TRUE, 返回 'DISTINCT'; 否则返回 ''.
	 */
	protected function parseDistinct($param = FALSE) {
		return (boolean)$param ? 'DISTINCT' : '';
	}
	
	/**
	 * 设置配置项.
	 * 
	 * @access public
	 * @param array $config 配置项.
	 * @return void
	 */
	public function setConfig(array $config) {
		// 默认配置.
		$conf = [
			'host' => '127.0.0.1',
			'port' => '3306',
			'userName' => 'root',
			'passwd' => NULL,
			'dbName' => NULL,
			'charset' => 'utf8'
		];
		
		$conf = array_merge($conf, $config);
		$this->host = $conf['host'];
		$this->port = $conf['port'];
		$this->userName = $conf['userName'];
		$this->passwd = $conf['passwd'];
		$this->dbName = $conf['dbName'];
		$this->charset = $conf['charset'];
		$this->activeConfig = $conf;
	}
	
	/**
	 * 查询记录.
	 * 
	 * @access public
	 * @param array $options SQL 语句选项.
	 * @param boolean $returnSql 返回 SQL 语句, 默认 FALSE; 为 TRUE 不执行 SQL, 只返回 SQL 语句.
	 * @return array|string 出错时抛异常.
	 */
	public function autoSelect(array $options, $returnSql = FALSE) {
		$options = array_merge($this->sqlOpts, $options);
		$opts = [];
		// 表名.
		$opts['table'] = '';
		if(!empty($options['table'])) {
			$opts['table'] = $this->parseTable($options['table']);
		}
		// distinct.
		$opts['distinct'] = '';
		if(!empty($options['distinct'])) {
			$opts['distinct'] = $this->parseDistinct($options['distinct']);
		}
		// 字段.
		$opts['field'] = '*';
		if(!empty($options['field'])) {
			$opts['field'] = implode(',', $this->parseField($options['field']));
		}
		// join.
		$opts['join'] = '';
		if(!empty($options['join'])) {
			$opts['join'] = $this->parseJoin($options['join']);
		}
		// where 条件.
		$opts['where'] = '';
		if(!empty($options['where'])) {
			$opts['where'] = $options['where'];
			if(!empty($options['bindData'])) {
				$bindData = $this->parseBindData($options['bindData']);
				$opts['where'] = str_replace(array_keys($bindData), array_values($bindData), $opts['where']);
			}
		}
		// group by.
		$opts['group'] = '';
		if(!empty($options['group'])) {
			$opts['group'] = $this->parseGroup($options['group']);
		}
		// having 条件.
		$opts['having'] = '';
		if(!empty($options['having'])) {
			$opts['having'] = $options['having'];
			if(!empty($options['bindData'])) {
				$bindData = $this->parseBindData($options['bindData']);
				$opts['having'] = str_replace(array_keys($bindData), array_values($bindData), $opts['having']);
			}
		}
		// order by.
		$opts['order'] = '';
		if(!empty($options['order'])) {
			$opts['order'] = $this->parseOrder($options['order']);
		}
		// limit.
		$opts['limit'] = '';
		if(!empty($options['limit'])) {
			$opts['limit'] = $this->parseLimit($options['limit']);
		}
		// union.
		$opts['union'] = '';
		if(!empty($options['union'])) {
			$opts['union'] = $this->parseUnion($options['union']);
		}
		// SQL 注释.
		$opts['comment'] = '';
		if(!empty($options['comment'])) {
			$opts['comment'] = $this->parseComment($options['comment']);
		}
		
		unset($options);
		// SELECT 语句模板.
		$strSql = 'SELECT%DISTINCT%%FIELD% FROM%TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT%%UNION%%COMMENT%';
		$sqlOpts = [
			'%DISTINCT%' => empty($opts['distinct']) ? '' : ' ' . $opts['distinct'],
			'%FIELD%' => empty($opts['field']) ? '' : ' ' . $opts['field'],
			'%TABLE%' => empty($opts['table']) ? '' : ' ' . $opts['table'],
			'%JOIN%' => empty($opts['join']) ? '' : ' ' . $opts['join'],
			'%WHERE%' => empty($opts['where']) ? '' : ' WHERE ' . $opts['where'],
			'%GROUP%' => empty($opts['group']) ? '' : ' ' . $opts['group'],
			'%HAVING%' => empty($opts['having']) ? '' : ' ' . $opts['having'],
			'%ORDER%' => empty($opts['order']) ? '' : ' ' . $opts['order'],
			'%LIMIT%' => empty($opts['limit']) ? '' : ' ' . $opts['limit'],
			'%UNION%' => empty($opts['union']) ? '' : ' ' . $opts['union'],
			'%COMMENT%' => empty($opts['comment']) ? '' : ' ' . $opts['comment']
		];

		// 拼装成 SQL.
		$strSql = str_replace(array_keys($sqlOpts), array_values($sqlOpts), $strSql);
		if(!empty($opts['union'])) {
			$pattern = '/(\s+(?:union\s+all|union\s+distinct|union)\s+)/im';
			$strSql = '(' . preg_replace($pattern, ') $0 (', $strSql) . ')';
		}
		unset($opts);
		if($returnSql) {
			return $strSql;
		}
		
		$result = $this->query($strSql) ? $this->fetchAll() : [];
		$this->freeResult();
		return $result;
	}
	
	/**
	 * 插入记录.
	 * 
	 * @access public
	 * @param array $options SQL 语句选项.
	 * @return boolean 成功返回 TRUE, 出错时抛异常.
	 */
	public function autoInsert(array $options) {
		$options = array_merge($this->sqlOpts, $options); // 合并 SQL 语句选项.
		$opts = [];
		// 表名.
		$opts['table'] = '';
		if(!empty($options['table'])) {
			$opts['table'] = $this->parseTable($options['table']);
		}
		// 字段.
		$opts['field'] = [];
		if(!empty($options['field'])) {
			$opts['field'] = $this->parseField($options['field']);
		}
		// SQL 注释.
		$opts['comment'] = '';
		if(!empty($options['comment'])) {
			$opts['comment'] = $this->parseComment($options['comment']);
		}
		// 插入的数据.
		$opts['data'] = [];
		if(!empty($options['data'])) {
			$opts['data'] = $this->parseData($options['data']);
		}
		unset($options);
		
		// 未指定字段, 判断 data 的 key, 若 key 是字符串认为 key 就是字段名; 若 data 为二维数组, 即一次性
		// 插入多条数据, 只有指定字段名或不指定, 指定时数据值按字段顺序; 不指定按表结构顺序指定.
		if(empty($opts['field'])) {
			$keys = array_keys($opts['data']);
			if(is_string(current($keys))) {
				$opts['field'] = $keys;
			}
			unset($keys);
		}
		
		// 生成待插入的数据字符串.
		$opts['value'] = [];
		if(is_array(current($opts['data']))) { // 插入多条数据.
			$tmpData = [];
			foreach($opts['data'] as $v) {
				$tmpData[] = '(' . implode(',', $v) . ')';
			}
			
			$opts['value'] = implode(',', $tmpData);
			unset($tmpData);
		} else { // 插入单条数据.
			// 插入单条数据时, 若有指定字段, 只插入有字段的数据.
			if(empty($opts['field'])) {
				$opts['value'] = '(' . implode(',', $opts['data']) . ')';
			} else { // 只插入有字段的数据.
				$tmpData = [];
				foreach($opts['field'] as $v) {
					if(isset($opts['data'][$v])) {
						$tmpData[] = $opts['data'][$v];
					}
				}
				
				$opts['value'] = '(' . implode(',', $tmpData) . ')';
				unset($tmpData);
			}
		}
		
		// INSERT 语句模板.
		$strSql = 'INSERT INTO %TABLE%%FIELD% VALUES %VALUE%%COMMENT%';
		$replaces = [
			'%TABLE%' => $opts['table'],
			'%FIELD%' => empty($opts['field']) ? '' : ' (' . implode(',', $opts['field']) . ')',
			'%VALUE%' => $opts['value'],
			'%COMMENT%' => empty($opts['comment']) ? '' : ' ' . $opts['comment']
		];
		
		$strSql = str_replace(array_keys($replaces), array_values($replaces), $strSql);
		unset($opts);
		// 执行 SQL.
		return $this->execute($strSql);
	}
	
	/**
	 * 删除记录.
	 * 
	 * @access public
	 * @param array $options SQL 语句选项.
	 * @return boolean 成功返回 TRUE, 出错抛异常.
	 */
	public function autoDelete(array $options) {
		$options = array_merge($this->sqlOpts, $options);
		$opts = [];
		// 表名.
		$opts['table'] = '';
		if(!empty($options['table'])) {
			$opts['table'] = $this->parseTable($options['table']);
		}
		// where 条件.
		$opts['where'] = '';
		if(!empty($options['where'])) {
			$opts['where'] = $options['where'];
			if(!empty($options['bindData'])) {
				$bindData = $this->parseBindData($options['bindData']);
				$opts['where'] = str_replace(array_keys($bindData), array_values($bindData), $opts['where']);
			}
		}
		// SQL 注释.
		$opts['comment'] = '';
		if(!empty($options['comment'])) {
			$opts['comment'] = $this->parseComment($options['comment']);
		}
		unset($options);
		
		// DELETE SQL 语句模板.
		$strSql = 'DELETE FROM %TABLE%%WHERE%%COMMENT%';
		$replaces = [
			'%TABLE%' => $opts['table'],
			'%WHERE%' => empty($opts['where']) ? '' : ' WHERE ' . $opts['where'],
			'%COMMENT%' => empty($opts['comment']) ? '' : ' ' . $opts['comment']
		];
		
		$strSql = str_replace(array_keys($replaces), array_values($replaces), $strSql);
		unset($opts);
		return $this->execute($strSql);
	}
	
	/**
	 * 更新记录.
	 *
	 * @access public
	 * @param array $options SQL 语句选项.
	 * @return boolean 成功返回 TRUE, 出错时抛异常.
	 */
	public function autoUpdate(array $options) {
		$options = array_merge($this->sqlOpts, $options);
		$opts = [];
		// 表名.
		$opts['table'] = '';
		if(!empty($options['table'])) {
			$opts['table'] = $this->parseTable($options['table']);
		}
		// 字段.
		$opts['field'] = [];
		if(!empty($options['field'])) {
			$opts['field'] = $this->parseField($options['field']);
		}
		// where 条件.
		$opts['where'] = '';
		if(!empty($options['where'])) {
			$opts['where'] = $options['where'];
			if(!empty($options['bindData'])) {
				$bindData = $this->parseBindData($options['bindData']);
				$opts['where'] = str_replace(array_keys($bindData), array_values($bindData), $opts['where']);
			}
		}
		// SQL 注释.
		$opts['comment'] = '';
		if(!empty($options['comment'])) {
			$opts['comment'] = $this->parseComment($options['comment']);
		}
		// 更新的数据.
		$opts['data'] = [];
		if(!empty($options['data'])) {
			$opts['data'] = $this->parseData($options['data']);
		}
		unset($options);
		
		// 若字段为空, 将数据的键名视为字段名; 字段不为空, 挨个取字段对应的数据;
		// 若数据中没有字段对应的键, 用字段的 key 取数据中的 key 的值.
		if(empty($opts['field'])) {
			$opts['field'] = array_keys($opts['data']);
		}
		
		$setList = []; // 将字段与数据对应.
		foreach($opts['field'] as $k => $v) {
			$tmpValue = NULL;
			if(isset($opts['data'][$v])) {
				$tmpValue = $opts['data'][$v];
			} elseif(isset($opts['data'][$k])) {
				$tmpValue = $opts['data'][$k];
			}
			
			if(NULL !== $tmpValue) {
				// 校验值是否包含表达式 SQL 表达式.
				if(0 === strncmp($tmpValue, 'exp(', 4)) {
					$pattern = '/^exp\((.*?)\)$/';
					$tmpValue = preg_replace($pattern, '$1', $tmpValue);
				}
				
				$setList[] = $v . '=' . $tmpValue;
			}
		}

		// UPDATE 语句模板.
		$strSql = 'UPDATE %TABLE% SET %LIST%%WHERE%%COMMENT%';
		$replaces = [
			'%TABLE%' => $opts['table'],
			'%LIST%' => implode(',', $setList),
			'%WHERE%' => empty($opts['where']) ? '' : ' WHERE ' . $opts['where'],
			'%COMMENT%' => empty($opts['comment']) ? '' : ' ' . $opts['comment']
		];
		
		$strSql = str_replace(array_keys($replaces), array_values($replaces), $strSql);
		unset($opts);
		return $this->execute($strSql);
	}
	
	/**
	 * 获取操作受影响的行数.
	 * 
	 * @access public
	 * @return integer
	 */
	public function affectedRows() {
		return $this->affectedRows;
	}
	
	/**
	 * 获取 lastSql.
	 * 
	 * @access public
	 * @return string
	 */
	public function lastSql() {
		return $this->lastSql;
	}
	
	/**
	 * 获取 lastInsertId.
	 * 
	 * @access public
	 * @return integer
	 */
	public function lastInsertId() {
		return $this->lastInsertId;
	}
	
	/**
	 * 获取原生数据库连接对象.
	 * 
	 * @access public
	 * @return \mysqli NULL 或 \mysqli 对象.
	 */
	public function originalLink() {
		return $this->linkId;
	}
	
	/**
	 * 获取本类的单例对象.
	 * 
	 * @access public
	 * @param array $configs 配置项.
	 * @return \wuyuan\db\Db
	 */
	public static function getInstance(array $configs = []) {
		static $db = NULL;
		if(NULL === $db) {
			$db = new static($configs);
		}
		
		return $db;
	}
	
	/**
	 * 构造方法.
	 * 
	 * @access public
	 * @param array $configs 配置项.
	 * @return void
	 */
	public function __construct(array $configs = []) {
		if(!empty($configs)) {
			$this->setConfig($configs);
		}
	}
	
	/**
	 * 析构方法.
	 * 
	 * @access public
	 * @return void
	 */
	public function __destruct() {
		$this->closed or $this->close();
	}
	
}
