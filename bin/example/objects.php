#!/usr/bin/php
<?php

use function Joop\Asynchronous\async;

require_once __DIR__ . '/../../vendor/autoload.php';


// Example class
class Sample
{
	private $data;

	public function __construct()
	{
		$this->data = [1, 2, 3];
	}

	public function getData()
	{
		return $this->data;
	}
}

// Create the process
$promise = async(function () {
	sleep(2);

	return new Sample();
});

// We can do some other stuff here while the process is running

// Resolve the promise
/** @var Sample $sample */
$sample = $promise->resolve()->getValue();
var_dump($sample->getData());
