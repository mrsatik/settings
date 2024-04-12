<?php

namespace mrsatik\Settings;

interface SettingsInterface
{

    /**
     * Возвращает значение ключа из conf-файла или бросает Exception при его отсутствии
     *
     * @param string $key строка вида "db.master.host"
     *
     * @return mixed
     * @throws InvalidKeyException
     */
    public function getValue($key);

    /**
     * Возвращает значение ключа из conf-файла или NULL при его отсутствии
     *
     * @param string $key строка вида "db.master.host"
     *
     * @return mixed|null
     */
    public function getNullableValue($key);

    /**
     * Возвращает значение ключа из conf-файла по wildcard-маске или NULL при его отсутствии
     *
     *     Пример: ('foo.bar.tests.*.host',['dbname'=>'tournaments']) - вернуть хост для первого значения с базой tournaments
     *
     * @param string $keyMask строка вида "db.master.*.host"
     * @param array $keyFilter пара ключ-значение, по которой будет искаться замена '*'
     * @return mixed
     */
    public function getValueByMask($keyMask, array $keyFilter);
}