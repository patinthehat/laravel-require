<?php

namespace LaravelRequire\Exceptions;

/**
 * thrown when the service provider we are attempting to register
 * has aready been registered.
 *
 */
class ServiceProviderAlreadyRegisteredException extends \Exception
{

}