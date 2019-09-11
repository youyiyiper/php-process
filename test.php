<?php 

require 'thread.php';
require 'runnable.php';
require 'act.php';

$pts = array(1,1,1,1);
$thread = new \thread\Act();
$runable = new \Globals\Runnable ( $thread, $pts, 10 );
$runable->run ();