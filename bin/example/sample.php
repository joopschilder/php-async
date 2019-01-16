#!/usr/bin/php
<?php

use function Joop\Asynchronous\async;

require_once __DIR__ . '/../../vendor/autoload.php';

$process = function ($number) {
	sleep($number);
	return $number;
};

async($process, 2);
// Do stuff...