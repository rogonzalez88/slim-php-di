<?php

/*
 * slim-php-di (https://github.com/juliangut/slim-php-di).
 * Slim Framework PHP-DI container implementation.
 *
 * @license BSD-3-Clause
 * @link https://github.com/juliangut/slim-php-di
 * @author Julián Gutiérrez <juliangut@gmail.com>
 */

declare(strict_types=1);

namespace Jgut\Slim\PHPDI\Tests;

use Jgut\Slim\PHPDI\CallableStrategy;
use Jgut\Slim\PHPDI\Configuration;
use Jgut\Slim\PHPDI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Handlers\Error;
use Slim\Handlers\NotAllowed;
use Slim\Handlers\NotFound;
use Slim\Handlers\PhpError;
use Slim\Http\Environment;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouterInterface;
use Slim\Router;

/**
 * Container tests.
 */
class ContainerTest extends TestCase
{
    /**
     * @var \Jgut\Slim\PHPDI\Container
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->container = ContainerBuilder::build(new Configuration());
    }

    /**
     * @expectedException \Slim\Exception\ContainerValueNotFoundException
     * @expectedExceptionMessage No entry or class found for 'baz'
     */
    public function testGetNonExistent()
    {
        self::assertFalse($this->container->has('baz'));
        $this->container['baz'];
    }

    /**
     * @expectedException \Slim\Exception\ContainerValueNotFoundException
     * @expectedExceptionMessage Setting "baz" not found
     */
    public function testGetNonExistentSetting()
    {
        self::assertFalse($this->container->has('settings.baz'));
        $this->container['settings.baz'];
    }

    public function testSettingsAccess()
    {
        $settings = [
            'foo' => [
                'bar' => [
                    'baz' => 'found!',
                ],
            ],
            'foo.bar' => 'bang!',
        ];
        $this->container->set('settings', $settings);

        self::assertTrue($this->container->has('settings.foo'));
        self::assertEquals(['bar' => ['baz' => 'found!']], $this->container->get('settings.foo'));

        self::assertTrue($this->container->has('settings.foo.bar'));
        self::assertEquals('bang!', $this->container->get('settings.foo.bar'));

        self::assertTrue($this->container->has('settings.foo.bar.baz'));
        self::assertEquals('found!', $this->container->get('settings.foo.bar.baz'));
    }

    /**
     * @expectedException \Slim\Exception\ContainerValueNotFoundException
     * @expectedExceptionMessage No entry or class found for 'none'
     */
    public function testGetWrong()
    {
        $this->container->get('none');
    }

    /**
     * @expectedException \Slim\Exception\ContainerException
     * @expectedExceptionMessage Entry "foo" cannot be resolved: the class doesn't exist
     */
    public function testUnresolvable()
    {
        $configuration = new Configuration([
            'definitions' => [
                ['foo' => \DI\create('\\Foo\\Bar')],
            ],
        ]);

        $container = ContainerBuilder::build($configuration);

        $container->get('foo');
    }

    public function testSetterGetter()
    {
        $this->container['foo'] = 'bar';
        self::assertTrue($this->container->has('foo'));
        self::assertEquals('bar', $this->container->get('foo'));

        $this->container['bar'] = 'baz';
        self::assertTrue(isset($this->container['bar']));
        self::assertEquals('baz', $this->container['bar']);

        $this->container['baz'] = 'bam';
        self::assertTrue(isset($this->container->baz));
        self::assertEquals('bam', $this->container->baz);

        $this->container->bam = 'foo';
        self::assertTrue($this->container->has('bam'));
        self::assertEquals('foo', $this->container->bam);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage It is not possible to unset container definitions
     */
    public function testUnset()
    {
        unset($this->container->foo);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage It is not possible to unset container definitions
     */
    public function testUnsetArray()
    {
        unset($this->container['foo']);
    }

    public function testDefaultServices()
    {
        self::assertTrue($this->container->has('settings'));
        self::assertInternalType('array', $this->container->get('settings'));
        self::assertEquals('1.1', $this->container->get('settings.httpVersion'));
        self::assertEquals(4096, $this->container->get('settings.responseChunkSize'));
        self::assertEquals('append', $this->container->get('settings.outputBuffering'));
        self::assertEquals(false, $this->container->get('settings.determineRouteBeforeAppMiddleware'));
        self::assertEquals(false, $this->container->get('settings.displayErrorDetails'));
        self::assertEquals(true, $this->container->get('settings.addContentLengthHeader'));
        self::assertEquals(false, $this->container->get('settings.routerCacheFile'));

        self::assertTrue($this->container->has('environment'));
        self::assertInstanceOf(Environment::class, $this->container->get('environment'));

        self::assertTrue($this->container->has('request'));
        self::assertInstanceOf(ServerRequestInterface::class, $this->container->get('request'));

        self::assertTrue($this->container->has('response'));
        self::assertInstanceOf(ResponseInterface::class, $this->container->get('response'));

        self::assertTrue($this->container->has('router'));
        self::assertInstanceOf(RouterInterface::class, $this->container->get('router'));

        self::assertTrue($this->container->has('phpErrorHandler'));
        self::assertInstanceOf(PhpError::class, $this->container->get('phpErrorHandler'));

        self::assertTrue($this->container->has('errorHandler'));
        self::assertInstanceOf(Error::class, $this->container->get('errorHandler'));

        self::assertTrue($this->container->has('notFoundHandler'));
        self::assertInstanceOf(NotFound::class, $this->container->get('notFoundHandler'));

        self::assertTrue($this->container->has('notAllowedHandler'));
        self::assertInstanceOf(NotAllowed::class, $this->container->get('notAllowedHandler'));

        self::assertTrue($this->container->has('foundHandler'));
        self::assertInstanceOf(CallableStrategy::class, $this->container['foundHandler']);

        self::assertTrue($this->container->has('callableResolver'));
        self::assertInstanceOf(CallableResolverInterface::class, $this->container->get('callableResolver'));

        self::assertTrue($this->container->has(Configuration::class));
        self::assertInstanceOf(Configuration::class, $this->container->get(Configuration::class));

        self::assertTrue($this->container->has(ContainerInterface::class));
        self::assertEquals($this->container, $this->container->get(ContainerInterface::class));
    }
}
