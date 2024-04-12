<?php

namespace mrsatik\Settings\Config;

interface LoaderInterface
{
    /**
     * Возвращает данные конфига
     * @param array $files
     */
    public function getConfig(array $files): array;

    /**
     * Возвращает директорию
     * @return string
     */
    public function getConfigFolder(): string;
}