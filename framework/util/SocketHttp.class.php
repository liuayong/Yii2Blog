<?php

namespace wuyuan\util;

use Exception;

/**
 * SocketHttp 处理类.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class SocketHttp {
	
	/**
	 * GET 请求.
	 * 
	 * @var string
	 */
	const METHOD_GET = 'GET';
	
	/**
	 * POST 请求.
	 * 
	 * @var string
	 */
	const METHOD_POST = 'POST';
	
	/**
	 * 换行符.
	 * 
	 * @var string
	 */
	const CRLF = "\r\n";
	
	/**
	 * 协议.
	 * 
	 * @var string
	 */
	private $_protocol = 'tcp://';
	
	/**
	 * 主机名.
	 * 
	 * @var string
	 */
	private $_hostName = NULL;
	
	/**
	 * 端口号, 默认 80.
	 * 
	 * @var integer
	 */
	private $_port = 80;
	
	/**
	 * 连接超时时间(秒), 默认 30.
	 * 
	 * @var integer
	 */
	private $_connectionTimeout = 30;
	
	/**
	 * 操作超时时间(秒), 默认 30.
	 * 
	 * @var integer
	 */
	private $_handleTimeout = 30;
	
	/**
	 * 请求的 URI, 默认 /.
	 * 
	 * @var string
	 */
	private $_uri = '/';
	
	/**
	 * 已关闭标记.
	 * 
	 * @var boolean
	 */
	private $_closed = FALSE;
	
	/**
	 * Socket 连接资源.
	 * 
	 * @var resource
	 */
	private $_socket = NULL;
	
	/**
	 * 已连接标记.
	 * 
	 * @var boolean
	 */
	private $_connected = FALSE;
	
	/**
	 * 阻塞模式标记.
	 * 
	 * @var boolean
	 */
	private $_isBlocking = TRUE;
	
	/**
	 * 获取请求头信息.
	 * 
	 * @var string
	 */
	private $_requestHeader = NULL;
	
	/**
	 * COOKIE.
	 * 
	 * @var array
	 */
	private $_cookies = [];
	
	/**
	 * 请求头信息.
	 * 
	 * @var array
	 */
	private $_headers = [
		'Connection' => 'Close', // 这个是异步的关闭.
	];
	
	/**
	 * 原始响应信息.
	 * 
	 * @var string
	 */
	private $_responses = NULL;
	
	/**
	 * 解析后的响应信息.
	 * 
	 * @var array
	 */
	private $_parsedResponse = [];
	
	/**
	 * 生成请求头.
	 * 
	 * @access private
	 * @param string $method 请求类型.
	 * @param string $uri 请求的 URI.
	 * @param array $param 请求的参数.
	 * @return string
	 */
	private function _makeHeader($method, $uri, array $param) {
		// 先解析 URI.
		$uriInfo = parse_url($this->_uri); // 解析 url.
		$uriInfo['query'] = !isset($uriInfo['query']) ? '' : $uriInfo['query'];
		$strParam = http_build_query($param); // 请求的参数内容.
		// 过滤请求类型.
		$method = strtoupper($method);
		$strHederFirstLine = ''; // 请求行部分.
		$strContent = ''; // 请求的内容部分.
		switch($method) {
			case self::METHOD_POST:
				$strHederFirstLine = $method . ' ' . $uri . ' HTTP/1.1'; // POST 请求头行.
				$this->_headers['Content-Length'] = strlen($strParam);
				$this->_headers['Content-Type'] = 'application/x-www-form-urlencoded';
				$strContent = $strParam; // POST 请求的内容.
				break;
			default: // 默认 GET 请求.
				$method = self::METHOD_GET;
				$uriBase = empty($uriInfo['path']) ? '/' : $uriInfo['path']; // uri 基础部分.
				if(empty($uriInfo['query'])) {
					$uriInfo['query'] = empty($strParam) ? '' : $strParam;
				} else {
					$uriInfo['query'] = $uriInfo['query'] . (empty($strParam) ? '' : '&' . $strParam);
				}
				
				$uriBase .= empty($uriInfo['query']) ? '' : '&' . $uriInfo['query'];
				$strHederFirstLine = $method . ' ' . $uriBase . ' HTTP/1.1'; // GET 请求行.
		}

		// 拼装格式化的请求头.
		$strHeader = $strHederFirstLine . self::CRLF; // 请求头.
		$strHeader .= 'Host: ' . $this->_hostName . self::CRLF;  // 主机名.
		unset($strHederFirstLine, $uriInfo, $uriBase, $strParam); // 删除临时变量.
		// 生成 COOKIE.
		if(!empty($this->_cookies)) {
			$strCookie = '';
			foreach($this->_cookies as $k => $v) {
				$strCookie .= (empty($strCookie) ? '' : '; ') . $k . '=' . $v;
			}
			
			$strHeader .= 'Cookie: ' . $strCookie . self::CRLF; // COOKIE.
			unset($strCookie);
		}
		
		// 生成其它 header.
		if(!empty($this->_headers)) {
			$strOtherHeader = '';
			foreach($this->_headers as $k => $v) {
				$strOtherHeader .= $k . ': ' . $v . self::CRLF;
			}
			
			$strHeader .= $strOtherHeader;
			unset($strOtherHeader);
		}
		
		// 生成请求头结束.
		$strHeader .= self::CRLF;
		$strHeader .= $strContent; // 请求正文.
		return $strHeader;
	}
	
	/**
	 * 解析 chunked 分块内容.
	 * 
	 * @access private
	 * @param string $chunkedContent 分块内容.
	 * @return string 不是分块, 原始内容.
	 */
	private function _decodeChunked($chunkedContent) {
		if(function_exists('http_chunked_decode')) {
			$res = http_chunked_decode($chunkedContent);
			return FALSE === $res ? $chunkedContent : $res;
		}
		
		/*
		 * 自己理解的 chunked 的格式为: 
		 * 					第 1 段: 16进制大小字符\r\n
		 * 							正文内容\r\n
		 * 					第 2 段: 16进制大小字符\r\n
		 * 							正文内容\r\n
		 * 					结束:	0\r\n
		 */
		$pos = 0;
		$len = strlen($chunkedContent);
		$decode = '';
		while($pos < $len) {
			$offset = strpos($chunkedContent, "\n", $pos) - $pos; // 内容长度的 16 进制字符长度.
			$hex = substr($chunkedContent, $pos, $offset + 1); // 表示内容长度的 16 进制字符, 包含 \r\n.
			$len_hex = hexdec(rtrim(ltrim($hex, "0"), "\r\n")) + 2; // 内容长度, 转换为整数字节数.
			if(empty($len_hex)) { // 为空, 表示 chunked 已完成, 取到 0 时才会中止.
				break;
			}
			
			$decode .= rtrim(substr($chunkedContent, $pos + $offset + 1, $len_hex), "\r\n"); // 拼接内容.
			$pos += $offset + $len_hex + 1;
		}
		
		return $decode;
	}
	
	/**
	 * 校验是否为有效的十六进制数字符串.
	 * 
	 * @access private
	 * @param string $hex 字符串.
	 * @return boolean 是十六进制返回 TRUE, 不是返回 FALSE.
	 */
	private function _isHex($hex) {
		$hex = strtolower(trim(ltrim($hex, "0")));
		if(empty($hex)) {
			$hex = 0;
		}
		
		$dec = hexdec($hex);
		return (dechex($dec) == $hex);
	}
	
	/**
	 * 解析响应信息.
	 * 
	 * @access private
	 * @return array 返回包含 code(HTTP 代码), header(响应头), body(响应内容), isChunked(是否分块), encoding(压缩方式).
	 */
	private function _parseResponse() {
		$return = ['code' => 0, 'header' => [], 'body' => '', 'isChunked' => FALSE, 'encoding' => ''];
		if(!empty($this->_responses)) {
			$pos = strpos($this->_responses, self::CRLF . self::CRLF);
			if(FALSE === $pos) {
				return $return;
			}
			
			$resHeader = explode(self::CRLF, substr($this->_responses, 0, $pos));
			foreach($resHeader as $row) {
				$tmp = explode(':', $row);
				if(isset($tmp[1])) {
					if(FALSE !== stripos($tmp[0], 'cookie')) { // 处理 Set-Cookie.
						$return['header'][$tmp[0]][] = trim($tmp[1]);
					} else {
						$return['header'][$tmp[0]] = trim($tmp[1]);
					}
				} else {
					$return['header'][] = $row;
					if(preg_match('/\d{3}/', $row, $matches)) {
						$return['code'] = (integer)$matches[0];
					}
				}
			}
			
			// 是否分块.
			if(isset($return['header']['Transfer-Encoding'])) {
				$return['isChunked'] = stripos($return['header']['Transfer-Encoding'], 'chunked') !== FALSE ? TRUE : FALSE;
			}
			// 压缩方式.
			if(isset($return['header']['Content-Encoding'])) {
				$return['encoding'] = trim($return['header']['Content-Encoding']);
			}
			
			$return['body'] = substr($this->_responses, $pos + strlen(self::CRLF.self::CRLF));
		}
		
		return $return;
	}
	
	/**
	 * 打开连接.
	 * 
	 * @access public
	 * @return \wuyuan\util\SocketHttp 出错时抛异常.
	 */
	public function open() {
		$this->_socket = fsockopen($this->_protocol . $this->_hostName, $this->_port, $errno, $error, $this->_connectionTimeout);
		if(FALSE === $this->_socket) {
			$this->_socket = NULL;
			throw new Exception($error, $errno);
		}
		
		$this->_connected = TRUE; // 已成功连接.
		return $this;
	}
	
	/**
	 * 执行请求.
	 * 
	 * @access public
	 * @param string $method 请求类型, 默认 self::METHOD_GET, METHOD_POST.
	 * @param array $param 请求参数.
	 * @param boolean $isOutput 输出返回值, 默认 FALSE, 设置 TRUE 时调用  .
	 * @return \wuyuan\util\SocketHttp 出错时抛异常.
	 */
	public function execute($method = self::METHOD_GET, array $param = [], $isOutput = FALSE) {
		set_time_limit(0);
		if(!$this->_connected) {
			throw new Exception('未建立连接.');
		}
		
		$this->_requestHeader = $strOut = $this->_makeHeader($method, $this->_uri, $param);
		
		// 设置阻塞或非阻塞模式.
		stream_set_blocking($this->_socket, ($this->_isBlocking ? 1 : 0)); // 0 表示非阻塞, 1: 表示阻塞.
		// 设置请求和获取操作的超时时间.
		stream_set_timeout($this->_socket, $this->_handleTimeout);
		// 执行请求.
		fwrite($this->_socket, $strOut);
		
		// 输出返回值.
		if($isOutput) {
			$strResponse = '';
			while(!feof($this->_socket)) {
				$tmp = fgets($this->_socket, 128);
				$strResponse .= $tmp === FALSE ? '' : $tmp;
			}
			
			if(empty($strResponse)) {
				throw new Exception('获取请求响应数据时出错.');
			}

			$this->_responses = $strResponse;
			$this->_parsedResponse = $this->_parseResponse();
		}
		
		return $this;
	}
	
	/**
	 * 直接输出响应头.
	 * 
	 * @access public
	 * @return void
	 */
	public function printResponse() {
		$content = $this->_parsedResponse['body'];
		// 解码 chunked.
		if($this->_parsedResponse['isChunked']) {
			$content = $this->_decodeChunked($content);
		}
		
		$content = $this->_unCompress($content); // 解压缩, 用了 gzinflage, gzdecode.
		// 输出响应头的 Content-Type.
		if(isset($this->_parsedResponse['header']['Content-Type'])) {
			header('Content-Type: ' . $this->_parsedResponse['header']['Content-Type']);
		}
		
		echo $content;
	}
	
	/**
	 * 解压缩响应内容.
	 * 
	 * @access private
	 * @param string $content 响应正文.
	 * @return string
	 */
	private function _unCompress($content) {
		$method = strtolower($this->_parsedResponse['encoding']);
		switch($method) {
			case 'gzip':
				if(function_exists('gzdecode')) {
					return gzdecode($content);
				} else {
					// gzip 与 deflate 的区别是, gzip 在 deflate 的基础上前面加了 10 个字节, 后面加了 8 个字节.
					return gzinflate(substr($content, 10, -8));
				}
				break;
			case 'deflate':
				return gzinflate($content);
				break;
			default: // 无压缩方式.
				return $content;
		}
	}
	
	/**
	 * 设置成非阻塞模式.
	 * 
	 * @access public
	 * @return \wuyuan\util\SocketHttp
	 */
	public function setBlocking() {
		$this->_isBlocking = FALSE;
		return $this;
	}
	
	/**
	 * 设置操作超时时间(秒).
	 * 
	 * @access public
	 * @param integer $time
	 * @return \wuyuan\util\SocketHttp
	 */
	public function setHandleTimeout($time) {
		$this->_handleTimeout = (integer)$time;
		return $this;
	}
	
	/**
	 * 设置 COOKIE.
	 * 
	 * @access public
	 * @param array $cookies COOKIE.
	 * @return \wuyuan\util\SocketHttp
	 */
	public function setCookie(array $cookies) {
		$this->_cookies = array_merge($this->_cookies, $cookies);
		return $this;
	}
	
	/**
	 * 设置请求头.
	 * 
	 * @access public
	 * @param array $headers HEADERS.
	 * @return \wuyuan\util\SocketHttp
	 */
	public function setHeader(array $headers) {
		$this->_headers = array_merge($this->_headers, $headers);
		return $this;
	}
	
	/**
	 * 关闭连接.
	 * 
	 * @access public
	 * @return void
	 */
	public function close() {
		if($this->_connected) {
			fclose($this->_socket);
		}
		
		$this->_connected = FALSE;
		$this->_closed = TRUE;
		$this->_socket = $this->_requestHeader = $this->_responses = NULL;
		$this->_parsedResponse = [];
	}
	
	/**
	 * 获取请求头信息.
	 * 
	 * @access public
	 * @return string
	 */
	public function getRequestHeader() {
		return $this->_requestHeader;
	}
	
	/**
	 * 获取解析后的响应信息.
	 * 
	 * @access public
	 * @return array 返回包含 code(HTTP 代码), header(响应头), body(响应内容), isChunked(是否分块), encoding(压缩方式).
	 */
	public function getResponse() {
		return $this->_parsedResponse;
	}
	
	/**
	 * 获取原始的响应信息.
	 * 
	 * @access public
	 * @return string
	 */
	public function getOriginalResponse() {
		return $this->_responses;
	}
	
	/**
	 * 构造方法.
	 * 
	 * @access public
	 * @param string $hostname 主机名.
	 * @param integer $port 端口号, 默认 80.
	 * @param string $uri 请求的 URI, 默认 /.
	 * @param integer $timeout 连接超时时间(秒), 默认 30.
	 * @return void
	 */
	public function __construct($hostname, $port = 80, $uri = '/', $timeout = 30) {
		if(FALSE === strpos($hostname, '://')) {
			$this->_hostName = $hostname;
		} else {
			list($protocol, $hostname) = explode('://', $hostname);
			$this->_protocol = $protocol . '://';
			$this->_hostName = $hostname;
		}
		
		$this->_port = (integer)$port;
		$this->_uri = (string)$uri;
		$this->_connectionTimeout = (integer)$timeout;
	}
	
	/**
	 * 析构方法.
	 * 
	 * @access public
	 * @return void
	 */
	public function __destruct() {
		$this->_closed or $this->close();
	}
	
}
