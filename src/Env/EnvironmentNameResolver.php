<?php
namespace mrsatik\Settings\Env;

use mrsatik\Settings\Env\NameResolverIntreface;
use RuntimeException;
use mrsatik\Settings\Driver\Builder;
use mrsatik\Servers\ServersCollection;

class EnvironmentNameResolver implements NameResolverIntreface
{
    const APPROVED_DOMAINS = [
        'ru',
        'en',
    ];

    const VK_DOMAINS = [
        'vkplay.com' => 'vk',
        'vkplay.ru' => 'vkru',
    ];

    /**
     * Имя ENV-переменной, где содержится conf-суффикс текущей среды разработки (dev)
     */
    const ENV_VAR_STAND_POSTFIX = 'APP_ENV';

    /**
     * Имя ENV-переменной, где содержится доменный префикс (rnefedov.pvp, aperchenok.pvp)
     */
    const ENV_VAR_DOMAIN_PREFIX = 'APP_DOMAIN_PREFIX';

    /**
     * Имя ENV-переменной, где содержится доменный суффикс для внешних доменов (.dev, .stage)
     */
    const ENV_VAR_EXTERNAL_DOMAIN_SUFFIX = 'APP_EXTERNAL_DOMAIN_SUFFIX';

    /**
     * Имя ENV-переменной, где содержится доменный суффикс для внешних доменов (.dev, .stage)
     */
    const ENV_VAR_DOMAIN_SUFFIX = 'APP_DOMAIN_LOCALITY';

    /**
     * Дефолтный домен при запуске из коммандной строки, если не указан параметр self::CLI_PLATFORM_LOCATION_PARAM_NAME
     */
    const CLI_DEFAULT_DOMAIN = 'ru';

    /**
     * Параметр для передачи текущего домена при запуске из коммандной строки
     */
    const CLI_PLATFORM_LOCATION_PARAM_NAME = '--platform-location=';

    const CONFIG_FILE_EXTENSION = 'php';

    const NAME_DELIMITER = '.';

    /**
     * Переменные окружения
     *
     * @var array $variables
     */
    private $variables = [
        self::ENV_VAR_STAND_POSTFIX => null, // stage, dev, prod
        self::ENV_VAR_EXTERNAL_DOMAIN_SUFFIX => null, // .stage, .dev, .prod
        self::ENV_VAR_DOMAIN_PREFIX => null // user.irr
    ];

    private $countrySuffix = null;

    /**
     *
     * @var string|null $platformLocation
     */
    private $platformLocation = null;

    /**
     * @var BuilderInterface
     */
    private $driverEnv;

    public function __construct()
    {
        $this->driverEnv = new Builder();
    }

    /**
     * {@inheritDoc}
     * @see NameResolverIntreface::getBaseConfigName()
     */
    public function getBaseConfigName($name)
    {
        return sprintf('%s.%s', $name, self::CONFIG_FILE_EXTENSION);
    }


    /**
     * {@inheritDoc}
     * @see NameResolverIntreface::getLocalConfigName()
     */
    public function getLocalConfigName($name)
    {
        return sprintf('%s.%s.%s', $name, $this->getStandPostfix(), self::CONFIG_FILE_EXTENSION);
    }


    /**
     * {@inheritDoc}
     * @see NameResolverIntreface::getDomainConfigName()
     */
    public function getDomainConfigName($name)
    {
        return sprintf('%s.%s.%s', $name, $this->getTopLevelDomain(), self::CONFIG_FILE_EXTENSION);
    }

    /**
     * {@inheritDoc}
     * @see NameResolverIntreface::getLocalDomainConfigName()
     */
    public function getLocalDomainConfigName($name)
    {
        return sprintf('%s.%s.%s.%s', $name, $this->getTopLevelDomain(), $this->getStandPostfix(), self::CONFIG_FILE_EXTENSION);
    }

    /**
     * {@inheritDoc}
     * @see NameResolverIntreface::getService()
     */
    public function getService($name)
    {
        $service = \sprintf('%s_%s', $this->getEnvVar(self::ENV_VAR_DOMAIN_SUFFIX, null), $name);
        $result = $this->getEnvVar($service);
        return new ServersCollection($result);
    }

    /**
     * {@inheritDoc}
     * @see NameResolverIntreface::getKey()
     */
    public function getKey($name): string
    {
        $service = \sprintf('%s_%s', $this->getEnvVar(self::ENV_VAR_DOMAIN_SUFFIX, null), $name);
        return $this->getEnvVar($service);
    }

