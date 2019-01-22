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
	/** @var bool */
	private static $inParentRuntime = true;


	/**
	 *
	 */
	public static function markChild()
	{
		self::$inParentRuntime = false;
	}


	/**
	 * @return bool
	 */
	public static function isChild()
	{
		return !self::$inParentRuntime;
	}

}