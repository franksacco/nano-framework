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

use PHPUnit\Framework\TestCase;

class ArrayDotNotationTraitTest extends TestCase
{
    /**
     * @dataProvider hasItemProvider
     * @param $key
     * @param $expected
     */
    public function testHasItem($key, $expected)
    {
        $array = $this->createDummyData();
        $trait = new ClassWithArrayDotNotationTrait();

        $this->assertSame($expected, $trait->hasItem($array, $key));
    }

    public function hasItemProvider()
    {
        return [
            ['foo.bar', true],
            ['foo.bar.something', false],
            ['foo.bar.baz.qux', false],
            ['baz', true],
            ['bar', false],
            ['qux.nested.more-nested.item0', true],
            ['qux.nested.more-nested.item3', false]
        ];
    }

    /**
     * @dataProvider getItemProvider
     * @param $key
     * @param $default
     * @param $expected
     */
    public function testGetItem($key, $default, $expected)
    {
        $array = $this->createDummyData();
        $trait = new ClassWithArrayDotNotationTrait();

        $this->assertSame($expected, $trait->getItem($array, $key, $default));
    }

    public function getItemProvider()
    {
        return [
            ['foo.bar', null, 'something'],
            ['foo.bar.something', 'default', 'default'],
            ['foo.bar.baz.qux', 'default', 'default'],
            ['baz', null, true],
            ['bar', null, null],
            ['qux.nested', null, ['more-nested' => ['item0' => 'value0', 'item1' => 'value1', 'item2' => 'value2']]],
            ['qux.nested.more-nested', null, ['item0' => 'value0', 'item1' => 'value1', 'item2' => 'value2']],
            ['qux.nested.more-nested.item0', null, 'value0']
        ];
    }

    /**
     * @dataProvider setItemProvider
     * @param $key
     * @param $value
     * @param $expected
     */
    public function testSetItem($key, $value, $expected)
    {
        $array = $this->createDummyData();
        $trait = new ClassWithArrayDotNotationTrait();
        $trait->setItem($array, $key, $value);

        $this->assertSame($expected, $array);
    }

    public function setItemProvider()
    {
        $array = $this->createDummyData();

        $expected0 = $array;
        $expected0['foo']['bar'] = 'something-else';

        $expected1 = $array;
        $expected1['baz'] = ['new-item' => true];

        $expected2 = $array;
        $expected2['qux']['nested']['more-nested'] = null;

        $expected3 = $array;
        $expected3['qux'] = ['item0' => 'value0', 'item1' => 'value1', 'item2' => 'value2'];

        $expected4 = $array;
        $expected4['foo']['baz']['qux'] = 'some-value';

        return [
            ['foo.bar', 'something-else', $expected0],
            ['baz', ['new-item' => true], $expected1],
            ['qux.nested.more-nested', null, $expected2],
            ['qux', ['item0' => 'value0', 'item1' => 'value1', 'item2' => 'value2'], $expected3],
            ['foo.baz.qux', 'some-value', $expected4]
        ];
    }

    public function testSetInvalidItem()
    {
        $array = $this->createDummyData();
        $trait = new ClassWithArrayDotNotationTrait();

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Setting value for a non-array item');

        $trait->setItem($array, 'foo.bar.something', 'something-else');
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

class ClassWithArrayDotNotationTrait
{
    use ArrayDotNotationTrait;
}