<?php
require_once(__DIR__.'/../vendor/autoload.php');

try {
	if(isset($_SERVER['HOME'])){
		Shellshock\Utilities::setHomeDir($_SERVER['HOME']);
	}

	$controller	= new \Shellshock\Controller();
	$controller->run($_SERVER['argv']);

} catch(\Exception $e){
	(new \Commando\Command(['__placeholder__']))->error($e);
}
