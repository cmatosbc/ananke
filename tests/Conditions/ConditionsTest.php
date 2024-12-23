<?php

namespace Ananke\Tests\Conditions;

use PHPUnit\Framework\TestCase;
use Ananke\Conditions\{
    CallableCondition,
    NotCondition,
    CachedCondition,
    AndCondition,
    OrCondition
};

class ConditionsTest extends TestCase
{
    public function testCallableCondition(): void
    {
        $condition = new CallableCondition('test', fn() => true);
        $this->assertTrue($condition->evaluate());
        $this->assertEquals('test', $condition->getName());

        $condition = new CallableCondition('test', fn() => false);
        $this->assertFalse($condition->evaluate());
    }

    public function testNotCondition(): void
    {
        $base = new CallableCondition('base', fn() => true);
        $not = new NotCondition($base);

        $this->assertFalse($not->evaluate());
        $this->assertEquals('not_base', $not->getName());
    }

    public function testCachedCondition(): void
    {
        $count = 0;
        $base = new CallableCondition('base', function() use (&$count) {
            $count++;
            return true;
        });

        $cached = new CachedCondition($base, 1);

        // First call should evaluate
        $this->assertTrue($cached->evaluate());
        $this->assertEquals(1, $count);

        // Second call should use cache
        $this->assertTrue($cached->evaluate());
        $this->assertEquals(1, $count);

        // Wait for cache to expire
        sleep(2);

        // Should evaluate again
        $this->assertTrue($cached->evaluate());
        $this->assertEquals(2, $count);
    }

    public function testAndCondition(): void
    {
        $conditions = [
            new CallableCondition('true1', fn() => true),
            new CallableCondition('true2', fn() => true)
        ];
        $and = new AndCondition($conditions);
        $this->assertTrue($and->evaluate());

        $conditions[] = new CallableCondition('false', fn() => false);
        $and = new AndCondition($conditions);
        $this->assertFalse($and->evaluate());
    }

    public function testOrCondition(): void
    {
        $conditions = [
            new CallableCondition('false1', fn() => false),
            new CallableCondition('true', fn() => true),
            new CallableCondition('false2', fn() => false)
        ];
        $or = new OrCondition($conditions);
        $this->assertTrue($or->evaluate());

        $conditions = [
            new CallableCondition('false1', fn() => false),
            new CallableCondition('false2', fn() => false)
        ];
        $or = new OrCondition($conditions);
        $this->assertFalse($or->evaluate());
    }

    public function testComplexConditions(): void
    {
        // Create a complex condition: (A AND B) OR (NOT C)
        $a = new CallableCondition('A', fn() => true);
        $b = new CallableCondition('B', fn() => true);
        $c = new CallableCondition('C', fn() => false);

        $ab = new AndCondition([$a, $b]);
        $notC = new NotCondition($c);
        $complex = new OrCondition([$ab, $notC]);

        $this->assertTrue($complex->evaluate());
        $this->assertEquals('or_and_A_B_not_C', $complex->getName());
    }
}