    /**
     * {@inheritDoc}
     * @see NameResolverIntreface::getDomainName()
     */
    public function getDomainName($subDomain)
    {
        $subDomain = $subDomain && strlen($subDomain) > 0 ? sprintf('%s.', $subDomain) : ''; // опционально
        $domain = \sprintf('%s%s%s', $subDomain, $this->getTopLevelDomain(), $this->getEnvExternalDomainSuffix());
        return $domain;
    }


    /**
     * {@inheritDoc}
     * @see NameResolverIntreface::getProjectRootPath()
     */
    public function getProjectRootPath($pathString)
    {
        $documentRoot = strstr(__DIR__, '/vendor/') ?
            preg_replace ('/^(.+)vendor.+$/iu', '\\1', __DIR__) :
            __DIR__ . '/../../';

        $documentRoot = realpath($documentRoot);

        return sprintf('%s%s', $documentRoot, $pathString);
    }

    /**
     * Вернет "dev"
     *
     * @return string
     */
    private function getStandPostfix()
    {
        return $this->getEnvVar(self::ENV_VAR_STAND_POSTFIX, '');
    }

    /**
     * Возвращает значение суффикса для внешнего домена
     *
     * @return string
     */
    private function getEnvExternalDomainSuffix()
    {
        return $this->getEnvVar(self::ENV_VAR_EXTERNAL_DOMAIN_SUFFIX, '');
    }

    /**
     *
     * @global type $argv
     * @return string
     */
    private function preparePlatformLocation()
    {
        global $argv;
        if ($this->platformLocation === null) {
            foreach ($argv as $arg) {
                if (strpos($arg, self::CLI_PLATFORM_LOCATION_PARAM_NAME) === 0) {
                    $this->platformLocation = strtolower(substr($arg, strlen(self::CLI_PLATFORM_LOCATION_PARAM_NAME), 2));
                }
            }
        }
        return $this->platformLocation;
    }

    /**
     * Возвращает доменный суффикс для conf-файлов (RU,EU)
     *
     * @return string
     */
    private function getTopLevelDomain()
    {
        if ($this->countrySuffix === null) {
            $this->countrySuffix = self::CLI_DEFAULT_DOMAIN;
            if (PHP_SAPI === 'cli') {
                $domain = $this->preparePlatformLocation();
                if ($domain !== null) {
                    $this->countrySuffix = $domain;
                } else {
                    $domain = $this->getEnvVar(self::ENV_VAR_DOMAIN_SUFFIX, null);
                    if ($domain !== null) {
                        $this->countrySuffix = $domain;
                    }
                }
            } else {

                if (!$host = $_SERVER['HTTP_HOST'] ?? '') {
                    $host = $_SERVER['SERVER_NAME'] ?? '';
                }

                $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));

                if ($host && '' !== preg_replace('/(?:^\[)?[a-zA-Z0-9-:\]_]+\.?/', '', $host)) {
                    throw new \UnexpectedValueException(sprintf('Invalid Host "%s"', $host));
                }

                if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $host, $regs) && array_key_exists($regs['domain'], self::VK_DOMAINS)) {
                    $this->countrySuffix = self::VK_DOMAINS[$regs['domain']];
                } elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) === true) {
                    $acceptLanguages = explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
                    if (isset($acceptLanguages[0]) === true) {
                        $suffix = $acceptLanguages[0];
                    }

                    if (in_array((string) $suffix, self::APPROVED_DOMAINS, true)) {
                        $this->countrySuffix = $suffix;
                    } else {
                        $this->countrySuffix = $this->getEnvVar(self::ENV_VAR_DOMAIN_SUFFIX, null);
                    }
                } else {
                    $this->countrySuffix = $this->getEnvVar(self::ENV_VAR_DOMAIN_SUFFIX, null);
                }
            }
        }

        return $this->countrySuffix;
    }

    /**
     * Возвращает значение ENV-переменной
     *
     * @param string $name
     * @param string|null $default
     *            Если переменная окружения false и $default === null, то получим Exception
     * @throws RuntimeException
     * @return string
     */
    private function getEnvVar($name, $default = null)
    {
        if (!isset($this->variables[$name])) {
            $this->variables[$name] = $this->driverEnv->getVariable($name);
            if ($this->variables[$name] === false) {
                if ($default === null) {
                    throw new RuntimeException(sprintf('Не задана переменная "%s"', $name));
                } else {
                    $this->variables[$name] = $default;
                }
            }
        }

        return $this->variables[$name];
    }
}
