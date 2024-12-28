<?php

namespace Ananke\Tests\Conditions;

use PHPUnit\Framework\TestCase;
use Ananke\Conditions\{
    CallableCondition,
    NorCondition,
    XorCondition
};

class LogicalConditionsTest extends TestCase
{
    private function createTestCondition(string $name, bool $result): CallableCondition
    {
        return new CallableCondition($name, fn() => $result);
    }

    public function testNorCondition(): void
    {
        // Test with all false conditions (should return true)
        $conditions = [
            $this->createTestCondition('false1', false),
            $this->createTestCondition('false2', false),
            $this->createTestCondition('false3', false)
        ];
        $nor = new NorCondition($conditions);
        $this->assertTrue($nor->evaluate(), 'NOR of all false conditions should be true');

        // Test with one true condition (should return false)
        $conditions = [
            $this->createTestCondition('false1', false),
            $this->createTestCondition('true', true),
            $this->createTestCondition('false2', false)
        ];
        $nor = new NorCondition($conditions);
        $this->assertFalse($nor->evaluate(), 'NOR with one true condition should be false');

        // Test with all true conditions (should return false)
        $conditions = [
            $this->createTestCondition('true1', true),
            $this->createTestCondition('true2', true)
        ];
        $nor = new NorCondition($conditions);
        $this->assertFalse($nor->evaluate(), 'NOR of all true conditions should be false');

        // Test with empty conditions (should return true)
        $nor = new NorCondition([]);
        $this->assertTrue($nor->evaluate(), 'NOR of empty conditions should be true');

        // Test name generation
        $conditions = [
            $this->createTestCondition('cond1', true),
            $this->createTestCondition('cond2', false)
        ];
        $nor = new NorCondition($conditions);
        $this->assertEquals('nor_cond1_cond2', $nor->getName());
    }

    public function testXorCondition(): void
    {
        // Test with one true condition (should return true)
        $conditions = [
            $this->createTestCondition('false1', false),
            $this->createTestCondition('true', true),
            $this->createTestCondition('false2', false)
        ];
        $xor = new XorCondition($conditions);
        $this->assertTrue($xor->evaluate(), 'XOR with exactly one true condition should be true');

        // Test with multiple true conditions (should return false)
        $conditions = [
            $this->createTestCondition('true1', true),
            $this->createTestCondition('false', false),
            $this->createTestCondition('true2', true)
        ];
        $xor = new XorCondition($conditions);
        $this->assertFalse($xor->evaluate(), 'XOR with multiple true conditions should be false');

        // Test with all false conditions (should return false)
        $conditions = [
            $this->createTestCondition('false1', false),
            $this->createTestCondition('false2', false)
        ];
        $xor = new XorCondition($conditions);
        $this->assertFalse($xor->evaluate(), 'XOR of all false conditions should be false');

        // Test with empty conditions (should return false)
        $xor = new XorCondition([]);
        $this->assertFalse($xor->evaluate(), 'XOR of empty conditions should be false');

        // Test name generation
        $conditions = [
            $this->createTestCondition('cond1', true),
            $this->createTestCondition('cond2', false)
        ];
        $xor = new XorCondition($conditions);
        $this->assertEquals('xor_cond1_cond2', $xor->getName());
    }
}
