<?php

namespace Globals;

/**
 * 线程抽象类，只能在unix下运行.
 * 消息中间件是由laravel的cache层实现
 *
 * @author zhulei
 */
abstract class Thread {
	
	/**
	 * 缓存KEY前缀
	 *
	 * @var string
	 */
	const PERFIX = 'thread_';
	
	/**
	 * 过期时间,5分钟
	 *
	 * @var integer
	 */
	const LIMIT_TIME = 2;
	
	/**
	 * 动态增加任务
	 *
	 * @return array
	 */
	abstract public function add_task();
	
	/**
	 * 处理返回结果
	 * @retrun mixed
	 */
	abstract public function process_return();
	
	/**
	 * 招待进程任务
	 *
	 * @param mixed $args        	
	 */
	abstract public function fork($args);
	
	/**
	 * 获取缓存key
	 *
	 * @param integer $pid        	
	 * @return string
	 */
	public function key($pid) {
		return static::PERFIX . __CLASS__ . $pid;
	}
	
	/**
	 * 获取执行结果
	 *
	 * @param integer $pid        	
	 * @return boolean
	 */
	public function get($pid) {
		#return \Laravel\Cache::get ( $this->key ( $pid ) );
	}
	
	/**
	 * 处理保存结果
	 *
	 * @param integer $pid        	
	 * @param mixed $return        	
	 * @return boolean
	 */
	public function send($pid, $return) {
		#\Laravel\Cache::put ( $this->key ( $pid ), $return, static::LIMIT_TIME );
	}
	
	/**
	 * 删除任务结果
	 */
	public function destruct($pid) {
		#\Laravel\Cache::forget ( $this->key ( $pid ) );
	}
}


