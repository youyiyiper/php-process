<?php

namespace Globals;

/**
 * 多进程
 * 
 * @author zhulei
 *         @uptime 2013-10-4
 */
final class Runnable {
	/**
	 * 子进程对象
	 * 
	 * @var Thread
	 */
	private $_thread;
	
	/**
	 * 进程参数列表
	 * 
	 * @var mixed
	 */
	private $_args;
	
	/**
	 * 最大同时运行数
	 * 
	 * @var number
	 */
	private $_max_thread;
	
	/**
	 * 是否需要返回
	 * 
	 * @var boolean
	 */
	private $_need_return;
	
	/**
	 * 是否处理返回结果
	 * 
	 * @var boolean
	 */
	private $_need_process_return;
	
	/**
	 * 检查进程间隔
	 * 
	 * @var number 微秒
	 */
	private $_sleep_time;
	
	/**
	 * 当前运行的进程数
	 * 
	 * @var number
	 */
	private $_current_thread_count = 0;
	
	/**
	 * 是否为子进程运行
	 * 
	 * @var boolean
	 */
	private $_is_child = false;
	
	/**
	 * 子进程Pid列表
	 * 
	 * @var array
	 */
	private $_child_list = array ();
	
	/**
	 * 总运行数
	 * 
	 * @var number
	 */
	private $_total_run = 0;
	
	/**
	 * 是否需要Thread::add_task() 动态增加方法
	 * 
	 * @var boolean
	 */
	private $_need_add_task = false; // 是否需要增加任务
	
	/**
	 * 初始化新的并发任务
	 * 
	 * @param
	 *        	Thread 子进程对象 $thread
	 * @param
	 *        	mixd 参数 $args
	 * @param
	 *        	number 最大同时执行进程 $max_thread
	 * @param
	 *        	string 是否需要返回 $need_return
	 * @param
	 *        	string 是否需要格式化返回内容 $need_process_return
	 * @param
	 *        	number 检查间隔时间 $sleep_time
	 */
	public function __construct(Thread $thread, $args = null, $max_thread = 10, $need_return = false, $need_process_return = false, $sleep_time = 10000) {
		$this->_thread = $thread;
		$this->_args = $args;
		$this->_max_thread = $max_thread;
		$this->_need_return = $need_return;
		$this->_need_process_return = $need_process_return;
		$this->_sleep_time = $sleep_time;
		if (is_null ( $this->_args )) {
			$this->_need_add_task = true;
			$this->_add_task ();
		}
	}
	
	/**
	 * 执行任务
	 */
	public function run() {
		$spawns = $this->_add_run ();
		return $this->_check ( $spawns );
	}
	
	/**
	 * gc时检查未完成的子进程,全部清除掉
	 */
	public function __destruct() {
		if (! $this->_is_child) {
			// if ($this->_need_return) {
			// $this->_thread->destruct ();
			// }
			
			if (count ( $this->_child_list )) {
				foreach ( $this->_child_list as $child_pid ) {
					write_info ( "异常退出,杀死子进程 : {$child_pid}" );
					posix_kill ( $child_pid, SIGKILL );
					usleep ( 30000 );
				}
			}
		}
	}
	
	/**
	 * 动态增加任务
	 */
	private function _add_task() {
		$this->_args = $this->_thread->add_task ();
	}
	
	/**
	 * 增加子任务
	 *
	 * @return number
	 */
	private function _add_run() {
		if (is_array ( $this->_args )){
			return $this->_run_array ();
		}elseif (is_numeric ( $this->_args )){
			return $this->_run_num ();
		}
	}
	
	/**
	 * 运行次数
	 * 
	 * @todo 未完成
	 * @return number
	 */
	private function _run_num() {
		for($i = 0; $i < $this->_args; $i ++) {
			if ($this->_current_thread_count == $this->_max_thread){
				break;
			}

			$child_id = $this->_spawn ( $i );
			$this->_child_list [$child_id] = $child_id;
			$this->_current_thread_count ++;
		}

		$this->_total_run += $i;
		return $i;
	}
	
	/**
	 * 以数组方式运行子进程
	 *
	 * @return number
	 */
	private function _run_array() {
		$i = 0;
		foreach ( $this->_args as $key => $val ) {
			// 不允许超过同时运行最大线程数
			if ($this->_current_thread_count == $this->_max_thread) {
				break;
			}

			$i ++;
			$pid = $this->_spawn ( $val );

			$this->_child_list [$pid] = $pid;
			unset ( $this->_args [$key] );

			$this->_current_thread_count++;
		}

		$this->_total_run += $i;
		return $i;
	}
	
	/**
	 * 检查任务是否全部运行
	 * 
	 * @param number $i  进程数量      	
	 * @return multitype:
	 */
	private function _check($i) {
		$this->_need_return && $data = array ();
		for($id = 0; $id < $i; $id ++) {

			//pcntl_waitpid
			// < -1	等待任意进程组ID等于参数pid给定值的绝对值的进程。
			// -1	等待任意子进程;与pcntl_wait函数行为一致。
			// 0	等待任意与调用进程组ID相同的子进程。
			// > 0	等待进程号等于参数pid值的子进程。

			while ( ! ($child_pid = pcntl_waitpid ( - 1, $status, WNOHANG )) ) {
				usleep ( $this->_sleep_time );
			}

			unset ( $this->_child_list [$child_pid] );

			if ($this->_need_return) {

				$result = $this->_need_process_return ? 
				$this->_thread->process_return ( $this->_thread->get ( $child_pid ) ) : 
				$this->_thread->get ( $child_pid );

				array_push ( $data, $result );
			}

			$this->_current_thread_count --;
			$i += $this->_add_run ();
		}

		if ($i && $this->_need_return){
			return $data;
		}
	}
	
	/**
	 * 子进程执行方式
	 * 1、pcntl_fork 生成子进程
	 * 2、执行结果返回
	 * 3、posix_kill杀死当前进程
	 *
	 * @param mixed $param        	
	 * @return number
	 */
	private function _spawn($param) {
		// 会创建一个子进程。子进程会复制当前进程，也就是父进程的所有：数据，代码，还有状态
		$pid = pcntl_fork ();
		// 当创建子进程成功后，在父进程内，返回0，在子进程内返回自身的进程号，失败则返回-1
		
		if($pid == -1){
        	//错误处理：创建子进程失败时返回-1.
        	exit( 'could not fork' );

		// 子进程
		}else if ($pid === 0) { 
			$this->_is_child = true;
			$child_pid = getmypid ();

			//转发调用
			$rs = $this->_thread->fork ( $param );
			if ($this->_need_return && $rs !== null) {
				$this->_thread->send ( $child_pid, $rs );
			}

			#在一个进程终止或者停止时，将SIGCHLD信号发送给其父进程，按系统默认将忽略此信号，
			#如果父进程希望被告知其子系统的这种状态，则应捕捉此信号。
			posix_kill ( $child_pid, SIGCHLD );

			// gc中间件
			$this->_thread->destruct ( $child_pid ); 

			// 子进程必须要退出，否则会向下执行
			exit ( "process is finished, PID : {$child_pid} \n" ); 

		//主进程
		} else {
			return $pid;
		}
	}
}