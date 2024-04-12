<?php

namespace mrsatik\Settings\Driver;

use mrsatik\Settings\Driver\BuilderInterface;
use mrsatik\Settings\Driver\Env;
use mrsatik\Settings\Driver\File;
use mrsatik\Settings\Env\EnvironmentNameResolver;

class Builder implements BuilderInterface
{
    /**
     * @var BuilderInterface
     */
    private $driver;

    public function __construct()
    {
        $issetVariables = getenv(EnvironmentNameResolver::ENV_VAR_STAND_POSTFIX);
        $this->driver = $issetVariables !== null && $issetVariables !== false ? new Env() : new File();
    }

    /**
     * {@inheritDoc}
     * @see BuilderInterface::getVariable()
     */
    public function getVariable($name): ?string
    {
        return $this->driver->getVariable($name);
    }
}