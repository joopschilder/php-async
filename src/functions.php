<?php

namespace Joop\Asynchronous;

require_once __DIR__ . '/../vendor/autoload.php';

if (!function_exists('async_run')) {

	/**
	 * @param callable $function
	 * @param mixed ...$parameters
	 * @return Promise|null
	 */
	function async(callable $function, ...$parameters)
	{
		return Asynchronous::run($function, ...$parameters);
	}
}


if (!function_exists('async_cleanup')) {
	/**
	 *
	 */
	function async_cleanup()
	{
		Asynchronous::cleanup();
	}
}
