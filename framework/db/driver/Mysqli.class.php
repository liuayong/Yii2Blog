<?php

namespace wuyuan\db\driver;

use wuyuan\db\Db;
use wuyuan\base\IDbDriver;
use wuyuan\base\DbException;

/**
 * wuyuan Mysqli 数据库驱动类.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class Mysqli extends Db implements IDbDriver {

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IDbDriver::close()
	 */
	public function close() {
		$this->closed = TRUE;
		if(NULL !== $this->linkId) {
			if(FALSE === $this->linkId->close()) {
				throw new DbException('关闭数据连接时出错('. $this->linkId->error .').', $this->linkId->errno);
			}
		}
		
		$this->linkId = NULL;
		return TRUE;
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IDbDriver::commit()
	 */
	public function commit() {
		NULL !== $this->linkId or $this->connect(); 
		if(!$this->transStarted) {
			throw new DbException('还未开启事务.');
		} elseif(FALSE === $this->linkId->commit()) {
			throw new DbException('提交事务时出错('. $this->linkId->error .').', $this->linkId->errno);
		}
		
		return TRUE;
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IDbDriver::connect()
	 */
	public function connect() {
		if(NULL === $this->linkId) {
			$this->linkId = new \mysqli();
		}
		
		// 连接数据库.
		$this->linkId->real_connect($this->host, $this->userName, $this->passwd, $this->dbName, $this->port);
		if($this->linkId->connect_errno) {
			$error = $this->linkId->error;
			$errno = $this->linkId->errno;
			$this->linkId = NULL;
			throw new DbException($error, $errno, $this->activeConfig);
		}
		
		$this->linkId->set_charset($this->charset); // 设置连接字符集.
		return TRUE;
	}
	
	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IDbDriver::execute()
	 */
	public function execute($strSql) {
		return $this->query($strSql);
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IDbDriver::fetchAll()
	 */
	public function fetchAll() {
		if(NULL === $this->result || TRUE === $this->result) {
			return [];
		}
		
		return $this->result->fetch_all(MYSQLI_ASSOC);
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IDbDriver::fetchFields()
	 */
	public function fetchFields($tableName) {
		NULL !== $this->linkId or $this->connect();
		$strSql = 'SHOW COLUMNS FROM ' . $tableName;
		$this->query($strSql);
		return $this->fetchAll();
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IDbDriver::fetchTables()
	 */
	public function fetchTables($dbName = NULL) {
		NULL !== $this->linkId or $this->connect();
		$dbName = NULL === $dbName ? $this->dbName : $dbName;
		$strSql = "SHOW TABLES FROM " . $dbName;
		$this->query($strSql);
		return $this->fetchAll();
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IDbDriver::freeResult()
	 */
	public function freeResult() {
		if(NULL !== $this->result) {
			$this->result->free();
		}
	}
	
	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IDbDriver::ping()
	 */
	public function ping() {
		NULL !== $this->linkId or $this->connect();
		return $this->linkId->ping();
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IDbDriver::query()
	 */
	public function query($strSql) {
		NULL !== $this->linkId or $this->connect();
		$this->lastSql = $strSql;
		$this->result = $this->linkId->query($strSql);
		$this->affectedRows = $this->linkId->affected_rows;
		$this->lastInsertId = $this->linkId->insert_id;
		if(FALSE === $this->result) {
			throw new DbException($this->linkId->error, $this->linkId->errno, ['lastSql' => $this->lastSql]);
		}
		
		return TRUE;
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IDbDriver::quote()
	 */
	public function quote($str) {
		NULL !== $this->linkId or $this->connect();
		return $this->linkId->escape_string($str);
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IDbDriver::rollback()
	 */
	public function rollback() {
		NULL !== $this->linkId or $this->connect();
		if(!$this->transStarted) {
			throw new DbException('还未开启事务.');
		} elseif(FALSE === $this->linkId->rollback()) {
			throw new DbException('回滚事务时出错('. $this->linkId->error . ').', $this->linkId->errno);
		}
		
		return TRUE;
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IDbDriver::selectDb()
	 */
	public function selectDb($dbName = NULL) {
		NULL !== $this->linkId or $this->connect();
		$dbName = NULL === $dbName ? $this->dbName : $dbName;
		if(FALSE === $this->linkId->select_db($dbName)) {
			throw new DbException('选择数据库('. $dbName .')时出错.', $this->linkId->errno);
		}
		
		return TRUE;
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IDbDriver::startTransaction()
	 */
	public function startTransaction() {
		NULL !== $this->linkId or $this->connect();
		if($this->transStarted) {
			return TRUE;
		} elseif(FALSE === $this->linkId->autocommit(FALSE)) {
			throw new DbException('开启事务('. $this->linkId->error .')时出错.', $this->linkId->errno);
		}
		
		return $this->transStarted = TRUE;
	}

}
