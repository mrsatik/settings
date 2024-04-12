<?php
namespace mrsatik\Settings\Config;

use mrsatik\Settings\Env\NameResolverIntreface;
use mrsatik\Settings\Config\LoaderInterface;

class Loader implements LoaderInterface
{
    /**
     * Путь к папке conf-файлов относительно общего корня
     *
     * @var string
     */
    const CONF_FOLDER = 'config';

    /**
     * Путь до conf-файлов
     *
     * @var string
     */
    private $pathToConfigFiles;

    private $configs = [];

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
     * @see LoaderInterface::getConfig()
     */
    public function getConfig(array $files): array
    {
        $result = [];
        foreach ((array) $files as $file) {
            $config = $this->loadConfig($this->getConfigPath($file));
            if (is_array($config) && $config !== []) {
                $result = array_replace_recursive($result, $config);
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     * @see LoaderInterface::getConfigFolder()
     */
    public function getConfigFolder(): string
    {
        return self::CONF_FOLDER;
    }

    private function loadConfig($config_path)
    {
        $config = null;

        if (isset($this->configs[$config_path])) {
            $config = $this->configs[$config_path];
        } else if (file_exists($config_path)) {
            $config = require $config_path;
            $this->configs[$config_path] = $config;
        }

        return $config;
    }

    /**
     * Возвращает путь до conf-файла
     *
     * @param string $name имя файла
     *
     * @return string
     */
    private function getConfigPath($name): string
    {
        if ($this->pathToConfigFiles === null) {
            $projectRoot = $this->environmentNameResolver->getProjectRootPath('');
            $this->pathToConfigFiles = $projectRoot . DIRECTORY_SEPARATOR . $this->getConfigFolder() . DIRECTORY_SEPARATOR;
        }
        return $this->pathToConfigFiles . $name;
    }
}