<?php

namespace JoopSchilder\Asynchronous;

/**
 * Class Runtime
 * @package JoopSchilder\Asynchronous
 *
 * Created to keep track of the current runtime situation.
 * This is critical in order for the application to know
 * which destructors and handlers to call.
 */
class Runtime
{
	/** @var int */
	public const INITIAL_SHM_SIZE_MB = 16;

	/** @var int */
	private static $sharedMemKey = null;

	/** @var int */
	private static $sharedMemSize = self::INITIAL_SHM_SIZE_MB * (1024 ** 2);

	/** @var bool */
	private static $inParentRuntime = true;


	/**
	 * Runtime constructor.
	 */
	private function __construct()
	{
	}


	/**
	 * @return int
	 */
	public static function getSharedMemorySize(): int
	{
		return self::$sharedMemSize;
	}


	/**
	 * @return int
	 */
	public static function getSharedMemoryKey(): int
	{
		/*
		 * Use the filename as an identifier to create the System V IPC key.
		 */
		if (is_null(self::$sharedMemKey)) {
			self::$sharedMemKey = ftok(__FILE__, 't');
		}

		return self::$sharedMemKey;
	}


	/**
	 * @return bool
	 */
	public static function isChild(): bool
	{
		return !self::$inParentRuntime;
	}


	/*
	 * 'Semi' private.
	 * To be used by internal classes.
	 */

	/**
	 * @param int $sizeMegaBytes
	 */
	public static function setSharedMemorySizeMB(int $sizeMegaBytes): void
	{
		self::$sharedMemSize = abs($sizeMegaBytes) * (1024 ** 2);
	}


	/**
	 * @param int $sizeBytes
	 */
	public static function setSharedMemorySizeB(int $sizeBytes): void
	{
		self::$sharedMemSize = $sizeBytes;
	}


	/**
	 *
	 */
	public static function markAsChild(): void
	{
		self::$inParentRuntime = false;
	}

}
