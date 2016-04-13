<?php

namespace wuyuan\base;

use wuyuan\wy;

/**
 * wuyuan 视图类.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class View {
	
	/**
	 * 模板数据.
	 *
	 * @var array
	 */
	private $_tplData = [];
	
	/**
	 * 临时样式(主题)目录名.
	 * 为 FALSE 表示禁用, 此值通过 theme 方法设置.
	 * 
	 * @var boolean|string
	 */
	private $_theme = FALSE;
	
	/**
	 * 左定界符.
	 * 
	 * @var string
	 */
	public $leftDs = '{';
	
	/**
	 * 右定界符.
	 * 
	 * @var string
	 */
	public $rightDs = '}';
	
	/**
	 * 样式(主题)目录名.
	 * 
	 * @var string
	 */
	public $style = 'default';
	
	/**
	 * 模板文件后缀名.
	 * 
	 * @var string
	 */
	public $tplSuffix = '.html';
	
	/**
	 * 模板文件存放目录路径.
	 * 
	 * @var string
	 */
	public $tplDir = '';
	
	/**
	 * 模板编译文件存放目录路径.
	 * 
	 * @var string
	 */
	public $compileDir = '';
	
	/**
	 * 解析模板标签.
	 *
	 * @access private
	 * @param string $tplFile 模板文件路径.
	 * @param string $compileFile 模板编译文件路径.
	 * @return void
	*/
	private function parseTpl($tplFile, $compileFile) {
		if(!is_file($tplFile)) {
			throw new FileException('模板文件(' . $tplFile . ')不存在.', FileException::NOT_FOUND);
		}
	
		$compileDir = dirname($compileFile);
		if(!is_dir($compileDir) && FALSE === mkdir($compileDir, 0777, TRUE)) {
			throw new FileException('创建模板编译目录(' . $compileDir . ')时出错.', FileException::CREATE_FAILD);
		}
	
		// 模板文件比编译文件新, 重新生成模板编译文件; 否则不生成.
		if(is_file($compileFile) && filemtime($compileFile) > filemtime($tplFile)) {
			return ;
		}
	
		// 转义边界符.
		$leftDs = preg_quote($this->leftDs);
		$rightDs = preg_quote($this->rightDs);
	
		// 所有标签语法正则.
		$tagPattern = [
			// 变量.
			'/' . $leftDs . '(\$\w+)' . $rightDs . '/ims',
			// 数组.
			'/' . $leftDs . '(\$\w+(?:\[["\']?\$?\w+["\']?\])+)' . $rightDs . '/ims',
			// 常量.
			'/' . $leftDs . 'const\.(\w+)' . $rightDs . '/ims',
			// if.
			'/' . $leftDs . 'if\s+(.+?)' . $rightDs . '/ims',
			// else.
			'/' . $leftDs . 'else' . $rightDs . '/ims',
			// elseif.
			'/' . $leftDs . 'elseif\s+(.+?)' . $rightDs . '/ims',
			// endif.
			'/' . $leftDs . 'endif' . $rightDs . '/ims',
			// loop : foreach($var as $v).
			'/' . $leftDs . 'loop\s+(\$\w+(?:\[.+?\])*)\s+(\$\w+)' . $rightDs . '/ims',
			// loop : foreach($var as $k => $v).
			'/' . $leftDs . 'loop\s+(\$\w+(?:\[.+?\])*)\s+(\$\w+)\s+(\$\w+)' . $rightDs . '/ims',
			// endloop.
			'/' . $leftDs . 'endloop' . $rightDs . '/ims',
			// include.
			'/' . $leftDs . 'include\s+["\'](.+?)["\']' . $rightDs . '/ims',
			// php.
			'/' . $leftDs . 'php' . $rightDs . '\s*(.*?)\s*' . $leftDs . '\/php' . $rightDs . '/ims',
			// 三元运算符.
			'/' . $leftDs . 'IIF\((.+?),\s+(.+?),\s+(.+?)\)' . $rightDs . '/ims',
			// 静态方法或函数.
			'/' . $leftDs . '((?:(?:[\\\]?\w+)+::)?\w+\((.*?)\))' . $rightDs . '/ims',
			// 对象属性或方法.
			'/' . $leftDs . '(\$\w+(?:\[.+?\])*->\w+(?:\(.*?\))?(?:(?:\[["\']?\$?\w+["\']?\])+)?)' . $rightDs . '/ims',
		];
	
		// 标签替换成的相应的内容语法.
		$tagReplace = [
			// 变量.
			'<?php echo isset($1) ? $1 : NULL; ?>',
			// 数组.
			'<?php echo isset($1) ? $1 : NULL; ?>',
			// 常量.
			'<?php echo defined("$1") ? $1 : NULL; ?>',
			// if.
			'<?php if($1) { ?>',
			// else.
			'<?php } else { ?>',
			// elseif.
			'<?php } elseif($1) { ?>',
			// endif.
			'<?php } ?>',
			// loop : foreach($var as $v).
			'<?php $1 = isset($1) && is_array($1) ? $1 : []; foreach($1 as $2) { ?>',
			// loop : foreach($var as $k => $v).
			'<?php $1 = isset($1) && is_array($1) ? $1 : []; foreach($1 as $2 => $3) { ?>',
			// endloop.
			'<?php } ?>',
			// include.
			'<?php $this->includeTpl("$1"); ?>',
			// php.
			'<?php ' . PHP_EOL . '$1' . PHP_EOL . '?>',
			// 三元运算符.
			'<?php echo $1 ? $2 : $3; ?>',
			// 静态方法或函数.
			'<?php echo $1; ?>',
			// 对象属性或方法.
			'<?php echo $1; ?>',
		];
	
		// 读取模板文件内容.
		$tplContent = file_get_contents($tplFile);
		if(FALSE === $tplContent) {
			throw new FileException('读取模板文件(' . $tplFile . ')时出错.', FileException::READ_FAILD);
		}
	
		// 正则替换模板标签.
		$tplContent = preg_replace($tagPattern, $tagReplace, $tplContent);
		if(FALSE === file_put_contents($compileFile, $tplContent)) {
			throw new FileException('写入模板编译文件(' . $compileFile . ')时出错.', FileException::WRITE_FAILD);
		}
	}
	
	/**
	 * 执行 include 标签.
	 * $tplName 格式参见 display 方法说明.
	 *
	 * @access private
	 * @param string $tplName 模板名称.
	 * @return void
	 */
	private function includeTpl($tplName) {
		$tplPath = $this->fetchViewPath($tplName);
		$this->parseTpl($tplPath['tpl'], $tplPath['compile']);
		extract($this->_tplData);
		require $tplPath['compile'];
	}
	
	/**
	 * 获取模板文件路径.
	 * $tplName 格式参见 display 方法说明.
	 *
	 * @access public
	 * @param string $tplName 模板名称, 默认 NULL.
	 * @return array ['tpl' => 模板文件路径, 'compile' => 模板编译文件路径]
	 */
	protected function fetchViewPath($tplName = NULL) {
		$isGroup = Url::isGroup();
		$groupName = Url::groupName();
		$controllerName = Url::controllerName();
		$actionName = Url::actionName();
		$style = ''; // 主题名称.
		// 临时主题目录名, 只能用一次 theme 方法, 但 theme 方法设置主题名的优先级低于冒号格式的设置.
		if(FALSE !== $this->_theme) {
			$style = $this->_theme;
			$this->_theme = FALSE;
		}
		
		// 判断 $tplName 已指定 主题名 的情况.
		$pos = NULL === $tplName ? FALSE : strpos($tplName, ':');
		if(FALSE !== $pos) {
			list($style, $tplName) = explode(':', $tplName);
		}
		
		$style = empty($style) ? $this->style : $style;
		$style = empty($style) ? '' : $style . '/';
		$result['tpl'] = $this->tplDir . $style;
		$result['compile'] = $this->compileDir . $style;
		$fullTpl = '';
		// 解析模板名称.
		if(NULL === $tplName || empty($tplName)) {
			$fullTpl = ($isGroup ? $groupName . '/' : '') . $controllerName . '/' . $actionName;
		} else {
			$arrTpl = explode('/', $tplName);
			$tplLen = count($arrTpl);
			$fullTpl = $tplName;
			if($isGroup && 1 === $tplLen) {
				$fullTpl = $groupName . '/' . $controllerName . '/' . $fullTpl;
			} elseif($isGroup && 2 === $tplLen) {
				$fullTpl = $groupName . '/' . $fullTpl;
			} elseif(1 === $tplLen) {
				$fullTpl = $controllerName  . '/' . $fullTpl;
			} elseif($tplLen > ($isGroup ? 3 : 2)) {
				$arrTpl = array_slice($arrTpl, 0, ($isGroup ? 3 : 2));
				$fullTpl = implode('/', $arrTpl);
			}
		}
	
		$pos = strrpos($fullTpl, '/');
		$oriTplName = substr($fullTpl, $pos + 1); // 模板文件名.
		$oriTplPath = substr($fullTpl, 0, $pos); // 相对路径.
		$result['tpl'] .= $fullTpl . $this->tplSuffix;
		$result['compile'] .= $oriTplPath . '/' . md5($oriTplName) . '_' . $oriTplName . '.php';
		return $result;
	}
	
	/**
	 * 设置临时样式(主题)名.
	 * 
	 * @access public
	 * @param string $name 样式(主题)目录名.
	 * @return \wuyuan\base\View
	 */
	public function theme($name) {
		if(is_string($name)) {
			$this->_theme = $name;
		}
		
		return $this;
	}
	
	/**
	 * 添加模板数据.
	 *
	 * @access public
	 * @param string|array $name 变量名, array 为批量添加.
	 * @param mixed $value 变量值, 默认 NULL.
	 * @return \wuyuan\base\View
	 */
	public function assign($name, $value = NULL) {
		if(is_array($name)) {
			foreach($name as $k => $v) {
				$this->_tplData[$k] = $v;
			}
		} else {
			$this->_tplData[$name] = $value;
		}
	
		return $this;
	}
	
	/**
	 * 显示模板.
	 * $tplName 格式: 主题名:分组名/控制器名/动作名.
	 * $tplName 为 NULL, 会根据请求的路径解析; 如 $tplName 指定 index, 会自动增加控制器名; 如 Index/index,
	 * 在开启分组时, 会自动增加分组名.
	 *
	 * @access public
	 * @param string $tplName 模板名称, 默认 NULL.
	 * @return void
	 */
	public function display($tplName = NULL) {
		$tplPath = $this->fetchViewPath($tplName);
		$this->parseTpl($tplPath['tpl'], $tplPath['compile']);
		extract($this->_tplData);
		require $tplPath['compile'];
	}
	
	/**
	 * 显示模板并指添加模板数据.
	 * $tplName 格式参见 display 方法说明.
	 * 
	 * @access public
	 * @param string $tplName 模板名称, 默认 NULL.
	 * @param array $tplData 模板数据.
	 * @return void
	 */
	public function render($tplName = NULL, array $tplData = []) {
		$this->assign($tplData)->display($tplName);
	}
	
	/**
	 * 获取模板文件内容.
	 * $tplName 格式参见 display 方法说明.
	 * 
	 * @access public
	 * @param string $tplName 模板名称, 默认 NULL.
	 * @return string 返回模板最终的 html 内容.
	 */
	public function fetch($tplName = NULL) {
		ob_start();
		$this->display($tplName);
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}
	
	/**
	 * 清除模板编译文件.
	 * $tplName 为 NULL, 清除全部模板编译文件; $tplName 格式参见 display 方法说明.
	 *
	 * @access public
	 * @param string $tplName 模板名称, 默认 NULL.
	 * @return boolean 清除成功返回 TRUE; 否则返回 FALSE.
	 */
	public function cleanCompile($tplName = NULL) {
		if(NULL === $tplName) {
			return wy::removeAllFile($this->configs['compileDir'], TRUE);
		}
	
		return unlink($this->fetchViewPath($tplName)['compile']);
	}
	
}
