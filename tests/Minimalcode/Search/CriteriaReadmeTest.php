<?php

namespace Minimalcode\Search;

use DateTime;

class CriteriaReadmeTest extends CriteriaBaseTest
{
    public function testGetQueryReadme()
    {
        // The output methods are equivalent
        $criteria = Criteria::where('field')->is('foo');
        self::assertEquals($criteria->getQuery(), (string) $criteria);
    }

    public function testIsReadme()
    {
        // string
        self::assertEquals('field:foo', (string) Criteria::where('field')->is('foo'));
        self::assertEquals('field:(foo bar)', (string) Criteria::where('field')->is('foo')->is('bar'));
        self::assertEquals('field:(foo bar)', (string) Criteria::where('field')->is(['foo', 'bar']));

        // int
        self::assertEquals('field:100', (string) Criteria::where('field')->is(100));
        self::assertEquals('field:\-100', (string) Criteria::where('field')->is(-100));

        // float
        self::assertEquals('field:4.5', (string) Criteria::where('field')->is(4.5));
        self::assertEquals('field:\-4.5', (string) Criteria::where('field')->is(-4.5));

        // bool
        self::assertEquals('field:true', (string) Criteria::where('field')->is(true));
        self::assertEquals('field:false', (string) Criteria::where('field')->is(false));

        // null
        self::assertEquals('-field:[* TO *]', (string) Criteria::where('field')->is(null));

        // date
        self::assertEquals('field:2016\-12\-25T00\:00\:00Z', (string) Criteria::where('field')->is(new DateTime('2016-12-25')));

        // escape
        self::assertEquals('field:"quote \-100 quote is \" symbol"', (string) Criteria::where('field')->is('quote -100 quote is " symbol'));

        // object
        self::assertEquals('field:"value from __toString"', (string) Criteria::where('field')->is(new ObjectWithToString()));
        
        // mixed
        $array = [100, '200', [null, new DateTime('2016-12-25'), ['foo', 'bar'], -2.4], true];
        self::assertEquals('-field:(100 200 [* TO *] 2016\-12\-25T00\:00\:00Z foo bar \-2.4 true)', (string) Criteria::where('field')->in($array));
    }

    public function testInReadme()
    {
        self::assertEquals('field:(foo bar)', (string) Criteria::where('field')->in(['foo', 'bar']));
    }

    public function testStartsWithReadme()
    {
        self::assertEquals('field:foo*', (string) Criteria::where('field')->startsWith('foo'));
        self::assertEquals('field:(foo* bar*)', (string) Criteria::where('field')->startsWith('foo')->startsWith('bar'));
        self::assertEquals('field:(foo* bar*)', (string) Criteria::where('field')->startsWith(['foo', 'bar']));
    }

    public function testEndsWithReadme()
    {
        self::assertEquals('field:*foo', (string) Criteria::where('field')->endsWith('foo'));
        self::assertEquals('field:(*foo *bar)', (string) Criteria::where('field')->endsWith('foo')->endsWith('bar'));
        self::assertEquals('field:(*foo *bar)', (string) Criteria::where('field')->endsWith(['foo', 'bar']));
    }

    public function testContainsWithReadme()
    {
        self::assertEquals('field:*foo*', (string) Criteria::where('field')->contains('foo'));
        self::assertEquals('field:(*foo* *bar*)', (string) Criteria::where('field')->contains('foo')->contains('bar'));
        self::assertEquals('field:(*foo* *bar*)', (string) Criteria::where('field')->contains(['foo', 'bar']));
    }

    public function testNotReadme()
    {
        self::assertEquals('-field:foo', (string) Criteria::where('field')->is('foo')->not());
        self::assertEquals('-field:*foo*', (string) Criteria::where('field')->contains('foo')->not());
        self::assertEquals('-field:(foo* bar*)', (string) Criteria::where('field')->startsWith(['foo', 'bar'])->not());
    }

    public function testIsNotNullReadme()
    {
        self::assertEquals('field:[* TO *]', (string) Criteria::where('field')->isNotNull());
    }

    public function testIsNullReadme()
    {
        self::assertEquals('-field:[* TO *]', (string) Criteria::where('field')->isNull());
    }

    public function testLessThanReadme()
    {
        self::assertEquals('field:[* TO 50}', (string) Criteria::where('field')->lessThan('50'));
        self::assertEquals('field:[* TO 15}', (string) Criteria::where('field')->lessThan(15));
        self::assertEquals('field:[* TO 2016\-12\-25T00\:00\:00Z}', (string) Criteria::where('field')->lessThan(new DateTime('2016-12-25')));
    }

    public function testLessThanEqualReadme()
    {
        self::assertEquals('field:[* TO 50]', (string) Criteria::where('field')->lessThanEqual('50'));
        self::assertEquals('field:[* TO 15]', (string) Criteria::where('field')->lessThanEqual(15));
        self::assertEquals('field:[* TO 4.5]', (string) Criteria::where('field')->lessThanEqual(4.5));
        self::assertEquals('field:[* TO 2016\-12\-25T00\:00\:00Z]', (string) Criteria::where('field')->lessThanEqual(new DateTime('2016-12-25')));
    }

    public function testGreaterThanReadme()
    {
        self::assertEquals('field:{50 TO *]', (string) Criteria::where('field')->greaterThan('50'));
        self::assertEquals('field:{15 TO *]', (string) Criteria::where('field')->greaterThan(15));
        self::assertEquals('field:{4.5 TO *]', (string) Criteria::where('field')->greaterThan(4.5));
        self::assertEquals('field:{2016\-12\-25T00\:00\:00Z TO *]', (string) Criteria::where('field')->greaterThan(new DateTime('2016-12-25')));
    }

