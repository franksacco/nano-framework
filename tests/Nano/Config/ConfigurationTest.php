<?php
/**
 * Nano Framework
 *
 * @package   Nano
 * @author    Francesco Saccani <saccani.francesco@gmail.com>
 * @copyright Copyright (c) 2019 Francesco Saccani
 * @version   1.0
 */

declare(strict_types=1);

namespace Nano\Config;

use Nano\Config\InvalidPrefixException;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    /**
     * @dataProvider hasProvider
     * @param $key
     * @param $expected
     */
    public function testHas($key, $expected)
    {
        $config = new Configuration($this->createDummyData());

        $this->assertSame($expected, $config->has($key));
    }

    public function hasProvider()
    {
        return [
            ['foo.bar', true],
            ['foo.bar.something', false],
            ['baz', true],
            ['bar', false],
            ['qux.nested.more-nested.item0', true],
            ['qux.nested.more-nested.item3', false]
        ];
    }

    /**
     * @dataProvider getProvider
     * @param $key
     * @param $default
     * @param $expected
     */
    public function testGet($key, $default, $expected)
    {
        $config = new Configuration($this->createDummyData());

        $this->assertSame($expected, $config->get($key, $default));
    }

    public function getProvider()
    {
        return [
            ['foo.bar', null, 'something'],
            ['foo.bar.something', 'default', 'default'],
            ['baz', null, true],
            ['bar', null, null],
            ['qux.nested', null, ['more-nested' => ['item0' => 'value0', 'item1' => 'value1', 'item2' => 'value2']]],
            ['qux.nested.more-nested', null, ['item0' => 'value0', 'item1' => 'value1', 'item2' => 'value2']],
            ['qux.nested.more-nested.item0', null, 'value0']
        ];
    }

    public function testAll()
    {
        $data = $this->createDummyData();
        $config = new Configuration($data);

        $this->assertSame($data, $config->all());
    }

    public function testPrefix()
    {
        $config = new Configuration($this->createDummyData());
        $prefixed = $config->withPrefix('qux');

        $this->assertSame('qux', $prefixed->getPrefix());
        $this->assertSame([
            'nested' => [
                'more-nested' => [
                    'item0' => 'value0',
                    'item1' => 'value1',
                    'item2' => 'value2'
                ]
            ]
        ], $prefixed->all());
        $this->assertSame(true, $prefixed->has('nested.more-nested.item1'));
        $this->assertSame('value0', $prefixed->get('nested.more-nested.item0'));
    }

    public function testInvalidPrefix()
    {
        $config = new Configuration($this->createDummyData());

        $this->expectException(InvalidPrefixException::class);
        $this->expectExceptionMessage('The prefix does not refer to an item of type array');

        $config->withPrefix('foo.bar');
    }

    private function createDummyData()
    {
        return [
            'foo' => [
                'bar' => 'something'
            ],
            'baz' => true,
            'qux' => [
                'nested' => [
                    'more-nested' => [
                        'item0' => 'value0',
                        'item1' => 'value1',
                        'item2' => 'value2'
                    ]
                ]
            ]
        ];
    }
}