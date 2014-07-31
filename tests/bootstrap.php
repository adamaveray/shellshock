<?php
date_default_timezone_set('UTC');

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = @include(__DIR__.'/../vendor/autoload.php');

if(!$loader){
	// Dependencies not installed
    echo 'You must install the Composer dependencies (`composer install`)';
    die(1);
}