    public function testGreaterThanEqualReadme()
    {
        self::assertEquals('field:[50 TO *]', (string) Criteria::where('field')->greaterThanEqual('50'));
        self::assertEquals('field:[15 TO *]', (string) Criteria::where('field')->greaterThanEqual(15));
        self::assertEquals('field:[4.5 TO *]', (string) Criteria::where('field')->greaterThanEqual(4.5));
        self::assertEquals('field:[2016\-12\-25T00\:00\:00Z TO *]', (string) Criteria::where('field')->greaterThanEqual(new DateTime('2016-12-25')));
    }

    public function testBetweenReadme()
    {
        self::assertEquals('field:[60 TO 100]', (string) Criteria::where('field')->between(60, '100'));
        self::assertEquals('field:[4.5 TO 5.5]', (string) Criteria::where('field')->between(4.5, '5.5'));
        self::assertEquals('field:[* TO 100]', (string) Criteria::where('field')->between(null, '100'));
        self::assertEquals('field:[60 TO *]', (string) Criteria::where('field')->between(60, null));
        self::assertEquals('field:[2016\-12\-25T00\:00\:00Z TO 2016\-12\-31T00\:00\:00Z]', (string) Criteria::where('field')->between(new DateTime('2016-12-25'), new DateTime('2016-12-31')));
    }

    public function testExpressionReadme()
    {
        self::assertEquals('__query__:{!dismax qf=myfield}how now brown cow', (string) Criteria::where('__query__')->expression('{!dismax qf=myfield}how now brown cow'));
    }

    public function testGeoSearchReadme()
    {
        self::assertEquals('{!bbox pt=38.116181,-86.929463 sfield=position d=100.5}', (string) Criteria::where('position')->nearCircle(38.116181, -86.929463, 100.5));
        self::assertEquals('{!geofilt pt=38.116181,-86.929463 sfield=position d=100.5}', (string) Criteria::where('position')->withinCircle(38.116181, -86.929463, 100.5));
        self::assertEquals('position:[38.116181,-86.929463 TO 38.116181,-86.929463]', (string) Criteria::where('position')->withinBox(38.116181, -86.929463, 38.116181, -86.929463));
    }

    public function testChristmasReadme()
    {
        $nearNorthPole  = Criteria::where('position')->nearCircle(38.116181, -86.929463, 100.5);
        self::assertEquals('{!bbox pt=38.116181,-86.929463 sfield=position d=100.5}', $nearNorthPole->getQuery());

        $santaClaus = Criteria::where('santa-name')->contains(['Noel', 'Claus', 'Natale', 'Baba', 'Nicolas'])
                    ->andWhere('santa-beard-exists')->is(true)
                    ->andWhere('santa-beard-lenght')->between(5.5, 10.0)
                    ->andWhere('santa-beard-color')->startsWith('whi')->endsWith('te')
                    ->andWhere($nearNorthPole);

        self::assertEquals('santa-name:(*Noel* *Claus* *Natale* *Baba* *Nicolas*) AND santa-beard-exists:true AND santa-beard-lenght:[5.5 TO 10] AND santa-beard-color:(whi* *te) AND {!bbox pt=38.116181,-86.929463 sfield=position d=100.5}', $santaClaus->getQuery());

        $goodPeople = Criteria::where('good-actions')->greaterThanEqual(10)
                            ->orWhere('bad-actions')->lessThanEqual(5);

        self::assertEquals('good-actions:[10 TO *] OR bad-actions:[* TO 5]', $goodPeople->getQuery());
        
        $gifts = Criteria::where('gift-name')->sloppy('LED TV GoPro Oculus Tablet Laptop', 2)
                                ->andWhere('gift-type')->fuzzy('information', 0.4)->startsWith('tech')
                                ->andWhere('__query__')->expression('{!dismax qf=myfield}how now brown cow');

        self::assertEquals('gift-name:"LED TV GoPro Oculus Tablet Laptop"~2 AND gift-type:(information~0.4 tech*) AND __query__:{!dismax qf=myfield}how now brown cow', $gifts->getQuery());

        $christmas = new DateTime('2016-12-25');
        $contributors = ['Christoph', 'Philipp', 'Francisco', 'Fabio'];
        $giftReceivers  = Criteria::where('gift-received')->is(null)
                                ->andWhere('chimney')->isNotNull()
                                ->andWhere('date')->is($christmas)->greaterThanEqual(new \Datetime('1970-01-01'))
                                ->andWhere($santaClaus)
                                ->andWhere($gifts)
                                ->andWhere(
                                    Criteria::where('name')->in($contributors)->boost(2.0)
                                            ->orWhere($goodPeople)
                                );

        self::assertEquals("-gift-received:[* TO *] AND chimney:[* TO *] AND date:(2016\\-12\\-25T00\\:00\\:00Z [1970\\-01\\-01T00\\:00\\:00Z TO *]) AND (santa-name:(*Noel* *Claus* *Natale* *Baba* *Nicolas*) AND santa-beard-exists:true AND santa-beard-lenght:[5.5 TO 10] AND santa-beard-color:(whi* *te) AND {!bbox pt=38.116181,-86.929463 sfield=position d=100.5}) AND (gift-name:\"LED TV GoPro Oculus Tablet Laptop\"~2 AND gift-type:(information~0.4 tech*) AND __query__:{!dismax qf=myfield}how now brown cow) AND (name:(Christoph Philipp Francisco Fabio)^2.0 OR (good-actions:[10 TO *] OR bad-actions:[* TO 5]))", $giftReceivers->getQuery());
    }
}

class ObjectWithToString
{
    public function __toString()
    {
        return 'value from __toString';
    }
}
