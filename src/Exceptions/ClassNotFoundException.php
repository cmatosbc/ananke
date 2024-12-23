<?php

namespace Ananke\Exceptions;

/**
 * Exception thrown when attempting to register a service with a non-existent class.
 * 
 * This exception is thrown by the ServiceFactory when register() is called with a
 * class name that does not exist or cannot be autoloaded.
 */
class ClassNotFoundException extends \Exception
{
}
