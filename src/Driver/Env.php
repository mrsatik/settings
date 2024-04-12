<?php

namespace mrsatik\Settings\Driver;

use mrsatik\Settings\Driver\EnvInterface;
use mrsatik\Settings\Driver\BuilderInterface;

class Env implements EnvInterface, BuilderInterface
{
    private $variables;

    public function __construct()
    {
        $this->variables = [];
    }

    /**
     * {@inheritDoc}
     * @see BuilderInterface::getVariable()
     */
    public function getVariable($varname): ?string
    {
        if (isset($this->variables[$varname])) {
            return $this->variables[$varname];
        }

        $this->variables[$varname] = getenv($varname);

        return $this->variables[$varname];
    }
}