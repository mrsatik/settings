<?php

namespace mrsatik\SettingsTest;

use PHPUnit\Framework\TestCase;
use mrsatik\Settings\Env\EnvironmentNameResolver;
use mrsatik\Settings\Value\Modifier;
use mrsatik\Settings\Value\ModifierInterface;
use mrsatik\Settings\Driver\File;
use mrsatik\Settings\Driver\FileInterface;
use mrsatik\Settings\Driver\Builder;
use mrsatik\Servers\ServerInterface;
use mrsatik\Servers\ServersCollectionInterface;

class ValueModifierTest extends TestCase
{
    /**
     * @dataProvider arrayReplacerDataProvider
     */
    public function testValueReplacer(ModifierInterface $arrayReplacer, ?bool $singleQuote = false)
    {
        $data['bar'] = 'foo';
        $data = $arrayReplacer->replaceRecursive($data);
        $this->assertSame($data['bar'], 'foo');

        $data['bar'] = 'servicekey:textkey';
        $data = $arrayReplacer->replaceRecursive($data);
        $this->assertArrayHasKey('bar', $data);
        $this->assertSame('keyvalue', $data['bar']);

        if ($singleQuote === true) {
            $data['bar'] = 'servicekey:textkeyquote';
            $data = $arrayReplacer->replaceRecursive($data);
            $this->assertArrayHasKey('bar', $data);
            $this->assertSame('keyvalue', $data['bar']);
        }

        $data['bar'] = 'service:foo';
        $data = $arrayReplacer->replaceRecursive($data);
        $this->assertArrayHasKey('bar', $data);
        $this->assertInstanceOf(ServersCollectionInterface::class, $data['bar']);

        /** @var ServerInterface $currentConfig */
        $currentConfig = $data['bar']->current();
        $this->assertSame($currentConfig->getHost(), 'bar');
        $this->assertNull($currentConfig->getPort());
        $this->assertNull($currentConfig->getPassword());
        $this->assertNull($currentConfig->getUser());

        $data['bar'] = 'service:bar';
        $data = $arrayReplacer->replaceRecursive($data);
        $this->assertArrayHasKey('bar', $data);
        $this->assertInstanceOf(ServersCollectionInterface::class, $data['bar']);
        /** @var ServerInterface $currentConfig */
        $currentConfig = $data['bar']->current();
        $this->assertSame($currentConfig->getHost(), 'foo');
        $this->assertSame($currentConfig->getPort(), 'bar');
        $this->assertSame($currentConfig->getPassword(), 'test');
        $this->assertSame($currentConfig->getUser(), 'user');

        $data['bar'] = 'service:test';
        $data = $arrayReplacer->replaceRecursive($data);
        $this->assertCount(1, $data['bar']);
        /** @var ServerInterface $currentConfig */
        $currentConfig = $data['bar']->current();
        $this->assertSame($currentConfig->getHost(), 'foo');
        $this->assertSame($currentConfig->getPort(), 'bar');
        $this->assertNull($currentConfig->getPassword());
        $this->assertNull($currentConfig->getUser());

    }


    /**
     * @return ModifierInterface
     */
    public function arrayReplacerDataProvider()
    {
        /** @var $fileReader FileInterface */
        $fileReader = $this->getMockBuilder(File::class)
            ->setMethods(['getFileName'])
            ->getMock();

        $fileReader->expects($this->once())
            ->method('getFileName')
            ->will($this->returnValue('.env_ru_test3'));

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

        $arrayReplacer = new Modifier($nameResolver);

        /** @var $fileReader FileInterface */
        $fileReaderQuotes = $this->getMockBuilder(File::class)
            ->setMethods(['getFileName'])
            ->getMock();

        $fileReaderQuotes->expects($this->any())
            ->method('getFileName')
            ->will($this->returnValue('.env_ru_test4'));

        $builderObj = new Builder();
        $reflectionBuilder = new \ReflectionClass($builderObj);
        $driverProperty = $reflectionBuilder->getProperty('driver');
        $driverProperty->setAccessible(true);
        $driverProperty->setValue($builderObj, $fileReaderQuotes);

        $nameResolverQuotes = new EnvironmentNameResolver();

        $reflection = new \ReflectionClass($nameResolverQuotes);
        $property = $reflection->getProperty('driverEnv');
        $property->setAccessible(true);
        $property->setValue($nameResolverQuotes, $builderObj);

        $arrayReplacerQuotes = new Modifier($nameResolverQuotes);

        return [[$arrayReplacer], [$arrayReplacerQuotes, true]];
    }
}
