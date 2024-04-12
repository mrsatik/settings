<?php
namespace mrsatik\Settings\Value;

use mrsatik\Settings\Env\NameResolverIntreface;
use mrsatik\Servers\ServersCollection;

class Modifier implements ModifierInterface
{
    /**
     * Префикс для ключей, содержащих внутренние домены сервисов
     * @var string
     */
    const SERVICE_KEY_PREFIX = 'service:';

    /**
     * Префикс для ключей, содержащих внешние домены
     * @var string
     */
    const DOMAIN_KEY_PREFIX = 'domain:';

    /**
     * Префикс для ключей, содержащих строковые данные
     * @var string
     */
    const SERVICEKEY_KEY_PREFIX = 'servicekey:';

    /**
     * Префикс для коннектов
     * @var string
     */
    const CONNECTION_KEY_PREFIX = 'connection:';

    const PROJECT_ROOT_PATH = 'project:';

    const APP_HOST_VAR = '%appHost%';

    private $variables = [
        self::APP_HOST_VAR => self::DOMAIN_KEY_PREFIX
    ];

    private $replacedValues = [];

    /**
     * Резолвер конфигов
     * @var NameResolverIntreface
     */
    private $environmentNameResolver;

    public function __construct(NameResolverIntreface $envResolver)
    {
        $this->environmentNameResolver = $envResolver;
    }

    /**
     * {@inheritDoc}
     * @see ModifierInterface::replaceRecursive()
     */
    public function replaceRecursive($data)
    {
        if (is_array($data)) {
            foreach ($data as $k => $value) {
                if (substr($k, 0, strlen(self::CONNECTION_KEY_PREFIX)) === self::CONNECTION_KEY_PREFIX) {
                    if (substr($k, 0, strlen(self::CONNECTION_KEY_PREFIX)) === self::CONNECTION_KEY_PREFIX) {
                        unset($data[$k]);
                        $value = $this->replaceValueByKeyPrefix($value);
                        $data[str_replace(self::CONNECTION_KEY_PREFIX, '', $k)] = is_string($value) ?
                        new ServersCollection($value) :
                        $value;
                    } else {
                        $data[$k] = $this->replaceRecursive($value);
                    }
                } else {
                    $data[$k] = $this->replaceRecursive($value);
                }
            }

            return $data;
        } else {
            return $this->replaceValueByKeyPrefix($this->replaceValueVariable($data));
        }
    }

    private function replaceValueVariable($value)
    {
        foreach ($this->variables as $variableFrom => $variableTo) {
            if (strpos($value, $variableFrom) !== false) {
                $value = str_replace($variableFrom, $this->replaceValueByKeyPrefix($variableTo), $value);
            }
        }
        return $value;
    }

    private function replaceValueByKeyPrefix($value)
    {
        if (isset($this->replacedValues[$value])) {
            return $this->replacedValues[$value];
        } else {
            if (substr($value, 0, strlen(self::SERVICE_KEY_PREFIX)) === self::SERVICE_KEY_PREFIX) {
                $this->replacedValues[$value] = $this->environmentNameResolver->getService(substr($value, strlen(self::SERVICE_KEY_PREFIX)));
            } elseif (substr($value, 0, strlen(self::SERVICEKEY_KEY_PREFIX)) === self::SERVICEKEY_KEY_PREFIX) {
                $this->replacedValues[$value] = $this->environmentNameResolver->getKey(substr($value, strlen(self::SERVICEKEY_KEY_PREFIX)));
            } elseif (substr($value, 0, strlen(self::DOMAIN_KEY_PREFIX)) === self::DOMAIN_KEY_PREFIX) {
                $this->replacedValues[$value] = $this->environmentNameResolver->getDomainName(substr($value, strlen(self::DOMAIN_KEY_PREFIX)));
            } elseif (strpos($value, self::PROJECT_ROOT_PATH) === 0) {
                $this->replacedValues[$value] = $this->environmentNameResolver->getProjectRootPath(substr($value, strlen(self::PROJECT_ROOT_PATH)));
            } else {
                return $value;
            }
        }

        return $this->replacedValues[$value];
    }
}