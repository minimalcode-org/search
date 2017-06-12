<?php
namespace Minimalcode\Search\Tests;

use Minimalcode\Search\Criteria;
use PHPUnit_Framework_TestCase;

abstract class CriteriaBaseTest extends PHPUnit_Framework_TestCase
{
    protected function getField(Criteria $criteria)
    {
        $field = new \ReflectionProperty('Minimalcode\Search\Criteria', 'field');
        $field->setAccessible(true);

        return $field->getValue($criteria);
    }

    protected function getPredicates(Criteria $criteria)
    {
        $field = new \ReflectionProperty('Minimalcode\Search\Criteria', 'predicates');
        $field->setAccessible(true);

        return $field->getValue($criteria);
    }
}
