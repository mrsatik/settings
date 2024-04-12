<?php

namespace mrsatik\SettingsTest;

use PHPUnit\Framework\TestCase;
use mrsatik\Settings\Env\EnvironmentNameResolver;
use mrsatik\Settings\Env\NameResolverIntreface;
use mrsatik\Settings\Config\Loader;
use mrsatik\Settings\Config\LoaderInterface;

class ConfigLoaderTest extends TestCase
{
    /**
     * @dataProvider nameResolverDataProvider
     */
    public function testNotExistConf(NameResolverIntreface $nameResolver)
    {
        $config = new Loader($nameResolver);
        $result = $config->getConfig(['dont_exist_conf.php']);

        $this->assertSame($result, []);
    }

    /**
     * @dataProvider nameResolverDataProvider
     */
    public function testLoadConfig(NameResolverIntreface $nameResolver)
    {
        /** @var $mock LoaderInterface */
        $mock = $this->getMockBuilder(Loader::class)
            ->setMethods(['getConfigFolder'])
            ->setConstructorArgs([$nameResolver])
            ->getMock();

        $mock->expects($this->once())
            ->method('getConfigFolder')
            ->will($this->returnValue("./tests/fixtures"));

        $response = $mock->getConfig(['foo.php', 'foo.dev.php']);

        $this->assertArrayHasKey('bar', $response);
        $this->assertSame($response['bar'], 'test-replace');
    }

    /**
     * @return NameResolverIntreface
     */
    public function nameResolverDataProvider()
    {
        $nameResolver = new EnvironmentNameResolver();
        return [
            [
                $nameResolver,
            ],
        ];
    }
}