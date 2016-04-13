<?php

namespace wuyuan\base;

/**
 * wuyuan 抽象控制器父类.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
abstract class Controller {
	
	/**
	 * 输出 json 数据的格式.
	 *
	 * @var array
	 */
	private $_ajaxData = [
		'status' => NULL,
		'msg' => NULL,
		'data' => []
	];
	
	/**
	 * 设置 json 输出的数据.
	 *
	 * @access protected
	 * @param integer $status 状态.
	 * @param string $msg 信息.
	 * @param array $data 附加数据, 默认 [].
	 * @return \wuyuan\base\Controller
	*/
	protected function setAjaxData($status, $msg, array $data = []) {
		$this->_ajaxData['status'] = $status;
		$this->_ajaxData['msg'] = $msg;
		$this->_ajaxData['data'] = $data;
		return $this;
	}
	
	/**
	 * 获取输出 json 的数据.
	 * 
	 * @access protected
	 * @return array
	 */
	protected function getAjaxData() {
		return $this->_ajaxData;
	}
	
	/**
	 * 输出 json 数据.
	 *
	 * @access protected
	 * @return void
	 */
	protected function ajaxReturn() {
		$data = $this->_ajaxData;
		$this->_ajaxData = [
			'status' => NULL,
			'msg' => NULL,
			'data' => []
		];
	
		exit(json_encode($data));
	}
	
	/**
	 * 重定向.
	 *
	 * @access protected
	 * @param string $route 路由.
	 * @param array $params 参数, 默认 [].
	 */
	protected function redirect($route, array $params = []) {
		$url = Url::createUrl($route, $params);
		if(!headers_sent()) {
			header('Location: ' . $url);
			exit();
		}
	
		exit('<meta http-equiv="refresh" content="0" url="'. $url .'" />');
	}
	
	/**
	 * 字段映射.
	 * 规则格式:
	 * ['表单字段名' => '数据表字段名']
	 *
	 * @param array $data 数据(一维), 传址.
	 * @param array $rules 映射规则.
	 * @return void
	 */
	protected function runAutoMap(array & $data, array $rules) {
		$keys = array_keys($data);
		foreach($keys as & $field) {
			if(isset($rules[$field])) {
				$field = $rules[$field];
			}
		}
	
		unset($field);
		$data = array_combine($keys, array_values($data));
	}
	
	/**
	 * 表单验证.
	 *
	 * @access protected
	 * @return \wuyuan\base\Validator
	 */
	protected function validator() {
		static $validator = NULL;
		if(NULL === $validator) {
			$validator = new Validator();
		}
	
		return $validator;
	}
	
	/**
	 * 视图对象.
	 *
	 * @access public
	 * @return \wuyuan\base\View
	 */
	protected function view() {
		static $view = NULL;
		if(NULL === $view) {
			$configs = Config::get('wuyuan\base\View');
			$view = new View();
			$view->leftDs = $configs['leftDs'];
			$view->rightDs = $configs['rightDs'];
			$view->tplSuffix = $configs['tplSuffix'];
			$view->style = $configs['style'];
			$view->tplDir = $configs['tplDir'];
			$view->compileDir = $configs['compileDir'];
			unset($configs);
		}
	
		return $view;
	}
}
