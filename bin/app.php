#!/usr/bin/php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use function Joop\Asynchronous\async;
use Joop\Asynchronous\Promise;

/**
 * @param Promise[] $promises
 */
function awaitPromises(array &$promises)
{
	foreach ($promises as $index => $promise) {
		if ($promise->isResolved()) {
			unset($promises[$index]);

			if (!$promise->isEmpty() && !$promise->isError())
				print($promise->getValue() . PHP_EOL);
		}
	}
}


/*
 * Example of asynchronous processing in PHP
 */

$process = function ($i) {
	$delayMicroseconds = (5 - $i) * 1000000;
	usleep($delayMicroseconds);

	return sprintf(
		'PID %-5d slept for %.1f seconds',
		getmypid(), $delayMicroseconds / 1000000
	);
};

$promises = [];
foreach (range(0, 5) as $i)
	$promises[] = async($process, $i);

while (count($promises) > 0)
	awaitPromises($promises);
