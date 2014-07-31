<?php
require_once(__DIR__.'/../vendor/autoload.php');

try {
	$controller	= new \Shellshock\Controller();
	$controller->run($_SERVER['argv']);

} catch(\Exception $e){
	(new \Commando\Command(['__placeholder__']))->error($e);
}
