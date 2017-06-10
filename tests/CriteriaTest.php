<?php

namespace Minimalcode\Search;

use DateTime;

class CriteriaTest extends CriteriaBaseTest
{
    public function testNearNegativePosition()
    {
        $criteria = Criteria::where('field_1')->withinBox(-48.303056, -14.290556, -48.303056, -14.290556);

        self::assertEquals('field_1', $this->getField($criteria));
        self::assertEquals('field_1:[-48.303056,-14.290556 TO -48.303056,-14.290556]', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testWithinCircleNegativePosition()
    {
        $criteria = Criteria::where('field_1')->withinCircle(-48.303056, -14.290556, 5);

        self::assertEquals('field_1', $this->getField($criteria));
        self::assertEquals('{!geofilt pt=-48.303056,-14.290556 sfield=field_1 d=5.0}', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testWithinCircleWithBigPosition()
    {
        $criteria = Criteria::where('field_1')->withinCircle(-48.303056, -14.290556, 5.123456);

        self::assertEquals('field_1', $this->getField($criteria));
        self::assertEquals('{!geofilt pt=-48.303056,-14.290556 sfield=field_1 d=5.123456}', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testNestedArray()
    {
        $criteria = Criteria::where('field_1')->in([
            'one', 'two', [
                'nested-one', 'nested-two', [
                    'deep-one', 'deep-two', 'deep-three'
                ], 'nested-three'
            ], 'three'
        ]);

        self::assertEquals('field_1', $this->getField($criteria));
        self::assertEquals('field_1:(one two nested\-one nested\-two deep\-one deep\-two deep\-three nested\-three three)', $criteria->getQuery());
        self::assertCount(9, $this->getPredicates($criteria));
    }

    public function testIsDateTime()
    {
        $date = new DateTime('2015-08-21 15:00:00', new \DateTimeZone('UTC'));
        $criteria = Criteria::where('field_1')->is($date);

        self::assertEquals('field_1', $this->getField($criteria));
        self::assertEquals("field_1:2015\\-08\\-21T15\\:00\\:00Z", $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testIsWithDateValue()
    {
        $dateTime = new DateTime('2012-8-21 06:35', new \DateTimeZone('UTC'));
        $criteria = Criteria::where('dateField')->is($dateTime);

        self::assertEquals("dateField:2012\\-08\\-21T06\\:35\\:00Z", $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testToString()
    {
        $criteria = Criteria::where('field_1')->in([-48.303056, -14.290556, -48.303056, -14.290556]);

        self::assertEquals('field_1', $this->getField($criteria));
        /** @noinspection ImplicitMagicMethodCallInspection */
        self::assertEquals("field_1:(\\-48.303056 \\-14.290556 \\-48.303056 \\-14.290556)", $criteria->__toString());
        self::assertCount(4, $this->getPredicates($criteria));
    }

    public function testLessThan()
    {
        $criteria = Criteria::where('field_1')->lessThan(1);

        self::assertEquals('field_1', $this->getField($criteria));
        self::assertEquals('field_1:[* TO 1}', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    public function testGreaterThan()
    {
        $criteria = Criteria::where('field_1')->greaterThan(1);

        self::assertEquals('field_1', $this->getField($criteria));
        self::assertEquals('field_1:{1 TO *]', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWhereNotStringException()
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        Criteria::where(2);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWhereEmptyStringException()
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        Criteria::where('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFuzzyWithInvalidDistanceException()
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        Criteria::where('field_1')->fuzzy('a b', 2);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSloppyWithInvalidDistanceException()
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        Criteria::where('field_1')->sloppy('a b', -2);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSloppyWithSingleWordException()
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        Criteria::where('field_1')->sloppy('a', 1);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBoostNegativeException()
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        Criteria::where('field_1')->boost(-1.0);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithinCircleNegativeDistanceException()
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        Criteria::where('field_1')->withinCircle(1.0, 1.0, -1);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNearCircleNegativeDistanceException()
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        Criteria::where('field_1')->nearCircle(1.0, 1.0, -1);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testStartsWithBlanksException()
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        Criteria::where('field_1')->startsWith('a b c');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testEndsWithBlanksException()
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        Criteria::where('field_1')->endsWith('a b c');
    }
    
    public function testIsWithNull()
    {
        $criteria = Criteria::where('field_1')->is(null);

        self::assertEquals('field_1', $this->getField($criteria));
        self::assertEquals('-field_1:[* TO *]', $criteria->getQuery());
        self::assertCount(1, $this->getPredicates($criteria));
    }
}
