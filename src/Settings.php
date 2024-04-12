<?php
namespace mrsatik\Settings;

use mrsatik\Settings\SettingsInterface;
use mrsatik\Settings\Config\Loader;
use mrsatik\Settings\Config\LoaderInterface;
use mrsatik\Settings\Env\NameResolverIntreface;
use mrsatik\Settings\Env\EnvironmentNameResolver;
use mrsatik\Settings\Exception\ConfigNotFoundException;
use mrsatik\Settings\Exception\InvalidKeyException;
use mrsatik\Settings\Value\Modifier;
use mrsatik\Settings\Value\ModifierInterface;

class Settings implements SettingsInterface
{
    /**
     * Разделитель ключей конфига
     * @var string
     */
    const KEY_DELIMITER = '.';

    /**
     * Имя ключа, содержащего значение ключа-массива, если у него есть дети
     * @var string
     */
    const VALUE_KEY_NAME = 'value:';

    /**
     * Символ, используемый для шаблонизации
     * @var string
     */
    const WILDCARD_SYMBOL = '*';

    /**
     * Указатель на instance объекта
     *
     * @var Settings|null
     *
     */
    private static $instance;

    /**
     * Резолвер конфигов
     * @var NameResolverIntreface
     */
    private $environmentNameResolver;

    /**
     * Подгрузчик конфигов по имени
     * @var LoaderInterface
     */
    private $configLoader;

    /**
     * @var ModifierInterface
     */
    private $valueModifier;

    /**
     * Возвращает указатель на объект
     *
     * @return SettingsInterface
     */
    final public static function getInstance(): SettingsInterface
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->environmentNameResolver = new EnvironmentNameResolver();
        $this->configLoader = new Loader($this->environmentNameResolver);
        $this->valueModifier = new Modifier($this->environmentNameResolver);
    }

    /**
     * {@inheritDoc}
     * @see SettingsInterface::getValue()
     */
    public function getValue($key)
    {
        $value = $this->getNullableValue($key);

        if ($value === null) {
            throw new InvalidKeyException(sprintf('Could not obtain config value for key %s!', $key));
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     * @see SettingsInterface::getNullableValue()
     */
    public function getNullableValue($key)
    {
        if ($key === '') {
            return null;
        }
        $value = $this->getValueInternal($key, []);

        return $value;
    }

    /**
     * {@inheritDoc}
     * @see SettingsInterface::getValueByMask()
     */
    public function getValueByMask($keyMask, array $keyFilter)
    {
        if ($keyMask === '' || sizeof($keyFilter) === 0 || key($keyFilter) === '' || current($keyFilter) === null) {
            return null;
        }
        return $this->getValueInternal($keyMask, $keyFilter);
    }

    /**
     *
     * @param string $keyMask
     * @param array $keyFilter
     * @return mixed
     */
    private function getValueInternal($keyMask, array $keyFilter)
    {
        $key_parts = $this->getPartsOfKey($keyMask);
        $filterParts = $this->getPartsOfKey(key($keyFilter));
        $filtered_value = current($keyFilter);
        $configName = array_shift($key_parts);
        $config = $this->getConfig($configName);
        if ($config !== null && $config !== []) {
            $value = $this->getFromArrayByKeyChainAndMask($config, $key_parts, $filterParts, $filtered_value);
            if ($value !== null) {
                $value = $this->valueModifier->replaceRecursive($value);
            }
            return $value;
        }

        throw new ConfigNotFoundException(sprintf('Config "%s" with key "%s" not found', $configName, $keyMask));
    }

    /**
     *
     * @param array $array
     * @param array $key_chain
     * @param array $filter_chain
     * @param mixed $value_to_compare
     * @return mixed
     */
    private function getFromArrayByKeyChainAndMask(array $array, array $key_chain, array $filter_chain = array(), $value_to_compare = null)
    {
        $result = $array;
        foreach ($key_chain as $key) {
            if ($key === self::VALUE_KEY_NAME || !is_array($result)) {
                $result = null;
                break;
            } elseif ($key === self::WILDCARD_SYMBOL) {
                $result = $this->determineStepByWildcard($result, $filter_chain, $value_to_compare);
            } elseif (isset($result[$key])) {
                $result = $result[$key];
            } else {
                $result = $this->determineStepByValue($result, $key);
            }
        }
        if (is_array($result) && isset($result[self::VALUE_KEY_NAME])) {
            $result = $result[self::VALUE_KEY_NAME];
        }
        return $result;
    }

    /**
     *
     * @param array $array
     * @param array $filterParts
     * @param mixed $value_to_compare
     * @return mixed
     */
    private function determineStepByWildcard(array $array, array $filterParts, $value_to_compare)
    {
        $result = null;
        if (sizeof($filterParts) !== 0) {
            foreach ($array as $subkey => $subvalue) {
                if (is_array($subvalue) && $this->getFromArrayByKeyChainAndMask($subvalue, $filterParts) === $value_to_compare) {
                    $result = $array[$subkey];
                    break;
                }
            }
        }
        return $result;
    }

    /**
     *
     * @param array $array
     * @param mixed $value
     * @return mixed
     */
    private function determineStepByValue(array $array, $value)
    {
        $result = null;
        foreach ($array as $subkey => $subvalue) {
            if (isset($subvalue[self::VALUE_KEY_NAME]) && (string)$subvalue[self::VALUE_KEY_NAME] === $value) {
                $result = $array[$subkey];
                break;
            }
        }
        return $result;
    }

    /**
     *
     * @param string $configName
     * @return array
     */
    private function getConfig($configName): array
    {
        return $this->configLoader->getConfig([
            $this->environmentNameResolver->getBaseConfigName($configName),           // redis.php
            $this->environmentNameResolver->getLocalConfigName($configName),          // redis.dev.php
            $this->environmentNameResolver->getDomainConfigName($configName),         // redis.ru.php
            $this->environmentNameResolver->getLocalDomainConfigName($configName),    // redis.ru.dev.php
        ]);
    }

    /**
     *
     * @param string $key
     * @return array
     */
    private function getPartsOfKey($key): array
    {
        return array_filter(explode(self::KEY_DELIMITER, $key), function ($value) {
            return strlen($value) > 0;
        });
    }
}
