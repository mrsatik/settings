<?php

namespace mrsatik\Settings\Driver;

interface FileInterface
{
    /**
     * Имя файла для чтения
     * @return string
     */
    public function getFileName(): string;
}