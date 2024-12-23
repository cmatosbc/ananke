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

/**
 * Test class for advanced condition scenarios and edge cases
 */
class AdvancedConditionsTest extends TestCase
{
    private function createTestCondition(string $name, bool $result): CallableCondition
    {
        return new CallableCondition($name, fn() => $result);
    }

    public function testNestedConditions(): void
    {
        // Create a deeply nested condition structure
        // ((A AND B) OR (NOT C)) AND (D OR (NOT E))
        $condA = $this->createTestCondition('A', true);
        $condB = $this->createTestCondition('B', true);
        $condC = $this->createTestCondition('C', false);
        $condD = $this->createTestCondition('D', false);
        $condE = $this->createTestCondition('E', true);

        $ab = new AndCondition([$condA, $condB]);
        $notC = new NotCondition($condC);
        $leftSide = new OrCondition([$ab, $notC]);

        $notE = new NotCondition($condE);
        $rightSide = new OrCondition([$condD, $notE]);

        $final = new AndCondition([$leftSide, $rightSide]);

        $this->assertFalse($final->evaluate());
        $this->assertStringContainsString('and_or_and_A_B_not_C_or_D_not_E', $final->getName());
    }

    public function testCacheWithChangingConditions(): void
    {
        $results = [true, false, true];
        $index = 0;
        
        $condition = new class($results, $index) implements \Ananke\Conditions\ConditionInterface {
            private array $results;
            private int $index;

            public function __construct(array &$results, int &$index)
            {
                $this->results = &$results;
                $this->index = &$index;
            }

            public function evaluate(): bool
            {
                return $this->results[$this->index++ % count($this->results)];
            }

            public function getName(): string
            {
                return 'changing';
            }
        };

        $cached = new CachedCondition($condition, 1);

        // First evaluation should be true
        $this->assertTrue($cached->evaluate());
        
        // Should still be true (cached) even though underlying condition would return false
        $this->assertTrue($cached->evaluate());

        // Wait for cache to expire
        sleep(2);

        // Should now be false (second evaluation)
        $this->assertFalse($cached->evaluate());
    }

    public function testDoubleNegation(): void
    {
        $base = $this->createTestCondition('base', true);
        $not = new NotCondition($base);
        $doubleNot = new NotCondition($not);

        $this->assertTrue($doubleNot->evaluate());
        $this->assertEquals('not_not_base', $doubleNot->getName());
    }

    public function testEmptyComposites(): void
    {
        $emptyAnd = new AndCondition([]);
        $emptyOr = new OrCondition([]);

        // Empty AND should return true (all conditions are met)
        $this->assertTrue($emptyAnd->evaluate());
        // Empty OR should return false (no conditions are met)
        $this->assertFalse($emptyOr->evaluate());
    }

    public function testCacheClearBehavior(): void
    {
        $count = 0;
        $condition = new class($count) implements \Ananke\Conditions\ConditionInterface {
            private int $count;

            public function __construct(int &$count)
            {
                $this->count = &$count;
            }

            public function evaluate(): bool
            {
                $this->count++;
                return true;
            }

            public function getName(): string
            {
                return 'counter';
            }
        };

        $cached = new CachedCondition($condition, 10);

        // First evaluation
        $this->assertTrue($cached->evaluate());
        $this->assertEquals(1, $count);

        // Cached evaluation
        $this->assertTrue($cached->evaluate());
        $this->assertEquals(1, $count);

        // Clear cache and evaluate again
        $cached->clearCache();
        $this->assertTrue($cached->evaluate());
        $this->assertEquals(2, $count);
    }

    public function testComplexCaching(): void
    {
        $base1 = $this->createTestCondition('base1', true);
        $base2 = $this->createTestCondition('base2', false);

        $not = new NotCondition($base2);
        $and = new AndCondition([$base1, $not]);

        // Cache the complex condition
        $cached = new CachedCondition($and, 1);

        $this->assertTrue($cached->evaluate());
        $this->assertTrue($cached->evaluate()); // Should use cache

        sleep(2); // Wait for cache to expire

        $this->assertTrue($cached->evaluate()); // Should re-evaluate
    }

    public function testShortCircuitEvaluation(): void
    {
        $evaluated = [];
        
        $conditions = [
            new class($evaluated) implements \Ananke\Conditions\ConditionInterface {
                private array $evaluated;

                public function __construct(array &$evaluated)
                {
                    $this->evaluated = &$evaluated;
                }

                public function evaluate(): bool
                {
                    $this->evaluated[] = 'first';
                    return false;
                }

                public function getName(): string
                {
                    return 'first';
                }
            },
            new class($evaluated) implements \Ananke\Conditions\ConditionInterface {
                private array $evaluated;

                public function __construct(array &$evaluated)
                {
                    $this->evaluated = &$evaluated;
                }

                public function evaluate(): bool
                {
                    $this->evaluated[] = 'second';
                    return true;
                }

                public function getName(): string
                {
                    return 'second';
                }
            }
        ];

        // Test AND short-circuit
        $and = new AndCondition($conditions);
        $and->evaluate();
        $this->assertEquals(['first'], $evaluated, 'AND should stop after first false');

        // Reset evaluated array
        $evaluated = [];

        // Test OR short-circuit
        $or = new OrCondition($conditions);
        $or->evaluate();
        $this->assertEquals(['first', 'second'], $evaluated, 'OR should continue until true');
    }
}
