#!/usr/bin/php
<?php

require_once __DIR__ . '/../vendor/autoload.php';


// Create a function we want to run asynchronously
$process = function ($i) {
	$delayMicroseconds = (10 - $i) * 1000000;
	usleep($delayMicroseconds);

	return getmypid();
};


// Execute the functions asynchronously - each returning a Promise
$promises = [];
foreach (range(0, 10) as $i)
	$promises[] = Asynchronous::run($process, $i);


// Wait for all promises to resolve
while (count($promises) > 0) {
	foreach ($promises as $index => $promise) {
		if ($promise->isResolved() && !$promise->isEmpty()) {
			print("Response retrieved: " . $promise->getValue() . PHP_EOL);
			unset($promises[$index]);
		}
	}
}


exit(0);


