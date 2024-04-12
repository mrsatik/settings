<?php

namespace mrsatik\Settings\Driver;

interface BuilderInterface
{
    /**
     * Возвращает данные по переменной окружения
     * @param string $varname
     * @return string|null
     */
    public function getVariable($varname): ?string;
}