<?php
use JoopSchilder\Asynchronous\Asynchronous;
use JoopSchilder\Asynchronous\Promise;

if (!function_exists('async')) {

	/**
	 * @param callable $function
	 * @param mixed ...$parameters
	 * @return Promise
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


if (!function_exists('async_wait_all')) {

	/**
	 *
	 */
	function async_wait_all()
	{
		Asynchronous::waitForChildren();
	}
}
