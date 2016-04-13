<?php

namespace wuyuan\page;

use wuyuan\base\Config;

/**
 * wuyuan 分页类.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class Page {
	
	/**
	 * 当前页.
	 * 
	 * @var integer
	 */
	private $_page = 1;
	
	/**
	 * 总记录数.
	 * 
	 * @var integer
	 */
	private $_totalRows = 0;
	
	/**
	 * 每页显示的条数.
	 * 
	 * @var integer
	 */
	private $_pageSize = 10;
	
	/**
	 * 数字链接显示的个数.
	 * 
	 * @var integer
	 */
	private $_pageRoll = 9;
	
	/**
	 * 总页数.
	 * 
	 * @var integer
	 */
	private $_pageCount = 0;
	
	/**
	 * 分页 URL 需要带的额外参数($_GET 中的参数会自动带入).
	 * 
	 * @var array
	 */
	private $_params = [];
	
	/**
	 * 分页参数名.
	 * 
	 * @var string
	 */
	private $_varPage = 'p';
	
	/**
	 * 分页大小参数名
	 * 
	 * @var string
	 */
	private $_varPageSize = 'rows';
	
	/**
	 * 分页的基本 URL, 建议使用 Url::createUrl 生成.
	 * 没用设置使用 $_SERVER['REQUEST_URI'] 的值.
	 * 
	 * @var string
	 */
	private $_url = '';
	
	/**
	 * 配置项.
	 * 
	 * @var array
	 */
	protected $_configs = [
		'header' => '<span class="header">共 %TOTAL_ROWS% 条记录</span>',
		'footer' => '<span class="footer">第 %PAGE% 页 / 共 %PAGE_COUNT% 页</span>',
		'first' => '|<',
		'last' => '>|',
		'prev' => '<<',
		'next' => '>>',
		// 数字链接第 1 个, fixed 固定显示.
		'numFirst' => ['text' => '1...', 'fixed' => FALSE],
		// 数字链接最后 1 个, fixed 同上.
		'numLast' => ['text' => '...%PAGE_COUNT%', 'fixed' => FALSE],
		// 显示设置每页显示数量的下拉选择框.
		'showPageSize' => FALSE,
		'pageSizeTpl' => '<span class="pagesize-list">每页显示: %LIST_PAGE_SIZE% 条</span>',
		'pageSizeList' => [10, 20, 30, 40, 50],
		// 选择每页显示数量时, 是否保留在当前页, 否则都从第 1 页显示.
		'keepPage' => TRUE,
		'showPageList' => FALSE,
		'pageListTpl' => '<span class="page-list">跳转到: %LIST_PAGE% 页</span>',
		// 显示输入页码跳转到指定页.
		'showGoPage' => TRUE,
		// 输入页码跳转到指定页模板.
		'goPageTpl' => '<script>function goPage(page) {var url = window.location.href;var reg = /%VAR_PAGE%=\d+/;if(!reg.test(url)){url += \'?%VAR_PAGE%=%PAGE%\';} if(!/\d+/.test(page)){alert(\'请输入正确的页码!\');return false;} url = url.replace(reg, \'%VAR_PAGE%\' + \'=\' + page);window.location=url;}
</script><label class="label-go-page">跳转到: <input class="input-page" value="%PAGE%" onkeyup="var e = window.event || event;if(e.keyCode == 13) {goPage(this.value);}" /><input class="btn-go-page" type="button" value="GO" onclick="goPage(this.previousSibling.value)" /></label>',
		// 分页显示模板.
		'theme' => '%HEADER% %FIRST% %PREV% %NUM_LINK% %NEXT% %LAST% %FOOTER% %PAGE_LIST% %PAGE_SIZE_LIST% %GO_PAGE%'
	];
	
	/**
	 * 获取链接地址.
	 * 
	 * @access public
	 * @param integer $page 当前分页.
	 * @param integer $pageSize 每页显示数量, 默认 NULL 表示使用内部的 pageSize.
	 * @return string
	 */
	public function getUrl($page, $pageSize = NULL) {
		$url = empty($this->_url) ? $_SERVER['REQUEST_URI'] : $this->_url;
		$urlInfo = parse_url($url);
		$base = $urlInfo['path'];
		$params = [];
		if(isset($urlInfo['query'])) {
			parse_str($urlInfo['query'], $params);
		}
		// 将用户设置的额外参数合并到已有的参数中, 有同名的使用用户设置的覆盖已有的值.
		$params = array_merge($params, $this->_params);
		$params[$this->_varPage] = $page;
		if(NULL === $pageSize) {
			$pageSize = $this->_pageSize;
		}
		if($this->_configs['showPageSize']) {
			$params[$this->_varPageSize] = $pageSize;
		}
		
		return $base . '?' . http_build_query($params);
	}
	
	/**
	 * 生成首页.
	 * 
	 * @access protected
	 * @param integer $page 当前分页.
	 * @return string
	 */
	protected function makeFirst($page) {
		if($page <= 1) {
			return '<span class="page-nav nav-first">'. $this->_configs['first'] .'</span>';
		}
		
		return '<a href="'. $this->getUrl(1) .'" class="page-nav">'. $this->_configs['first'] .'</a>';
	}
	
	/**
	 * 生成尾页.
	 * 
	 * @access protected
	 * @param integer $page 当前分页.
	 * @param integer $pageCount 总页数.
	 * @return string
	 */
	protected function makeLast($page, $pageCount) {
		if($page >= $pageCount) {
			return '<span class="page-nav nav-last">'. $this->_configs['last'] .'</span>'; 
		}
		
		return '<a href="'. $this->getUrl($pageCount) .'" class="page-nav">'. $this->_configs['last'] .'</a>';
	}
	
	/**
	 * 生成上一页.
	 * 
	 * @access protected
	 * @param integer $page 当前分页.
	 * @return string
	 */
	protected function makePrev($page) {
		if($page <= 1) {
			return '<span class="page-nav nav-prev">'. $this->_configs['prev'] .'</span>';
		}
		
		return '<a href="'. $this->getUrl(--$page) .'" class="page-nav">'. $this->_configs['prev'] .'</a>';
	}
	
	/**
	 * 生成下一页.
	 * 
	 * @access protected
	 * @param integer $page 当前分页.
	 * @param integer $pageCount 总页数.
	 * @return string
	 */
	protected function makeNext($page, $pageCount) {
		if($page >= $pageCount) {
			return '<span class="page-nav nav-next">'. $this->_configs['next'] .'</span>';
		}
		
		return '<a href="'. $this->getUrl(++$page) .'" class="page-nav">'. $this->_configs['next'] .'</a>';
	}
	
	/**
	 * 生成数字链接.
	 * 
	 * @access protected
	 * @param integer $page 当前分页.
	 * @param integer $pageCount 总页数.
	 * @param integer $pageRoll 显示的数字链接个数.
	 * @return string
	 */
	protected function makeNumLinks($page, $pageCount, $pageRoll) {
		// 显示的数字链接个数大于总页数.
		if($pageRoll >= $pageCount) {
			$pageRoll = $pageCount;
		}
		
		$ceilRoll = ceil($pageRoll / 2); // 当前页在数字链接中居中显示的临界点.
		// 固定显示数字链接中的第 1 个.
		$strFirst = '';
		if($this->_configs['numFirst']['fixed'] && $pageCount > 2) {
			if($page <= 1) {
				$strFirst = '<span class="num current">'. $this->_configs['numFirst']['text'] .'</span>';
			} else {
				$strFirst = '<a href="'. $this->getUrl(1) .'" class="num">'. $this->_configs['numFirst']['text'] .'</a>';
			}
		}
		
		// 固定显示数字链接中的最后 1 个.
		$strLast = '';
		if($this->_configs['numLast']['fixed'] && $pageCount > 2) {
			if($page >= $pageCount) {
				$strLast = '<span class="num current">'. $this->_configs['numLast']['text'] .'</span>';
			} else {
				$strLast = '<a href="'. $this->getUrl($pageCount) .'" class="num">'. $this->_configs['numLast']['text'] .'</a>';
			}
		}
		
		// 数字链接主体部分.
		$strHtml = '';
		for($i = 1; $i <= $pageRoll; ++$i) {
			$p = 1; // 临时当前分页, 用于计算分页 url.
			if($page < $ceilRoll) { // 固定小于 $ceilRoll 的页码.
				$p = $i;
			} elseif($page >= $ceilRoll && ($page + $ceilRoll) <= $pageCount) {
				$p = $page - $ceilRoll + $i;
			} elseif($page <= $pageCount) {
				$p = $pageCount - $pageRoll + $i;
			} else {
				break;
			}
			
			// 固定首页.
			if($this->_configs['numFirst']['fixed']) {
				if(1 == $p) { // 是第 1 页.
					continue;
				} elseif($page <= ($pageCount - $ceilRoll) && $p == ($page - $ceilRoll + 1)) { // 当前页未超过总页数减临界点的页码.
					continue;
				} elseif($page > ($pageCount - $ceilRoll) && $p == ($pageCount - $pageRoll + 1)) { // 当前页已超过总页数减临界点的页码.
					continue;
				}
			}
			// 固定尾页.
			if($this->_configs['numLast']['fixed']) {
				if($p == $pageCount) { // 是最后 1 页.
					break;
				} elseif($page < $ceilRoll && $p == $pageRoll) { // 当前页小于临界值且临时页码等最 $pageRoll.
					break;
				} elseif($page >= $ceilRoll && $p == ($page + $ceilRoll - 1)) { // 当前页大于临界点.
					break;
				}
			}
			
			// 计算出的页码大于总页数.
			if($p > $pageCount) {
				break;
			}
			
			// 拼接数字链接.
			if($page == $p) {
				$strHtml .= '<span class="num current">'. $p .'</span>';
			} else {
				$strHtml .= '<a href="'. $this->getUrl($p) .'" class="num">'. $p .'</a>';
			}
		}
		
		return $strFirst . $strHtml . $strLast;
	}
	
	/**
	 * 生成分页尺寸大小列表.
	 * 
	 * @access protected
	 * @param integer $pageSize 当前的分页尺寸.
	 * @return string
	 */
	protected function makePageSizeList($pageSize) {
		if(!$this->_configs['showPageSize']) {
			return '';
		}
		
		$page = 1; // 选择尺寸时, 默认在第 1 页.
		if($this->_configs['keepPage']) {
			$page = $this->_page; // 开启了保留在当前分页.
		}
		
		$strHtml = '<select class="select-pagesize" ';
		$strHtml .= 'onchange="window.location=this.value;">';
		foreach($this->_configs['pageSizeList'] as $size) {
			$selected = '';
			if($size == $this->_pageSize) {
				$selected = 'selected="selected"';
			}
			
			$strHtml .= '<option value="'. $this->getUrl($page, $size) .'" '. $selected .'>'. $size .'</option>';
		}
		
		$strHtml .= '</select>';
		return $strHtml;
	}

	/**
	 * 生成页码列表.
	 * 
	 * @access protected
	 * @param integer $page 当前页.
	 * @param integer $pageCount 总页数.
	 * @return string
	 */
	protected function makePageList($page, $pageCount) {
		if(!$this->_configs['showPageList']) {
			return '';
		}
		
		if($page <= 1) {
			$page = 1;
		} elseif($page >= $pageCount) {
			$page = $pageCount;
		}
		
		$strHtml = '<select class="selectPageList" ';
		$strHtml .= 'onchange="window.location=this.value;">';
		for($i = 1; $i <= $pageCount; ++$i) {
			$selected = '';
			if($i == $page) {
				$selected = 'selected="selected"';
			}
			
			$strHtml .= '<option value="'. $this->getUrl($i) .'" '. $selected .'>'. $i .'</option>';
		}
		
		$strHtml .= '</select>';
		return $strHtml;
	}
	
	/**
	 * 设置分页的基本 URL.
	 * 
	 * @access public
	 * @param string $url 基本的 URL, 建议使用 Url::createUrl 生成.
	 * @return \wuyuan\page\Page
	 */
	public function setUrl($url) {
		$this->_url = $url;
		return $this;
	}
	
	/**
	 * 设置分页需要带的额外参数.
	 * $_GET 中的参数会自动带入.
	 * 
	 * @access public
	 * @param array $params [参数名=>参数值].
	 * @return \wuyuan\page\Page
	 */
	public function setParams(array $params) {
		if(!empty($params)) {
			$this->_params = $params;
		}
		return $this;
	}
	
	/**
	 * 设置分页参数名.
	 * 
	 * @access public
	 * @param string $varPage
	 * @return \wuyuan\page\Page
	 */
	public function setVarPage($varPage) {
		$this->_varPage = $varPage;
		if(isset($_GET[$varPage])) {
			$page = (integer)$_GET[$varPage];
			$this->_page = $page < 1 ? 1 : $page;
		}
		return $this;
	}
	
	/**
	 * 设置分页大小参数名.
	 * 
	 * @access public
	 * @param string $varPageSize
	 * @return \wuyuan\page\Page
	 */
	public function setVarPageSize($varPageSize) {
		if(!$this->_configs['showPageSize']) {
			return $this;
		}
		
		$this->_varPageSize = $varPageSize;
		if(isset($_GET[$varPageSize])) {
			$size = (integer)$_GET[$varPageSize];
			$this->_pageSize = $size < 1 ? $this->_pageSize : $size;
		}
		return $this;
	}
	
	/**
	 * 设置数字链接显示个数.
	 * 
	 * @access public
	 * @param integer $num
	 * @return \wuyuan\page\Page
	 */
	public function setPageRoll($num) {
		$this->_pageRoll = $num;
		return $this;
	}
	
	/**
	 * 设置当前页.
	 * 正常情况下不用设置, 若在 get 参数中页码大于总页数, 希望显示最后一页的数据时, 可以设置为总页数. 
	 * 
	 * @access public
	 * @param integer $page
	 * @return \wuyuan\page\Page
	 */
	public function setCurrPage($page) {
		$this->_page = $page;
		return $this;
	}
	
	/**
	 * 获取当前页码.
	 * 
	 * @access public
	 * @return integer
	 */
	public function getCurrPage() {
		return $this->_page;
	}
	
	/**
	 * 获取页码参数名.
	 * 
	 * @access public
	 * @return string
	 */
	public function getVarPage() {
		return $this->_varPage;
	}
	
	/**
	 * 获取数据链接显示个数.
	 * 
	 * @access public
	 * @return integer
	 */
	public function getPageRoll() {
		return $this->_pageRoll;
	}
	
	/**
	 * 获取总页数.
	 * 
	 * @access public
	 * @return integer
	 */
	public function getPageCount() {
		return $this->_pageCount;
	}
	
	/**
	 * 获取 limit 字符串.
	 * 
	 * @access public
	 * @return string
	 */
	public function getLimit() {
		return ($this->_page - 1) * $this->_pageSize . ',' . $this->_pageSize;
	}
	
	/**
	 * 设置配置项.
	 * 
	 * @access public
	 * @param array $configs 配置项.
	 * @return \wuyuan\page\Page
	 */
	public function setConfig(array $configs) {
		$this->_configs = array_merge($this->_configs, $configs);
		return $this;
	}
	
	/**
	 * 输出分页 HTML.
	 * 
	 * @access public
	 * @return string
	 */
	public function showPage() {
		// 总页数小于 1 页, 不显示分页.
		if($this->_pageCount <= 1) {
			return '';
		}
		
		// 页面尺寸大小列表.
		$strPageSizeList = '';
		if($this->_configs['showPageSize']) {
			$strPageSizeList = str_replace('%LIST_PAGE_SIZE%', 
				$this->makePageSizeList($this->_pageSize), $this->_configs['pageSizeTpl']);
		}
		
		// 页码列表.
		$strPageList = '';
		if($this->_configs['showPageList']) {
			$strPageList = str_replace('%LIST_PAGE%', 
				$this->makePageList($this->_page, $this->_pageCount), $this->_configs['pageListTpl']);
		}
		
		// 输入页码跳转.
		$strGoPage = '';
		if($this->_configs['showGoPage']) {
			$strGoPage = $this->_configs['goPageTpl'];
		}
		
		// 待替换的标记.
		$searches = [
			'%HEADER%', '%FIRST%', '%PREV%', '%NUM_LINK%', '%NEXT%', '%LAST%', '%FOOTER%',
			'%TOTAL_ROWS%', '%PAGE_COUNT%', '%PAGE_SIZE_LIST%', '%PAGE_LIST%', '%GO_PAGE%', '%VAR_PAGE%', '%PAGE%'
		];
		// 替换的值.
		$replaces = [
			$this->_configs['header'],
			$this->makeFirst($this->_page),
			$this->makePrev($this->_page),
			$this->makeNumLinks($this->_page, $this->_pageCount, $this->_pageRoll),
			$this->makeNext($this->_page, $this->_pageCount),
			$this->makeLast($this->_page, $this->_pageCount),
			$this->_configs['footer'],
			$this->_totalRows,
			$this->_pageCount,
			$strPageSizeList,
			$strPageList,
			$strGoPage,
			$this->_varPage,
			$this->_page
		];
		
		return str_replace($searches, $replaces, $this->_configs['theme']);
	}
	
	/**
	 * 构造方法.
	 * 
	 * @access public
	 * @param integer $totalRows 总条数.
	 * @param integer $pageSize 每页显示的条数, 默认 10.
	 * @param array $configs 配置项, 默认 [], 表示使用配置文件中的配置.
	 */
	public function __construct($totalRows, $pageSize = 10, array $configs = []) {
		if(empty($configs)) {
			$configs = Config::get(__CLASS__);
		}
		
		// 合并配置.
		$this->_configs = array_merge($this->_configs, $configs);
		$this->_pageSize = $pageSize;
		if(isset($_GET[$this->_varPage])) {
			$page = (integer)$_GET[$this->_varPage];
			$this->_page = $page < 1 ? 1 : $page;
		}
		if($this->_configs['showPageSize']) {
			$size = isset($_GET[$this->_varPageSize]) ? (integer)$_GET[$this->_varPageSize] : $pageSize;
			$this->_pageSize = $size < 1 ? $pageSize : $size;
			if(!in_array($this->_pageSize, $this->_configs['pageSizeList'])) {
				$this->_pageSize = $this->_configs['pageSizeList'][0];
			}
		}
		
		$this->_totalRows = $totalRows;
		$this->_pageCount = (integer)ceil($totalRows / $this->_pageSize);
	}
	
}
