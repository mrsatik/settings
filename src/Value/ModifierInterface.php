<?php
namespace mrsatik\Settings\Value;

interface ModifierInterface
{
    /**
     * Рекурсивно обходит массив чтобы заменить плейсхолдеры
     * @param array|string $data
     */
    public function replaceRecursive($data);
}