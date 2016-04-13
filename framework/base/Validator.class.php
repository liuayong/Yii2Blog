<?php

namespace wuyuan\base;

/**
 * wuyuan Form 表单验证类.
 * 提供了 required, email, number, integer, url, regexp, in, notin, compare, callback, length, unique 验证.
 * 其中 email, integer, url, regexp, callback 都是使用 filter_var 实现的.
 * 规则格式:
 * ['name' => '规则名称', 'field' => '验证字段', 'msg' => '失败信息', 'opts' => '额外选项', 'on' => '验证场景',
 * 'for' => '验证条件', 'depends' => callable]
 * 一. 规则详细说明:
 * 1. 规则名称(必须): required, email, number, integer, url, regexp, in, notin, compare, callback, length, unique.
 * 2. 验证字段(必须): 一维数组.
 * 3. 失败信息(必须): 验证不通过的提示信息.
 * 4. 额外选项: 具体验证规则提供的额外选项.
 * 5. 一个有效规则至少包含 name, field, msg.
 * 6. 验证场景, 一维数组, 其值是自定义的.
 * 7. 验证条件, 数字, self::VALIDATE_MUST(必须验证), VALIDATE_NOT_EMPTY(值非空时), VALIDATE_EXIST(字段存在, 默认).
 * 8. 依赖回调, 回调返回 TRUE 执行验证, 返回 FALSE 跳过该规则.
 * 二. 额外选项说明:
 * 1. number : 'decimal': 小数位数, 允许最多的小数位数; 'min' : 最小值(>=), 'max' : 最大值(<=); 可以为负数.
 * 2. integer : 'min' : 最小值(>=), 'max' : 最大值(<=); 可以为负数.
 * 3. compare : 'field' : '待比较的字段名', 'operator' => '运算符, 默认 eq', 'formatter' => callable.
 * 运算符: eq(==), neq(!=), gt(>), egt(>=), lt(<), elt(<=); formatter : 格式化回调, 参数是待比较字段的值.
 * 4. length : 数字或数字字符串或数组, 数组可包含: 'min' : 最小位数(>=), 'max' : 最大位数(<=), 'charset' : 字符集(默认 utf-8). 
 * 5. regexp : 正则表达式.
 * 6. in, notin : 一维数组.
 * 7. unique : 一维数组, ['model' => '模型类名称|模型类对象', 'field' => '表中字段名'], 表中的字段名为空, 就用 field 字段值.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class Validator {

	/**
	 * 必须验证.
	 * 无论字段存在与否.
	 * 
	 * @var integer
	 */
	const VALIDATE_MUST = 1;
	
	/**
	 * 值不为空时验证.
	 * 
	 * @var integer
	 */
	const VALIDATE_NOT_EMPTY = 2;
	
	/**
	 * 字段存在时验证(默认).
	 * 
	 * @var integer
	 */
	const VALIDATE_EXIST = 3;
	
	/**
	 * 验证失败的错误信息.
	 * 
	 * @var array
	 */
	private $_errors = [];
	
	/**
	 * 验证字段显示名称.
	 * 
	 * @var array
	 */
	private $_fieldLabel = [];
	
	/**
	 * 验证 required.
	 *
	 * @access protected
	 * @param array $data 验证的数据.
	 * @param array $rule 验证规则.
	 * @return boolean 验证成功返回 TRUE; 否则返回 FALSE.
	 */
	protected function validateRequired(array $data, array $rule) {
		$result = TRUE;
		$pattern = '/\S+/'; // 正则.
		foreach($rule['field'] as $field) {
			// 有该字段的错误跳过该字段的其它规则验证.
			if(isset($this->_errors[$field])) {
				continue;
			}
			
			$flag = TRUE; // 验证成功标记.
			$isExec = FALSE; // 是否要执行验证过程, 为 TRUE 表示要执行验证过程.
			switch($rule['for']) {
				case self::VALIDATE_MUST: // 无论字段是否存在都验证.
					$flag = $isExec = isset($data[$field]) ? TRUE : FALSE;
					break;
				case self::VALIDATE_NOT_EMPTY: // 值不为空时验证.
					$isExec = isset($data[$field]) && '' !== $data[$field] ? TRUE : FALSE;
					break;
				default: // 默认存在字段就验证 VALIDATE_EXIST.
					$isExec = isset($data[$field]) ? TRUE : FALSE;
			}
			// 执行验证过程.
			if($isExec) {
				$flag = (boolean)preg_match($pattern, $data[$field]);
			}
			
			// 验证失败.
			if(!$flag) {
				$result = $result ? $flag : $result; // 返回值为TRUE, 置为 $flag; 否则保留原值. 
				if(!isset($this->_errors[$field])) {
					$this->_errors[$field] = $rule['msg'];
				}
			}
		}
		
		unset($data, $rule);
		return $result;
	}
	
	/**
	 * 验证邮箱.
	 * 
	 * @access protected
	 * @param array $data 验证的数据.
	 * @param array $rule 验证规则.
	 * @return boolean 验证成功返回 TRUE; 否则返回 FALSE.
	 */
	protected function validateEmail(array $data, array $rule) {
		$result = TRUE;
		foreach($rule['field'] as $field) {
			// 有该字段的错误跳过该字段的其它规则验证.
			if(isset($this->_errors[$field])) {
				continue;
			}
			
			$flag = TRUE; // 验证成功标记.
			$isExec = FALSE; // 是否要执行验证过程, 为 TRUE 表示要执行验证过程.
			switch($rule['for']) {
				case self::VALIDATE_MUST: // 无论字段是否存在都验证.
					$flag = $isExec = isset($data[$field]) ? TRUE : FALSE;
					break;
				case self::VALIDATE_NOT_EMPTY: // 值不为空时验证.
					$isExec = isset($data[$field]) && '' !== $data[$field] ? TRUE : FALSE;
					break;
				default: // 默认存在字段就验证 VALIDATE_EXIST.
					$isExec = isset($data[$field]) ? TRUE : FALSE;
			}
			// 执行验证过程.
			if($isExec) {
				$flag = (boolean)filter_var($data[$field], FILTER_VALIDATE_EMAIL);
			}
			
			// 验证失败.
			if(!$flag) {
				$result = $result ? $flag : $result; // 返回值为TRUE, 置为 $flag; 否则保留原值. 
				if(!isset($this->_errors[$field])) {
					$this->_errors[$field] = $rule['msg'];
				}
			}
		}
		
		unset($data, $rule);
		return $result;
	}

	/**
	 * 验证数字.
	 * 选项: 'decimal': 小数位数, 允许最多的小数位数; 可以为负数.
	 * 
	 * @access protected
	 * @param array $data 验证的数据.
	 * @param array $rule 验证规则.
	 * @return boolean 验证成功返回 TRUE; 否则返回 FALSE.
	 */
	protected function validateNumber(array $data, array $rule) {
		$result = TRUE;
		foreach($rule['field'] as $field) {
			// 有该字段的错误跳过该字段的其它规则验证.
			if(isset($this->_errors[$field])) {
				continue;
			}
			
			$flag = TRUE; // 验证成功标记.
			$isExec = FALSE; // 是否要执行验证过程, 为 TRUE 表示要执行验证过程.
			switch($rule['for']) {
				case self::VALIDATE_MUST: // 无论字段是否存在都验证.
					$flag = $isExec = isset($data[$field]) ? TRUE : FALSE;
					break;
				case self::VALIDATE_NOT_EMPTY: // 值不为空时验证.
					$isExec = isset($data[$field]) && '' !== $data[$field] ? TRUE : FALSE;
					break;
				default: // 默认存在字段就验证 VALIDATE_EXIST.
					$isExec = isset($data[$field]) ? TRUE : FALSE;
			}
			// 执行验证过程.
			if($isExec) {
				// 是否点数, $flag 为值.
				$flag = filter_var($data[$field], FILTER_VALIDATE_FLOAT);
				if(FALSE !== $flag) { // 不等于 FALSE, 表示是浮点数.
					$data['field'] = $flag;
					$flag = TRUE;
					// min, max 检测.
					$min = isset($rule['opts']['min']) ? (integer)$rule['opts']['min'] : '';
					$max = isset($rule['opts']['max']) ? (integer)$rule['opts']['max'] : '';
					if('' !== $min && '' !== $max) {
						$flag = $data[$field] >= $min && $data[$field] <= $max;
					} elseif('' !== $min) {
						$flag = $data[$field] >= $min;
					} elseif('' !== $max) {
						$flag = $data[$field] <= $max;
					} else {
						// 小数位数检测.
						$decimal = isset($rule['opts']['decimal']) ? (integer)$rule['opts']['decimal'] : 0;
						if($decimal >= 1) {
							$pattern = '/^(?:[-]?)\d+(?:\.\d{1,'. $decimal .'})?$/';
							$flag = (boolean)preg_match($pattern, $data[$field]);
						}
					}
				}
			}
			
			// 验证失败.
			if(!$flag) {
				$result = $result ? $flag : $result; // 返回值为TRUE, 置为 $flag; 否则保留原值. 
				if(!isset($this->_errors[$field])) {
					$this->_errors[$field] = $rule['msg'];
				}
			}
		}
		
		unset($data, $rule);
		return $result;
	}

	/**
	 * 验证整数.
	 * 选项: 'min' : 最小值(>=), 'max' : 最大值(<=); 可以为负数.
	 *
	 * @access protected
	 * @param array $data 验证的数据.
	 * @param array $rule 验证规则.
	 * @return boolean 验证成功返回 TRUE; 否则返回 FALSE.
	 */
	protected function validateInteger(array $data, array $rule) {
		$result = TRUE;
		foreach($rule['field'] as $field) {
			// 有该字段的错误跳过该字段的其它规则验证.
			if(isset($this->_errors[$field])) {
				continue;
			}
			
			$flag = TRUE; // 验证成功标记.
			$isExec = FALSE; // 是否要执行验证过程, 为 TRUE 表示要执行验证过程.
			switch($rule['for']) {
				case self::VALIDATE_MUST: // 无论字段是否存在都验证.
					$flag = $isExec = isset($data[$field]) ? TRUE : FALSE;
					break;
				case self::VALIDATE_NOT_EMPTY: // 值不为空时验证.
					$isExec = isset($data[$field]) && '' !== $data[$field] ? TRUE : FALSE;
					break;
				default: // 默认存在字段就验证 VALIDATE_EXIST.
					$isExec = isset($data[$field]) ? TRUE : FALSE;
			}
			// 执行验证过程.
			if($isExec) {
				$min = isset($rule['opts']['min']) ? (integer)$rule['opts']['min'] : '';
				$max = isset($rule['opts']['max']) ? (integer)$rule['opts']['max'] : '';
				$opts = [];
				if('' !== $min && '' !== $max) {
					$opts['options'] = ['min_range' => $min, 'max_range' => $max];
				} elseif('' !== $min) {
					$opts['options'] = ['min_range' => $min];
				} elseif('' !== $max) {
					$opts['options'] = ['max_range' => $max];
				}
					
				$flag = FALSE === filter_var($data[$field], FILTER_VALIDATE_INT, $opts) ? FALSE : TRUE;
			}
			
			// 验证失败.
			if(!$flag) {
				$result = $result ? $flag : $result; // 返回值为TRUE, 置为 $flag; 否则保留原值. 
				if(!isset($this->_errors[$field])) {
					$this->_errors[$field] = $rule['msg'];
				}
			}
		}
		
		unset($data, $rule);
		return $result;
	}

	/**
	 * 验证 URL.
	 *
	 * @access protected
	 * @param array $data 验证的数据.
	 * @param array $rule 验证规则.
	 * @return boolean 验证成功返回 TRUE; 否则返回 FALSE.
	 */
	protected function validateUrl(array $data, array $rule) {
		$result = TRUE;
		foreach($rule['field'] as $field) {
			// 有该字段的错误跳过该字段的其它规则验证.
			if(isset($this->_errors[$field])) {
				continue;
			}
			
			$flag = TRUE; // 验证成功标记.
			$isExec = FALSE; // 是否要执行验证过程, 为 TRUE 表示要执行验证过程.
			switch($rule['for']) {
				case self::VALIDATE_MUST: // 无论字段是否存在都验证.
					$flag = $isExec = isset($data[$field]) ? TRUE : FALSE;
					break;
				case self::VALIDATE_NOT_EMPTY: // 值不为空时验证.
					$isExec = isset($data[$field]) && '' !== $data[$field] ? TRUE : FALSE;
					break;
				default: // 默认存在字段就验证 VALIDATE_EXIST.
					$isExec = isset($data[$field]) ? TRUE : FALSE;
			}
			// 执行验证过程.
			if(isset($data[$field]) && '' !== $data[$field]) {
				$flag = (boolean)filter_var($data[$field], FILTER_VALIDATE_URL);
			}
				
			// 验证失败.
			if(!$flag) {
				$result = $result ? $flag : $result; // 返回值为TRUE, 置为 $flag; 否则保留原值.
				if(!isset($this->_errors[$field])) {
					$this->_errors[$field] = $rule['msg'];
				}
			}
		}
		
		unset($data, $rule);
		return $result;
	}

	/**
	 * 验证正则.
	 *
	 * @access protected
	 * @param array $data 验证的数据.
	 * @param array $rule 验证规则.
	 * @return boolean 验证成功返回 TRUE; 否则返回 FALSE.
	 */
	protected function validateRegexp(array $data, array $rule) {
		$result = TRUE;
		foreach($rule['field'] as $field) {
			// 有该字段的错误跳过该字段的其它规则验证.
			if(isset($this->_errors[$field])) {
				continue;
			}
			
			$flag = TRUE; // 验证成功标记.
			$isExec = FALSE; // 是否要执行验证过程, 为 TRUE 表示要执行验证过程.
			switch($rule['for']) {
				case self::VALIDATE_MUST: // 无论字段是否存在都验证.
					$flag = $isExec = isset($data[$field]) ? TRUE : FALSE;
					break;
				case self::VALIDATE_NOT_EMPTY: // 值不为空时验证.
					$isExec = isset($data[$field]) && '' !== $data[$field] ? TRUE : FALSE;
					break;
				default: // 默认存在字段就验证 VALIDATE_EXIST.
					$isExec = isset($data[$field]) ? TRUE : FALSE;
			}
			// 执行验证过程.
			if($isExec) {
				$opts['options'] = ['regexp' => $rule['opts']];
				$flag = FALSE === filter_var($data[$field], FILTER_VALIDATE_REGEXP, $opts) ? FALSE : TRUE;
			}
		
			// 验证失败.
			if(!$flag) {
				$result = $result ? $flag : $result; // 返回值为TRUE, 置为 $flag; 否则保留原值.
				if(!isset($this->_errors[$field])) {
					$this->_errors[$field] = $rule['msg'];
				}
			}
		}
		
		unset($data, $rule);
		return $result;
	}

	/**
	 * 验证 in.
	 *
	 * @access protected
	 * @param array $data 验证的数据.
	 * @param array $rule 验证规则.
	 * @return boolean 验证成功返回 TRUE; 否则返回 FALSE.
	 */
	protected function validateIn(array $data, array $rule) {
		$result = TRUE;
		foreach($rule['field'] as $field) {
			// 有该字段的错误跳过该字段的其它规则验证.
			if(isset($this->_errors[$field])) {
				continue;
			}
			
			$flag = TRUE; // 验证成功标记.
			$isExec = FALSE; // 是否要执行验证过程, 为 TRUE 表示要执行验证过程.
			switch($rule['for']) {
				case self::VALIDATE_MUST: // 无论字段是否存在都验证.
					$flag = $isExec = isset($data[$field]) ? TRUE : FALSE;
					break;
				case self::VALIDATE_NOT_EMPTY: // 值不为空时验证.
					$isExec = isset($data[$field]) && '' !== $data[$field] ? TRUE : FALSE;
					break;
				default: // 默认存在字段就验证 VALIDATE_EXIST.
					$isExec = isset($data[$field]) ? TRUE : FALSE;
			}
			// 执行验证过程.
			if($isExec) {
				$flag = in_array($data[$field], $rule['opts'], TRUE);
			}
		
			// 验证失败.
			if(!$flag) {
				$result = $result ? $flag : $result; // 返回值为TRUE, 置为 $flag; 否则保留原值.
				if(!isset($this->_errors[$field])) {
					$this->_errors[$field] = $rule['msg'];
				}
			}
		}
		
		unset($data, $rule);
		return $result;
	}
	
	/**
	 * 验证 notin.
	 *
	 * @access protected
	 * @param array $data 验证的数据.
	 * @param array $rule 验证规则.
	 * @return boolean 验证成功返回 TRUE; 否则返回 FALSE.
	 */
	protected function validateNotin(array $data, array $rule) {
		$result = TRUE;
		foreach($rule['field'] as $field) {
			// 有该字段的错误跳过该字段的其它规则验证.
			if(isset($this->_errors[$field])) {
				continue;
			}
			
			$flag = TRUE; // 验证成功标记.
			$isExec = FALSE; // 是否要执行验证过程, 为 TRUE 表示要执行验证过程.
			switch($rule['for']) {
				case self::VALIDATE_MUST: // 无论字段是否存在都验证.
					$flag = $isExec = isset($data[$field]) ? TRUE : FALSE;
					break;
				case self::VALIDATE_NOT_EMPTY: // 值不为空时验证.
					$isExec = isset($data[$field]) && '' !== $data[$field] ? TRUE : FALSE;
					break;
				default: // 默认存在字段就验证 VALIDATE_EXIST.
					$isExec = isset($data[$field]) ? TRUE : FALSE;
			}
			// 执行验证过程.
			if($isExec) {
				$flag = !in_array($data[$field], $rule['opts'], TRUE);
			}
		
			// 验证失败.
			if(!$flag) {
				$result = $result ? $flag : $result; // 返回值为TRUE, 置为 $flag; 否则保留原值.
				if(!isset($this->_errors[$field])) {
					$this->_errors[$field] = $rule['msg'];
				}
			}
		}
		
		unset($data, $rule);
		return $result;
	}

	/**
	 * 验证 compare.
	 *
	 * @access protected
	 * @param array $data 验证的数据.
	 * @param array $rule 验证规则.
	 * @throws \InvalidArgumentException
	 * @return boolean 验证成功返回 TRUE; 否则返回 FALSE.
	 */
	protected function validateCompare(array $data, array $rule) {
		$result = TRUE;
		foreach($rule['field'] as $field) {
			// 有该字段的错误跳过该字段的其它规则验证.
			if(isset($this->_errors[$field])) {
				continue;
			}
			
			$flag = TRUE; // 验证成功标记.
			$isExec = FALSE; // 是否要执行验证过程, 为 TRUE 表示要执行验证过程.
			switch($rule['for']) {
				case self::VALIDATE_MUST: // 无论字段是否存在都验证.
					$flag = $isExec = isset($data[$field]) ? TRUE : FALSE;
					break;
				case self::VALIDATE_NOT_EMPTY: // 值不为空时验证.
					$isExec = isset($data[$field]) && '' !== $data[$field] ? TRUE : FALSE;
					break;
				default: // 默认存在字段就验证 VALIDATE_EXIST.
					$isExec = isset($data[$field]) ? TRUE : FALSE;
			}
			// 执行验证过程.
			if($isExec) {
				$opts = $rule['opts']; // 额外选项.
				$oldValue = $data[$field];
				$newValue = isset($data[$opts['field']]) ? $data[$opts['field']] : '';
				$allowOperators = ['eq' => 0, 'neq' => 1, 'gt' => 2, 'egt' => 3, 'lt' => 4, 'elt' => 5];
				// eq(==), neq(!=), gt(>), egt(>=), lt(<), elt(<=)
				if(isset($opts['formatter']) && is_callable($opts['formatter'])) {
					$oldValue = call_user_func($opts['formatter'], $oldValue);
					$newValue = call_user_func($opts['formatter'], $newValue);
				}
				
				$operator = isset($allowOperators[$opts['operator']]) ? $allowOperators[$opts['operator']] : 0;
				switch($operator) {
					case 0: // ==
						$flag = $oldValue == $newValue;
						break;
					case 1: // !=
						$flag = $oldValue != $newValue;
						break;
					case 2: // >
						$flag = $oldValue > $newValue;
						break;
					case 3: // >=
						$flag = $oldValue >= $newValue;
						break;
					case 4: // <
						$flag = $oldValue < $newValue;
						break;
					case 5: // <=
						$flag = $oldValue <= $newValue;
						break;
					default:
						throw new \InvalidArgumentException('Invalid operator of compare rule.');
				}
			}
			
			// 验证失败.
			if(!$flag) {
				$result = $result ? $flag : $result; // 返回值为TRUE, 置为 $flag; 否则保留原值.
				if(!isset($this->_errors[$field])) {
					$this->_errors[$field] = $rule['msg'];
				}
			}
		}
		
		unset($data, $rule);
		return $result;
	}

	/**
	 * 验证 callback.
	 *
	 * @access protected
	 * @param array $data 验证的数据.
	 * @param array $rule 验证规则.
	 * @return boolean 验证成功返回 TRUE; 否则返回 FALSE.
	 */
	protected function validateCallback(array $data, array $rule) {
		$result = TRUE;
		foreach($rule['field'] as $field) {
			// 有该字段的错误跳过该字段的其它规则验证.
			if(isset($this->_errors[$field])) {
				continue;
			}
			
			$flag = TRUE; // 验证成功标记.
			$isExec = FALSE; // 是否要执行验证过程, 为 TRUE 表示要执行验证过程.
			switch($rule['for']) {
				case self::VALIDATE_MUST: // 无论字段是否存在都验证.
					$flag = $isExec = isset($data[$field]) ? TRUE : FALSE;
					break;
				case self::VALIDATE_NOT_EMPTY: // 值不为空时验证.
					$isExec = isset($data[$field]) && '' !== $data[$field] ? TRUE : FALSE;
					break;
				default: // 默认存在字段就验证 VALIDATE_EXIST.
					$isExec = isset($data[$field]) ? TRUE : FALSE;
			}
			// 执行验证过程.
			if($isExec) {
				$opts['options'] = isset($rule['opts']) ? $rule['opts'] : '';
				$flag = filter_var($data[$field], FILTER_CALLBACK, $opts);
			}
			
			// 验证失败.
			if(!$flag) {
				$result = $result ? $flag : $result; // 返回值为TRUE, 置为 $flag; 否则保留原值.
				if(!isset($this->_errors[$field])) {
					$this->_errors[$field] = $rule['msg'];
				}
			}
		}
		
		unset($data, $rule);
		return $result;
	}

	/**
	 * 验证 length.
	 * 数字或数字字符串或数组, 数组可包含: 'min' : 最小位数(>=), 'max' : 最大位数(<=), 'charset' : 字符集(默认 utf-8).
	 *
	 * @access protected
	 * @param array $data 验证的数据.
	 * @param array $rule 验证规则.
	 * @return boolean 验证成功返回 TRUE; 否则返回 FALSE.
	 */
	protected function validateLength(array $data, array $rule) {
		$result = TRUE;
		foreach($rule['field'] as $field) {
			// 有该字段的错误跳过该字段的其它规则验证.
			if(isset($this->_errors[$field])) {
				continue;
			}
			
			$flag = TRUE; // 验证成功标记.
			$isExec = FALSE; // 是否要执行验证过程, 为 TRUE 表示要执行验证过程.
			switch($rule['for']) {
				case self::VALIDATE_MUST: // 无论字段是否存在都验证.
					$flag = $isExec = isset($data[$field]) ? TRUE : FALSE;
					break;
				case self::VALIDATE_NOT_EMPTY: // 值不为空时验证.
					$isExec = isset($data[$field]) && '' !== $data[$field] ? TRUE : FALSE;
					break;
				default: // 默认存在字段就验证 VALIDATE_EXIST.
					$isExec = isset($data[$field]) ? TRUE : FALSE;
			}
			// 执行验证过程.
			if($isExec) {
				$opts = $rule['opts'];
				$min = isset($opts['min']) ? $opts['min'] : '';
				$max = isset($opts['max']) ? $opts['max'] : '';
				$charset = isset($opts['charset']) ? $opts['charset'] : 'utf-8';
				$length = mb_strlen($data[$field], $charset);
				
				if(is_numeric($opts)) {
					$opts = (integer)$opts;
					$flag = $length === $opts;
				} elseif('' !== $min && '' !== $max) {
					$min = (integer)$min;
					$max = (integer)$max;
					$flag = $length >= $min && $length <= $max;
				} elseif('' !== $min) {
					$min = (integer)$min;
					$flag = $length >= $min;
				} elseif('' !== $max) {
					$max = (integer)$max;
					$flag = $length <= $max;
				}
			}
		
			// 验证失败.
			if(!$flag) {
				$result = $result ? $flag : $result; // 返回值为TRUE, 置为 $flag; 否则保留原值.
				if(!isset($this->_errors[$field])) {
					$this->_errors[$field] = $rule['msg'];
				}
			}
		}
		
		unset($data, $rule);
		return $result;
	}
	
	/**
	 * 验证 unique.
	 *
	 * @access protected
	 * @param array $data 验证的数据.
	 * @param array $rule 验证规则.
	 * @return boolean 验证成功返回 TRUE; 否则返回 FALSE.
	 */
	protected function validateUnique(array $data, array $rule) {
		$result = TRUE;
		foreach($rule['field'] as $field) {
			// 有该字段的错误跳过该字段的其它规则验证.
			if(isset($this->_errors[$field])) {
				continue;
			}
			
			$flag = TRUE; // 验证成功标记.
			$isExec = FALSE; // 是否要执行验证过程, 为 TRUE 表示要执行验证过程.
			switch($rule['for']) {
				case self::VALIDATE_MUST: // 无论字段是否存在都验证.
					$flag = $isExec = isset($data[$field]) ? TRUE : FALSE;
					break;
				case self::VALIDATE_NOT_EMPTY: // 值不为空时验证.
					$isExec = isset($data[$field]) && '' !== $data[$field] ? TRUE : FALSE;
					break;
				default: // 默认存在字段就验证 VALIDATE_EXIST.
					$isExec = isset($data[$field]) ? TRUE : FALSE;
			}
			// 执行验证过程.
			if($isExec) {
				$opts = $rule['opts'];
				/* @var \wuyuan\base\Model $model */
				$model = NULL;
				if($opts['model'] instanceof \wuyuan\base\Model) {
					$model = $opts['model'];
				} else {
					$model = new $opts['model']();
				}
				$_tableField = !isset($opts['field']) || empty($opts['field']) ? $field : $opts['field'];
				$_tmpResult = $model->where($_tableField . '=:unique')->bindParam(':unique', $data[$field])->find();
				$flag = empty($_tmpResult) ? TRUE : FALSE;
				unset($model, $_tableField, $_tmpResult);
			}
		
			// 验证失败.
			if(!$flag) {
				$result = $result ? $flag : $result; // 返回值为TRUE, 置为 $flag; 否则保留原值.
				if(!isset($this->_errors[$field])) {
					$this->_errors[$field] = $rule['msg'];
				}
			}
		}
		
		unset($data, $rule);
		return $result;
	}

	/**
	 * 处理验证规则.
	 * 过滤出有效的验证规则, 即必须包含 name, field, msg 项和符合场景的规则.
	 * 符合场景的规则即规则中没有 on 项和 on 项值包含 $scenario 的规则.
	 * 
	 * @access protected
	 * @param array $rules 验证规则.
	 * @param string $scenario 场景.
	 * @return array 返回有效的验证规则.
	 */
	protected function resolveRules(array $rules, $scenario) {
		$result = [];
		foreach($rules as $row) {
			// 有效的规则
			if(isset($row['name']) && isset($row['field']) && isset($row['msg'])) {
				$_r = [];
				if(!isset($row['on'])) {
					$_r = $row;
				} elseif(NULL !== $scenario && in_array($scenario, $row['on'], TRUE)) {
					$_r = $row;
				}
				
				// 默认 VALIDATE_EXIST 存在字段就验证.
				$_r['for'] = isset($_r['for']) && in_array((integer)$_r['for'], [1, 2, 3], TRUE) ? (integer)$_r['for'] : self::VALIDATE_EXIST;
				$result[] = $_r;
			}
		}
		
		unset($rules);
		return array_values($result);
	}

	/**
	 * 执行验证.
	 * 
	 * @access public
	 * @param array $data 验证的数据.
	 * @param array $rules 验证规则.
	 * @param string $scenario 验证场景, 默认 NULL, 表示验证没有指定场景的规则; 
	 * 指定值时, 只会验证指定的场景规则和没有指定场景的规则.
	 * @throws \InvalidArgumentException
	 * @return boolean 验证成功返回 TRUE; 否则返回 FALSE.
	 */
	public function validate(array $data, array $rules, $scenario = NULL) {
		// 有效的验证规则.
		$rules = $this->resolveRules($rules, $scenario);
		$flag = TRUE; // 验证成功标记.
		// 循环执行验证.
		foreach($rules as $row) {
			// 有效的依赖回调且返回 FALSE, 跳过该规则.
			if(isset($row['depends']) && FALSE === call_user_func($row['depends'])) {
				continue;
			}
			
			// required, email, number, integer, url, regexp, in, notin, compare, callback, length, unique.
			$validateFuncName = 'validate' . ucfirst($row['name']);
			$res = $this->$validateFuncName($data, $row);
			// 验证失败, 将标记置为 FALSE, 表示有验证失败项.
			if(FALSE === $res) {
				$flag = FALSE;
			}
		}
		
		unset($data, $rules);
		return $flag;
	}
	
	/**
	 * 检测字段是否验证失败.
	 * 
	 * @access public
	 * @param string $field 字段名.
	 * @return boolean
	 */
	public function hasError($field) {
		return isset($this->_errors[$field]) ? TRUE : FALSE;
	}

	/**
	 * 获取验证失败提示信息.
	 * $field 为 NULL, 返回全部验证失败提示信息.
	 * 
	 * @access public
	 * @param string $field 字段名, 默认 NULL.
	 * @return string|array $field 为 NULL, 返回 array; $field 无效返回 NULL.
	 */
	public function getError($field = NULL) {
		if(NULL === $field) {
			return $this->_errors;
		}
		
		return isset($this->_errors[$field]) ? $this->_errors[$field] : NULL;
	}
	
	/**
	 * 设置字段显示名称.
	 * 格式: '字段名' => '显示名称'.
	 * 
	 * @access public
	 * @param array $fieldLabel 显示名称键值对.
	 * @return void
	 */
	public function setFieldLabel(array $fieldLabel = []) {
		$this->_fieldLabel = $fieldLabel;
	}
	
	/**
	 * 获取字段显示名称.
	 * 
	 * @access public
	 * @param string $field 字段名, 默认 NULL.
	 * @return string|array $field 为 NULL, 返回 array; $field 无效返回 NULL.
	 */
	public function getFieldLabel($field = NULL) {
		if(NULL === $field) {
			return $this->_fieldLabel;
		}
		
		return isset($this->_fieldLabel[$field]) ? $this->_fieldLabel[$field] : NULL;
	}

	/**
	 * 获取本类的单例对象.
	 * 
	 * @access public
	 * @return \wy\base\Validator
	 */
	public static function getInstance() {
		static $ins = NULL;
		if(NULL === $ins) {
			$ins = new static();
		}
		
		return $ins;
	}

}
