<?php

namespace JoopSchilder\Asynchronous;

/**
 * Class Asynchronous
 * Responsible for management of child processes and shared memory.
 */
class Asynchronous
{
	/** @var Asynchronous|null */
	private static $instance;

	/** @var int[] */
	private $children = [];

	/** @var resource */
	private $shm;


	/**
	 * @param callable $function
	 * @param mixed ...$parameters
	 * @return Promise|null;
	 */
	public static function run(callable $function, ...$parameters)
	{
		/*
		 * Prepare for fork
		 */
		$instance = self::getInstance();
		$promiseKey = Promise::generatePromiseKey();

		/*
		 * Fork the parent
		 */
		$pid = pcntl_fork();

		/*
		 * The fork failed. Instead of returning a promise, we return null.
		 */
		if ($pid == -1)
			return null;

		/*
		 * Parent process. We keep track of the PID of the child process
		 * in order for us to read out it's status later on.
		 * A Promise instance is returned that corresponds to the key in
		 * memory to which the child process will write sometime.
		 */
		if ($pid > 0) {
			$instance->children[] = $pid;

			return new Promise($promiseKey);
		}

		/*
		 * Child process. Mark the (copied) instance of this class as a child
		 * to prevent unneeded shutdown handler execution.
		 * Reattach to the shared memory block (the $shm member variable is a
		 * resource since PHP > 5.3 and is thus not shared with the child)
		 * and execute the function.
		 * On a successful execution, write the result to the shared memory
		 * block to which the parent is attached.
		 * On failure, write a default response to the block in order for
		 * the Promise to be able to resolve.
		 */
		Runtime::markAsChild();
		$instance->_attachToShm();

		try {
			$response = call_user_func($function, ...$parameters);
			shm_put_var($instance->shm, $promiseKey, $response ?? Promise::RESPONSE_NONE);

			exit(0);

		} catch (\Throwable $throwable) {
			shm_put_var($instance->shm, $promiseKey, Promise::RESPONSE_ERROR);

			exit(1);
		}
	}

	/**
	 *
	 */
	public static function reap()
	{
		/*
		 * Iterate over all child process PIDs and check
		 * if one or more of them has stopped.
		 */
		$instance = self::getInstance();
		foreach ($instance->children as $index => $pid) {
			$response = pcntl_waitpid($pid, $status, WNOHANG);
			if ($response === $pid)
				unset($instance->children[$index]);
		}
	}

	/**
	 *
	 */
	public static function waitForChildren()
	{
		self::getInstance()->_awaitChildren();
	}

	/**
	 *
	 */
	public static function removeShmBlock()
	{
		self::getInstance()->_removeShmBlock();
	}

	/**
	 * @return int
	 */
	public static function childCount()
	{
		return count(self::getInstance()->children);
	}



	/*
	 * Private methods below
	 */

	/**
	 * Asynchronous constructor.
	 */
	private function __construct()
	{
		/*
		 * The reason we do this is for when the shm block
		 * already exists. We attach, remove, detach and reattach
		 * to ensure a clean state.
		 */
		$this->_attachToShm(); // Attach
	}

	/**
	 * @return $this
	 */
	private function _awaitChildren()
	{
		/*
		 * Wait for the children to terminate
		 */
		while (count($this->children) > 0) {
			pcntl_wait($status);
			array_shift($this->children);
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	private function _removeShmBlock()
	{
		/*
		 * Detach from the shared memory block
		 */
		if (is_resource($this->shm)) {
			shm_remove($this->shm);
			shm_detach($this->shm);
		}

		return $this;
	}


	/**
	 * @return $this
	 */
	private function _attachToShm()
	{
		$this->shm = shm_attach(Runtime::getSharedMemoryKey(), Runtime::getSharedMemorySize());

		return $this;
	}


	/**
	 * @return Asynchronous
	 */
	private static function getInstance()
	{
		if (is_null(self::$instance)) {
			/*
			 * This is executed once during runtime;
			 * when a functionality from this class
			 * is used for the first time.
			 */
			self::$instance = new static();
			self::registerHandlers();
		}

		return self::$instance;
	}


	/**
	 *
	 */
	private static function registerHandlers()
	{
		$instance = self::getInstance();

		/*
		 * The shutdown handler
		 */
		register_shutdown_function(function () use (&$instance) {
			if (Runtime::isChild())
				return;

			$instance->_awaitChildren();
			$instance->_removeShmBlock();
		});
	}

	/**
	 *
	 */
	public function __destruct()
	{
		if (Runtime::isChild())
			return;

		$instance = self::getInstance();
		$instance->_removeShmBlock();
	}

}