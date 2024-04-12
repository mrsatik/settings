<?php

namespace mrsatik\SettingsTest;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use mrsatik\Settings\Env\EnvironmentNameResolver;
use mrsatik\Settings\Env\NameResolverIntreface;
use mrsatik\Settings\Driver\Builder;
use mrsatik\Settings\Driver\File;
use mrsatik\Settings\Driver\FileInterface;
use mrsatik\Servers\ServersCollectionInterface;
use mrsatik\Servers\ServerInterface;

class NameResolverTest extends TestCase
{
    /**
     * @dataProvider nameResolverDataProvider
     */
    public function testEnv(NameResolverIntreface $nameResolver)
    {
        $this->assertSame($nameResolver->getBaseConfigName('foo'), 'foo.php');
        $this->assertSame($nameResolver->getLocalConfigName('foo'), 'foo.dev.php');
        $this->assertSame($nameResolver->getDomainConfigName('foo'), 'foo.ru.php');
        $this->assertSame($nameResolver->getLocalDomainConfigName('foo'), 'foo.ru.dev.php');

        $this->assertSame($nameResolver->getDomainName('pvpmail'), 'pvpmail.ru');

        $this->assertSame($nameResolver->getProjectRootPath('/tests'), __DIR__);
    }

    /**
     * @dataProvider serviceResolverDataProvider
     */
    public function testServiceName(
        NameResolverIntreface $nameResolver,
        NameResolverIntreface $nameBlankResolver,
        NameResolverIntreface $nameManyResolver
    ) {

        /** @var ServersCollectionInterface $bar */
        $bar = $nameResolver->getService('test');
        $this->assertCount(1, $bar);

        /** @var ServerInterface $currentConfig */
        $currentConfig = $bar->current();
        $this->assertSame($currentConfig->getHost(), 'foo');
        $this->assertSame($currentConfig->getPort(), 'bar');
        $this->assertSame($currentConfig->getPassword(), 'test');
        $this->assertSame($currentConfig->getUser(), 'user');

        /** @var ServersCollectionInterface $bar */
        $bar = $nameManyResolver->getService('test');
        $this->assertCount(2, $bar);
        /** @var ServerInterface $service */
        foreach ($bar as $k => $service) {
            $this->assertSame($service->getHost(), 'foo' . $k);
            $this->assertSame($service->getPort(), 'bar' . $k);
            $this->assertSame($service->getPassword(), 'test' . $k);
            $this->assertSame($service->getUser(), 'user' . $k);
        }

    }

    /**
     * @dataProvider serviceResolverDataProvider
     */
    public function testInvalidException(
        NameResolverIntreface $nameResolver,
        NameResolverIntreface $nameBlankResolver,
        NameResolverIntreface $nameManyResolver
    ) {
        $this->expectException(InvalidArgumentException::class);
        /** @var ServersCollectionInterface $bar */
        $bar = $nameBlankResolver->getService('test');
    }

    /**
     *
     * @return NameResolverIntreface
     */
    public function nameResolverDataProvider()
    {
        $nameResolver = new EnvironmentNameResolver();
        return [[$nameResolver]];
    }

    /**
     *
     * @return NameResolverIntreface
     */
    public function serviceResolverDataProvider()
    {
        /** @var $fileReader FileInterface */
        $fileReader = $this->getMockBuilder(File::class)
            ->setMethods(['getFileName'])
            ->getMock();

        $fileReader->expects($this->once())
            ->method('getFileName')
            ->will($this->returnValue('.env_ru_test0'));

        $builderObj = new Builder();
        $reflectionBuilder = new \ReflectionClass($builderObj);
        $driverProperty = $reflectionBuilder->getProperty('driver');
        $driverProperty->setAccessible(true);
        $driverProperty->setValue($builderObj, $fileReader);

        $nameResolver = new EnvironmentNameResolver();
        $reflection = new \ReflectionClass($nameResolver);
        $property = $reflection->getProperty('driverEnv');
        $property->setAccessible(true);
        $property->setValue($nameResolver, $builderObj);

        /** @var $fileReader FileInterface */
        $fileReader = $this->getMockBuilder(File::class)
            ->setMethods(['getFileName'])
            ->getMock();

        $fileReader->expects($this->once())
            ->method('getFileName')
            ->will($this->returnValue('.env_ru_test1'));

        $builderObj = new Builder();
        $reflectionBuilder = new \ReflectionClass($builderObj);
        $driverProperty = $reflectionBuilder->getProperty('driver');
        $driverProperty->setAccessible(true);
        $driverProperty->setValue($builderObj, $fileReader);

        $nameResolverHostOnly = new EnvironmentNameResolver();
        $reflection = new \ReflectionClass($nameResolverHostOnly);
        $propertyBlank = $reflection->getProperty('driverEnv');
        $propertyBlank->setAccessible(true);
        $propertyBlank->setValue($nameResolverHostOnly, $builderObj);

        /** @var $fileReader FileInterface */
        $fileReader = $this->getMockBuilder(File::class)
            ->setMethods(['getFileName'])
            ->getMock();

        $fileReader->expects($this->once())
            ->method('getFileName')
            ->will($this->returnValue('.env_ru_test2'));

        $builderObj = new Builder();
        $reflectionBuilder = new \ReflectionClass($builderObj);
        $driverProperty = $reflectionBuilder->getProperty('driver');
        $driverProperty->setAccessible(true);
        $driverProperty->setValue($builderObj, $fileReader);

        $nameResolverTest = new EnvironmentNameResolver();
        $reflection = new \ReflectionClass($nameResolverTest);
        $propertyFoo = $reflection->getProperty('driverEnv');
        $propertyFoo->setAccessible(true);
        $propertyFoo->setValue($nameResolverTest, $builderObj);

        return [
            [
                $nameResolver,
                $nameResolverHostOnly,
                $nameResolverTest
            ],
        ];
    }
}