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


if (!function_exists('async_reap_zombies')) {

	/**
	 *
	 */
	function async_reap_zombies()
	{
		Asynchronous::reap();
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