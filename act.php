<?php

namespace thread;

final class Act extends \Globals\Thread {
	
	/*
	 * (non-PHPdoc) @see \Globals\Thread::add_task()
	 */
	public function add_task() {
		// TODO Auto-generated method stub
	}
	
	/*
	 * (non-PHPdoc) @see \Globals\Thread::fork()
	 */
	public function fork($args) {
        list ( $pt, $date ) = $args;
		try {
            $table_date = date ( 'YmdHis');
            echo "删除:".$table_date."条数据";
		} catch ( \Exception $e ) {
			var_dump($e->getMessage());
		}
	}
	
	/*
	 * (non-PHPdoc) @see \Globals\Thread::process_return()
	 */
	public function process_return() {
		// TODO Auto-generated method stub
	}
}