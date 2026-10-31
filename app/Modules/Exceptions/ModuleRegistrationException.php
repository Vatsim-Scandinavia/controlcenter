<?php

namespace App\Modules\Exceptions;

use RuntimeException;

/**
 * Thrown when a module registers an extension point incorrectly, e.g. a
 * permission outside its own namespace.
 */
class ModuleRegistrationException extends RuntimeException {}
