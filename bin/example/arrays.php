#!/usr/bin/php
<?php

use function Joop\Asynchronous\async;

require_once __DIR__ . '/../../vendor/autoload.php';

$promise = async(function () {
	sleep(1);

	return range(random_int(0, 10), random_int(20, 60));
});

$array = $promise->resolve()->getValue();
var_dump($array);
