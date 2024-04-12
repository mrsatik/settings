<?php
namespace mrsatik\Settings\Env;

use mrsatik\Servers\ServersCollectionInterface;

interface NameResolverIntreface
{
    /**
     *
     * @param string $name
     * @return string
     */
    public function getBaseConfigName($name);

    /**
     *
     * @param string $name
     * @return string
     */
    public function getLocalConfigName($name);

    /**
     *
     * @param string $name
     * @return string
     */
    public function getDomainConfigName($name);

    /**
     *
     * @param string $name
     * @return string
     */
    public function getLocalDomainConfigName($name);

    /**
     *
     * @param string $name
     * @return ServersCollectionInterface
     */
    public function getService($name);

    /**
     *
     * @param string $subDomain
     * @return string
     */
    public function getDomainName($subDomain);
    /**
     *
     * @param string $pathString
     * @return string
     */
    public function getProjectRootPath($pathString);

    /**
     * Взять ключ по данным
     * @param string $name
     * @return static
     */
    public function getKey($name): string;
}
