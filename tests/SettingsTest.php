<?php

namespace mrsatik\SettingsTest;

use PHPUnit\Framework\TestCase;
use mrsatik\Settings\Env\EnvironmentNameResolver;
use mrsatik\Settings\Config\Loader;
use mrsatik\Settings\Config\LoaderInterface;
use mrsatik\Settings\Settings;
use mrsatik\Settings\SettingsInterface;
use mrsatik\Settings\Value\Modifier;
use mrsatik\Settings\Exception\InvalidKeyException;
use mrsatik\Servers\ServersCollectionInterface;

class SettingsTest extends TestCase
{
    /**
     * @dataProvider settingsDataProvider
     */
    public function testSettingsGetException(SettingsInterface $settings)
    {
        $this->expectException(InvalidKeyException::class);
        $settings->getValue('');
    }

    /**
     * @dataProvider settingsDataProvider
     */
    public function testSettingsGetValue(SettingsInterface $settings)
    {
        $setting = $settings->getValue('foo.bar');
        $this->assertEquals($setting, 'test-replace');

        $setting = $settings->getValue('foo');
        $this->assertArrayHasKey('bar', $setting);

        $setting = $settings->getValue('foo');
        $this->assertEquals($setting['bar'], 'test-replace');
        
        $setting = $settings->getValue('foo.foo');
        $this->assertArrayHasKey('params', $setting);
        $this->assertInstanceOf(ServersCollectionInterface::class, $setting['params']);
    }


    /**
     * @dataProvider settingsDataProvider
     */
    public function testSettingsGetNullValue(SettingsInterface $settings)
    {
        $setting = $settings->getNullableValue('');
        $this->assertNull($setting);

        $setting = $settings->getNullableValue('foo.bar');
        $this->assertNotNull($setting);
    }

    /**
     * @dataProvider settingsDataProvider
     */
    public function testSettingsGetMask(SettingsInterface $settings)
    {
        $setting = $settings->getValueByMask('foo.bar2.tests.*.host', []);
        $this->assertNull($setting);

        $setting = $settings->getValueByMask('foo.bar2.tests.*.host', ['host' => 'test1']);
        $this->assertEquals($setting, 'test1');
    }

    /**
     * @return SettingsInterface
     */
    public function settingsDataProvider()
    {
        $nameResolver = new EnvironmentNameResolver();

        /** @var $mock LoaderInterface */
        $mock = $this->getMockBuilder(Loader::class)
            ->setMethods(['getConfigFolder'])
            ->setConstructorArgs([$nameResolver])
            ->getMock();

        $mock->expects($this->once())
            ->method('getConfigFolder')
            ->will($this->returnValue("./tests/fixtures"));

        $settings = Settings::getInstance();
        $reflectionSettings = new \ReflectionClass($settings);

        $propertyNameResolver = $reflectionSettings->getProperty('environmentNameResolver');
        $propertyNameResolver->setAccessible(true);
        $propertyNameResolver->setValue($settings, $nameResolver);

        $propertyConfigLoader = $reflectionSettings->getProperty('configLoader');
        $propertyConfigLoader->setAccessible(true);
        $propertyConfigLoader->setValue($settings, $mock);

        $propertyValueModifier = $reflectionSettings->getProperty('valueModifier');
        $propertyValueModifier->setAccessible(true);
        $propertyValueModifier->setValue($settings, new Modifier($nameResolver));

        return [[$settings]];
    }
}