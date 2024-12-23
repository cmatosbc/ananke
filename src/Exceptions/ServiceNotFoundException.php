<?php

namespace Ananke\Exceptions;

/**
 * Exception thrown when attempting to create a service that has not been registered.
 * 
 * This exception is thrown by the ServiceFactory when create() is called with a
 * service name that was not previously registered using register().
 */
class ServiceNotFoundException extends \Exception
{
}
