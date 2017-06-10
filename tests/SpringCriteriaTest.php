<?php

namespace Minimalcode\Search\Tests;

use Minimalcode\Search\Criteria;
use Minimalcode\Search\CriteriaBaseTest;

class SpringCriteriaTest extends CriteriaBaseTest
{
    public function testIs()
    {
        $criteria = Criteria::where('field_1')->is('is');

        self::assertEquals('field_1', $this->getField($criteria));
        self::assertEquals('field_1:is', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testMultipleIs()
    {
        $criteria = Criteria::where('field_1')->is('is')->is('another is');

        self::assertEquals('field_1', $this->getField($criteria));
        self::assertEquals('field_1:(is "another is")', $criteria->getQuery());
        self::assertCount(2, $this->getPredicates($criteria));
    }

    public function testEndsWith()
    {
        $criteria = Criteria::where('field_1')->endsWith('end');

        self::assertEquals('field_1', $this->getField($criteria));
        self::assertEquals('field_1:*end', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testEndsWithMulitpleValues()
    {
        $criteria = Criteria::where('field_1')->endsWith(['one', 'two', 'three']);

        self::assertEquals('field_1', $this->getField($criteria));
        self::assertEquals('field_1:(*one *two *three)', $criteria->getQuery());
        self::assertCount(3, $this->getPredicates($criteria));
    }

    public function testStartsWith()
    {
        $criteria = Criteria::where('field_1')->startsWith('start');

        self::assertEquals('field_1', $this->getField($criteria));
        self::assertEquals('field_1:start*', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testStartsWithMultipleValues()
    {
        $criteria = Criteria::where('field_1')->startsWith(['one', 'two', 'three']);

        self::assertEquals('field_1', $this->getField($criteria));
        self::assertEquals('field_1:(one* two* three*)', $criteria->getQuery());
        self::assertCount(3, $this->getPredicates($criteria));
    }

    public function testContains()
    {
        $criteria = Criteria::where('field_1')->contains('contains');

        self::assertEquals('field_1', $this->getField($criteria));
        self::assertEquals('field_1:*contains*', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testContainsWithMultipleValues()
    {
        $criteria = Criteria::where('field_1')->contains(['one', 'two', 'three']);

        self::assertEquals('field_1', $this->getField($criteria));
        self::assertEquals('field_1:(*one* *two* *three*)', $criteria->getQuery());
        self::assertCount(3, $this->getPredicates($criteria));
    }
    
    public function testExpression()
    {
        $criteria = Criteria::where('field_1')->expression('(have fun using +solr && expressions*)');
        
        self::assertEquals('field_1:(have fun using +solr && expressions*)', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }
    
    public function testCriteriaChain()
    {
        $criteria = Criteria::where('field_1')
            ->startsWith('start')
            ->endsWith('end')
            ->contains('contains')
            ->is('is');

        self::assertEquals('field_1', $this->getField($criteria));
        self::assertEquals('field_1:(start* *end *contains* is)', $criteria->getQuery());
        self::assertCount(4, $this->getPredicates($criteria));
    }

    public function testAnd()
    {
        $criteria = Criteria::where('field_1')
            ->startsWith('start')
            ->endsWith('end')
        ->andWhere('field_2')// Nesting: predicates = 2 crotches
            ->startsWith('2start')
            ->endsWith('2end');

        self::assertEquals('field_1:(start* *end) AND field_2:(2start* *2end)', $criteria->getQuery());
        self::assertCount(2, $this->getPredicates($criteria));
    }

    public function testOr()
    {
        $criteria = Criteria::where('field_1')
            ->startsWith('start')
        ->orWhere('field_2')// Nesting: predicates = 2 crotches
            ->endsWith('end')
            ->startsWith('start2');
        
        self::assertEquals('field_1:start* OR field_2:(*end start2*)', $criteria->getQuery());
        self::assertCount(2, $this->getPredicates($criteria));
    }
    
    public function testCriteriaWithWhiteSpace()
    {
        $criteria = Criteria::where('field_1')->is('white space');
        self::assertEquals('field_1:"white space"', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testIsNot()
    {
        $criteria = Criteria::where('field_1')->is('value_1')->not();
        self::assertEquals('-field_1:value_1', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testFuzzy()
    {
        $criteria = Criteria::where('field_1')->fuzzy('value_1');
        self::assertEquals('field_1:value_1~', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testFuzzyWithDistance()
    {
        $criteria = Criteria::where('field_1')->fuzzy('value_1', 0.5);
        self::assertEquals('field_1:value_1~0.5', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testSloppy()
    {
        $criteria = Criteria::where('field_1')->sloppy('value1 value2', 2);
        self::assertEquals('field_1:"value1 value2"~2', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testBoost()
    {
        $criteria = Criteria::where('field_1')->is('value_1')->boost(2.0);
        self::assertEquals('field_1:value_1^2.0', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testBoostMultipleValues()
    {
        $criteria = Criteria::where('field_1')->is('value_1')->is('value_2')->boost(2.0);
        self::assertEquals('field_1:(value_1 value_2)^2.0', $criteria->getQuery());
        self::assertCount(2, $this->getPredicates($criteria));
    }

    public function testBoostMultipleCriteriasValues()
    {
        $criteria = Criteria::where('field_1')
            ->is('value_1')
            ->is('value_2')
            ->boost(2.0)
        ->andWhere('field_3')// Nesting: predicates = 1 Crotch
            ->is('value_3');

        self::assertEquals('field_1:(value_1 value_2)^2.0 AND field_3:value_3', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testBetween()
    {
        $criteria = Criteria::where('field_1')->between(100, 200);
        self::assertEquals('field_1:[100 TO 200]', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testBetweenExcludeLowerBound()
    {
        $criteria = Criteria::where('field_1')->between(100, 200, false, true);
        self::assertEquals('field_1:{100 TO 200]', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testBetweenExcludeUpperBound()
    {
        $criteria = Criteria::where('field_1')->between(100, 200, true, false);
        self::assertEquals('field_1:[100 TO 200}', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testBetweenWithoutUpperBound()
    {
        $criteria = Criteria::where('field_1')->between(100, null);
        self::assertEquals('field_1:[100 TO *]', $criteria->getQuery());
    }

    public function testBetweenWithoutLowerBound()
    {
        $criteria = Criteria::where('field_1')->between(null, 200);
        self::assertEquals('field_1:[* TO 200]', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testBetweenNegativeNumber()
    {
        $criteria = Criteria::where('field_1')->between(-200, -100);
        self::assertEquals("field_1:[\\-200 TO \\-100]", $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testIn()
    {
        $criteria = Criteria::where('field_1')->in([1, 2, 3, 5, 8, 13, 21]);
        self::assertEquals('field_1:(1 2 3 5 8 13 21)', $criteria->getQuery());
        self::assertCount(7, $this->getPredicates($criteria));
    }

    public function testIsWithNegativeNumner()
    {
        $criteria = Criteria::where('field_1')->is(-100);
        self::assertEquals("field_1:\\-100", $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testNear()
    {
        $criteria = Criteria::where('field_1')->nearCircle(48.303056, 14.290556, 5);
        self::assertEquals('{!bbox pt=48.303056,14.290556 sfield=field_1 d=5.0}', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testNearWithDistanceUnitKilometers()
    {
        $criteria = Criteria::where('field_1')->nearCircle(48.303056, 14.290556, 1);
        self::assertEquals('{!bbox pt=48.303056,14.290556 sfield=field_1 d=1.0}', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testNearWithCoords()
    {
        $criteria = Criteria::where('field_1')->withinBox(48.303056, 14.290556, 48.303056, 14.290556);
        self::assertEquals('field_1:[48.303056,14.290556 TO 48.303056,14.290556]', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    /**
     * @see DATASOLR-105
     * @throws \InvalidArgumentException
     */
    public function testNestedOrPartWithAnd()
    {
        $criteria = Criteria::where('field_1') ->is('foo')
            ->andWhere(Criteria::where('field_2')->is('bar')->orWhere('field_3')->is('roo'))// Nesting
            ->orWhere(Criteria::where('field_4')->is('spring')->andWhere('field_5')->is('data'));

        self::assertEquals('field_1:foo AND (field_2:bar OR field_3:roo) OR (field_4:spring AND field_5:data)', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    /**
     * @see DATASOLR-105
     * @throws \InvalidArgumentException
     */
    public function testNestedOrPartWithAndSomeOtherThings()
    {
        $criteria = Criteria::where('field_1')->is('foo')->is('bar')
            ->andWhere(Criteria::where('field_2')->is('bar')->is('lala')->orWhere('field_3')->is('roo'))// Nesting
            ->orWhere(Criteria::where('field_4')->is('spring')->andWhere('field_5')->is('data'));

        self::assertEquals(
            'field_1:(foo bar) AND (field_2:(bar lala) OR field_3:roo) OR (field_4:spring AND field_5:data)',
            $criteria->getQuery()
        );

        self::assertCount(2, $this->getPredicates($criteria));
    }

    /**
     * @see DATASOLR-105
     * @throws \InvalidArgumentException
     */
    public function testMultipleAnd()
    {
        $criteria = Criteria::where('field_1')->is('foo')
            ->andWhere('field_2')->is('bar')// Nesting
            ->andWhere('field_3')->is('roo');

        self::assertEquals('field_1:foo AND field_2:bar AND field_3:roo', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    /**
     * @see DATASOLR-105
     * @throws \InvalidArgumentException
     */
    public function testMultipleOr()
    {
        $criteria = Criteria::where('field_1')->is('foo')
            ->orWhere('field_2')->is('bar')// Nesting
            ->orWhere('field_3')->is('roo');

        self::assertEquals('field_1:foo OR field_2:bar OR field_3:roo', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    /**
     * @see DATASOLR-105
     * @throws \InvalidArgumentException
     */
    public function testEmptyCriteriaShouldBeDefaultedToNotNUll()
    {
        $criteria = Criteria::where('field_1')->is('foo')
            ->andWhere('field_2')// Nesting
            ->orWhere('field_3');

        self::assertEquals('field_1:foo AND field_2:[* TO *] OR field_3:[* TO *]', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    /**
     * @see DATASOLR-105
     * @throws \InvalidArgumentException
     */
    public function testDeepNesting()
    {
        $criteria = Criteria::where('field_1')->is('foo')
            ->andWhere(Criteria::where('field_2')->is('bar')->andWhere('field_3')->is('roo')
            ->andWhere(Criteria::where('field_4')->is('spring')->andWhere('field_5')->is('data')->orWhere('field_6')->is('solr')));

        self::assertEquals(
            'field_1:foo AND (field_2:bar AND field_3:roo AND (field_4:spring AND field_5:data OR field_6:solr))',
            $criteria->getQuery()
        );

        self::assertCount(1, $this->getPredicates($criteria));
    }

    /**
     * @see DATASOLR-168
     * @throws \InvalidArgumentException
     */
    public function testNotCritieraCarriedOnPorperlyForNullAndNotNull()
    {
        $criteria = Criteria::where('param1')->isNotNull()
            ->andWhere('param2')->isNull();// Nesting

        self::assertEquals('param1:[* TO *] AND -param2:[* TO *]', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    /**
     * @see DATASOLR-196
     * @throws \InvalidArgumentException
     */
    public function testConnectShouldAllowConcatinationOfCriteriaWithAndPreservingDesiredBracketing()
    {
        $part1 = Criteria::where('z')->is('roo');
        $part2 = Criteria::where('x')->is('foo')->orWhere('y')->is('bar');
        $criteria = $part1->connect()->andWhere($part2);

        self::assertEquals('z:roo AND (x:foo OR y:bar)', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    /**
     * @see DATASOLR-196
     * @throws \InvalidArgumentException
     */
    public function testConnectShouldAllowConcatinationOfCriteriaWithAndPreservingDesiredBracketingReverse()
    {
        $part1 = Criteria::where('z')->is('roo');
        $part2 = Criteria::where('x')->is('foo')->orWhere('y')->is('bar');
        $criteria = $part2->connect()->andWhere($part1);

        self::assertEquals('(x:foo OR y:bar) AND z:roo', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    /**
     * @see DATASOLR-196
     * @throws \InvalidArgumentException
     */
    public function testConnectShouldAllowConcatinationOfCriteriaWithOrPreservingDesiredBracketing()
    {
        $part1 = Criteria::where('z')->is('roo');
        $part2 = Criteria::where('x')->is('foo')->orWhere('y')->is('bar');
        $criteria = $part1->connect()->orWhere($part2);

        self::assertEquals('z:roo OR (x:foo OR y:bar)', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    /**
     * @see DATASOLR-196
     * @throws \InvalidArgumentException
     */
    public function testConnectShouldAllowConcatinationOfCriteriaWithOrPreservingDesiredBracketingReverse()
    {
        $part1 = Criteria::where('z')->is('roo');
        $part2 = Criteria::where('x')->is('foo')->orWhere('y')->is('bar');
        $criteria = $part2->connect()->orWhere($part1);

        self::assertEquals('(x:foo OR y:bar) OR z:roo', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    /**
     * @see DATASOLR-196
     * @throws \InvalidArgumentException
     */
    public function testNotOperatorShouldWrapWholeExpression()
    {
        $part1 = Criteria::where('text')->startsWith('fx')->orWhere('product_code')->startsWith('fx');
        $part2 = Criteria::where('text')->startsWith('option')->orWhere('product_code')->startsWith('option');
        $criteria = $part1->connect()->andWhere($part2)->notOperator();

        self::assertEquals('-((text:fx* OR product_code:fx*) AND (text:option* OR product_code:option*))', $criteria->getQuery());
    }

    /**
     * @see DATASOLR-196
     * @throws \InvalidArgumentException
     */
    public function testNotOperatorShouldWrapNestedExpressionCorrectly()
    {
        $part1 = Criteria::where('z')->is('roo');
        $part2 = Criteria::where('x')->is('foo')->orWhere('y')->is('bar')->notOperator();
        $criteria = $part1->connect()->orWhere($part2);

        self::assertEquals('z:roo OR -(x:foo OR y:bar)', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    /**
     * @see DATASOLR-196
     * @throws \InvalidArgumentException
     */
    public function testNotOperatorShouldWrapNestedExpressionCorrectlyReverse()
    {
        $part1 = Criteria::where('z')->is('roo');
        $part2 = Criteria::where('x')->is('foo')->orWhere('y')->is('bar')->notOperator();
        $criteria = $part2->connect()->orWhere($part1);

        self::assertEquals('-(x:foo OR y:bar) OR z:roo', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    /**
     * @see DATASOLR-196
     * @throws \InvalidArgumentException
     */
    public function testNotOperatorShouldWrapNestedExpressionCorrectlyReverseWithDoubleNegation()
    {
        $part1 = Criteria::where('z')->is('roo');
        $part2 = Criteria::where('x')->is('foo')->orWhere('y')->is('bar')->notOperator();
        $criteria = $part2->connect()->andWhere($part1)->notOperator();

        self::assertEquals('-(-(x:foo OR y:bar) AND z:roo)', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testCriteriaWithDoubleQuotes()
    {
        $criteria =  Criteria::where('field_1')->is('with \"quote');
        self::assertEquals('field_1:"with \\\\\"quote"', $criteria->getQuery());
    }
}
