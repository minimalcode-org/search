<?php
/*
 * Copyright 2016 the original author or authors.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Minimalcode\Search;

use DateTime;
use InvalidArgumentException;
use Minimalcode\Search\Internal\Node;

/**
 * Minimalistic stand-alone PHP implementation of Spring Solr Data Criteria.
 * {@see https://github.com/spring-projects/spring-data-solr}.
 *
 * The original authors are: Christoph Strobl, Philipp Jardas, Francisco Spaeth.
 *
 * Criteria is the central class when constructing queries. It follows more or less
 * a fluent API style, which allows to easily chain together multiple criteria.
 *
 * @author Fabio Piro <pirofabio@gmail.com>
 */
class Criteria
{
    /** @var string[] */
    private $predicates = [];

    /** @var Node */
    private $root;

    /** @var string */
    private $field;

    /** @var float */
    private $boost;

    /** @var bool */
    private $isNegating;

    /** @var bool */
    private $isHideFieldName;

    /**
     * Criteria private constructor.
     *
     * @param string $field
     * @param Node $root
     * @throws \InvalidArgumentException if the field param is not a string
     * @throws \InvalidArgumentException if the field name is an empty string
     */
    private function __construct($field, Node $root)
    {
        if (!\is_string($field)) {
            throw new InvalidArgumentException('Criteria\'s field must be a string type.');
        }

        if ($field === '') {
            throw new InvalidArgumentException('Field name for criteria must not be null/empty.');
        }

        $this->field = $field;
        $this->root = $root;
    }

    /**
     * Static factory method to create a new Criteria for the provided field.
     *
     * @param string $field
     * @return $this
     * @throws \InvalidArgumentException if the field param is not a string
     */
    public static function where($field)
    {
        $root = new Node(Node::TYPE_CROTCH, Node::OPERATOR_BLANK);
        $criteria = new Criteria($field, $root);
        $root->append(Node::OPERATOR_BLANK, $criteria);

        return $criteria;
    }

    /**
     * Combine two nodes (string field or nested Criteria object) using the AND operator.
     *
     * @param string|Criteria $field
     * @return $this
     * @throws \InvalidArgumentException if field is not a string or a Criteria object
     */
    public function andWhere($field)
    {
        if ($field instanceof Criteria) {
            return $this->root->inject(Node::OPERATOR_AND, $field->root)->getMostRecentCriteria();
        }

        return $this->root->append(Node::OPERATOR_AND, new Criteria($field, $this->root))->getMostRecentCriteria();
    }

    /**
     * Combine two nodes (string field or nested Criteria object) using the OR operator.
     *
     * @param string|Criteria $field
     * @return $this
     * @throws \InvalidArgumentException if field is not a string or a Criteria object
     */
    public function orWhere($field)
    {
        if ($field instanceof Criteria) {
            return $this->root->inject(Node::OPERATOR_OR, $field->root)->getMostRecentCriteria();
        }

        return $this->root->append(Node::OPERATOR_OR, new Criteria($field, $this->root))->getMostRecentCriteria();
    }

    /**
     * Crates new predicate for RANGE [lowerBound TO upperBound]
     *
     * @param mixed $lowerBound
     * @param mixed $upperBound
     * @param bool  $includeLowerBound optional
     * @param bool  $includeUpperBound optional
     * @return $this
     */
    public function between($lowerBound, $upperBound, $includeLowerBound = true, $includeUpperBound = true)
    {
        $lowerBound = ($lowerBound === null) ? '*' : $lowerBound;
        $upperBound = ($upperBound === null) ? '*' : $upperBound;
        $this->predicates[] = ($includeLowerBound ? '[' : '{') . $this->processValue($lowerBound) .
            ' TO ' . $this->processValue($upperBound) . ($includeUpperBound ? ']' : '}');

        return $this;
    }

    /**
     * Crates new predicate for RANGE [* TO upperBound].
     *
     * @param mixed $upperBound
     * @return $this
     */
    public function lessThanEqual($upperBound)
    {
        return $this->between(null, $upperBound);
    }

    /**
     * Crates new predicate for RANGE [* TO upperBound}.
     *
     * @param mixed $upperBound
     * @return $this
     */
    public function lessThan($upperBound)
    {
        return $this->between(null, $upperBound, true, false);
    }

    /**
     * Crates new predicate for RANGE {lowerBound TO *].
     *
     * @param mixed $lowerBound
     * @return $this
     */
    public function greaterThan($lowerBound)
    {
        return $this->between($lowerBound, null, false, true);
    }

    /**
     * Crates new predicate for RANGE [lowerBound TO *].
     *
     * @param mixed $lowerBound
     * @return $this
     */
    public function greaterThanEqual($lowerBound)
    {
        return $this->between($lowerBound, null);
    }

    /**
     * Creates new predicate without any wildcards for each entry.
     *
     * For arrays the Criteria::in() method is equivalent but more performant.
     *
     * @param mixed|array $value
     * @return $this
     */
    public function is($value)
    {
        if ($value === null) {
            return $this->isNull();
        }

        if (\is_array($value)) {
            return $this->in($value);
        }

        $this->predicates[] = $this->processValue($value);

        return $this;
    }

    /**
     * Crates new predicate for multiple (possibly nested) values [arg0 arg1 arg2 ...].
     *
     * @param array $values
     * @return $this
     */
    public function in(array $values)
    {
        foreach ($values as $value) {
            if (\is_array($value)) {
                $this->in($value);
            } else {
                $this->is($value);
            }
        }

        return $this;
    }

    /**
     * Creates new predicate for !geodist through circle and radius.
     *
     * The geofilt filter allows you to retrieve results based on the geospatial distance
     * (AKA the "great circle distance") from a given point.
     *
     * @param float $latitude
     * @param float $longitude
     * @param float $distance
     * @return $this
     * @throws \InvalidArgumentException if radius is negative
     */
    public function withinCircle($latitude, $longitude, $distance)
    {
        $this->assertPositiveFloat($distance);
        $this->predicates[] = '{!geofilt pt=' . $this->processFloat($latitude) . ',' .
            $this->processFloat($longitude) . ' sfield=' . $this->field . ' d=' . $this->processFloat($distance) . '}';

        $this->isHideFieldName = true;

        return $this;
    }

    /**
     * Creates new predicate for a RANGE spatial search.
     *
     * Finds exactly everything in a rectangular area, such as the area covered by a map the user is looking at.
     *
     * @param float $startLatitude
     * @param float $startLongitude
     * @param float $endLatitude
     * @param float $endLongitude
     * @return $this
     */
    public function withinBox($startLatitude, $startLongitude, $endLatitude, $endLongitude)
    {
        $this->predicates[] = '[' . $this->processFloat($startLatitude) . ',' . $this->processFloat($startLongitude) .
            ' TO ' . $this->processFloat($endLatitude) . ',' . $this->processFloat($endLongitude) . ']';

        return $this;
    }

    /**
     * Creates new predicate for !bbox filter.
     *
     * The !bbox filter is very similar to !geofilt except it uses the bounding box of the calculated circle.
     * The rectangular shape is faster to compute and so it's sometimes used as an alternative to !geofilt
     * when it's acceptable to return points outside of the radius.
     *
     * @param float $latitude
     * @param float $longitude
     * @param float $distance
     * @return $this
     * @throws \InvalidArgumentException if radius is negative
     */
    public function nearCircle($latitude, $longitude, $distance)
    {
        $this->assertPositiveFloat($distance);
        $this->predicates[] = '{!bbox pt=' . $this->processFloat($latitude) . ',' . $this->processFloat($longitude) .
            ' sfield=' . $this->field . ' d=' . $this->processFloat($distance) . '}';

        $this->isHideFieldName = true;

        return $this;
    }

    /**
     * Crates new predicate for null values.
     *
     * @return $this
     */
    public function isNull()
    {
        return $this->between(null, null)->not();
    }

    /**
     * Crates new predicate for !null values.
     *
     * @return $this
     */
    public function isNotNull()
    {
        return $this->between(null, null);
    }

    /**
     * Crates new predicate with leading and trailing wildcards for each entry.
     *
     * @param string|string[] $value
     * @return $this
     */
    public function contains($value)
    {
        if (\is_array($value)) {
            /** @noinspection ForeachSourceInspection */
            foreach ($value as $item) {
                $this->contains($item);
            }
        } else {
            $this->predicates[] = '*' . $this->processValue($value) . '*';
        }

        return $this;
    }

    /**
     * Crates new predicate with trailing wildcard for each entry.
     *
     * @param string|string[] $prefix
     * @return $this
     * @throws \InvalidArgumentException if prefix contains blank char
     */
    public function startsWith($prefix)
    {
        if (\is_array($prefix)) {
            /** @noinspection ForeachSourceInspection */
            foreach ($prefix as $item) {
                $this->startsWith($item);
            }
        } else {
            $this->assertNotBlanks($prefix);
            $this->predicates[] = $this->processValue($prefix) . '*';
        }

        return $this;
    }

    /**
     * Crates new predicate with leading wildcard for each entry.
     *
     * @param string|string[] $postfix
     * @return $this
     * @throws \InvalidArgumentException if postfix contains blank char
     */
    public function endsWith($postfix)
    {
        if (\is_array($postfix)) {
            /** @noinspection ForeachSourceInspection */
            foreach ($postfix as $item) {
                $this->endsWith($item);
            }
        } else {
            $this->assertNotBlanks($postfix);
            $this->predicates[] = '*' . $this->processValue($postfix);
        }

        return $this;
    }

    /**
     * Negates current criteria using - operator.
     *
     * @return $this
     */
    public function not()
    {
        $this->isNegating = true;

        return $this;
    }

    /**
     * Explicitly wraps Criteria inside not operation.
     *
     * @return $this
     */
    public function notOperator()
    {
        $this->root->setNegatingWholeChildren(true);

        return $this;
    }

    /**
     * Crates new predicate with trailing ~ optionally followed by levensteinDistance.
     *
     * @param string $value
     * @param float $levenshteinDistance optional
     * @return $this
     * @throws \InvalidArgumentException if levensteinDistance with wrong bounds
     */
    public function fuzzy($value, $levenshteinDistance = null)
    {
        if ($levenshteinDistance !== null && ($levenshteinDistance < 0 || $levenshteinDistance > 1)) {
            throw new InvalidArgumentException('Levenshtein Distance has to be within its bounds (0.0 - 1.0).');
        }

        $this->predicates[] = $this->processValue($value) . '~' .
            ($levenshteinDistance === null ? '' : $this->processFloat($levenshteinDistance));

        return $this;
    }

    /**
     * Crates new precidate with trailing ~ followed by distance.
     *
     * @param string $phrase
     * @param int $distance
     * @return $this
     * @throws \InvalidArgumentException if sloppy distance < 0
     * @throws \InvalidArgumentException if sloppy phrase without multiple terms
     */
    public function sloppy($phrase, $distance)
    {
        if ($distance <= 0) {
            throw new InvalidArgumentException('Slop distance has to be greater than 0.');
        }

        if (\strpos($phrase, ' ') === false) {
            throw new InvalidArgumentException('Sloppy phrase must consist of multiple terms, separated with spaces.');
        }

        $this->predicates[] = $this->processValue($phrase) . '~' . $distance;

        return $this;
    }

    /**
     * Crates new predicate allowing native solr expressions.
     *
     * @param string $value
     * @return $this
     */
    public function expression($value)
    {
        $this->predicates[] = $value;

        return $this;
    }

    /**
     * Explicitly connect Criteria with another one allows to create explicit bracketing.
     *
     * @return $this
     */
    public function connect()
    {
        $this->root->connect();

        return $this;
    }

    /**
     * Boost positive hit with given factor. eg. ^2.3 value.
     *
     * @param float $value
     * @return $this
     * @throws \InvalidArgumentException if provided boost is negative
     */
    public function boost($value)
    {
        if ($value < 0) {
            throw new InvalidArgumentException('Boost must not be negative.');
        }

        $this->boost = $this->processFloat($value);

        return $this;
    }

    /**
     * Generates the query string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->traverse($this->root);
    }

    /**
     * Generates the query string.
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->traverse($this->root);
    }

    /**
     * Parses and manages the current node tree of predicates.
     *
     * @param Node $node
     * @return string
     */
    private function traverse(Node $node)
    {
        $query = '';

        if ($node->getOperator() !== Node::OPERATOR_BLANK) {
            $query .= ' ' . $node->getOperator() . ' ';
        }

        if ($node->getType() === Node::TYPE_CROTCH) {
            $addsParentheses = $node->isNegatingWholeChildren()
                || ($node !== $this->root && \count($node->getChildren()) > 1);

            if ($node->isNegatingWholeChildren()) {
                $query .= '-';
            }

            if ($addsParentheses) {
                $query .= '(';
            }

            foreach ($node->getChildren() as $child) {
                $query .= $this->traverse($child);
            }

            if ($addsParentheses) {
                $query .= ')';
            }
        } else {// if ($node->getType() === Node::TYPE_LEAF) { is always true
            $criteria = $node->getCriteria();
            $countPredicates = \count($criteria->predicates);
            $addsParentheses = $countPredicates > 1;

            if ($countPredicates === 0) {
                $criteria->isNotNull();// where("field"); => field:[* TO *]
                $countPredicates = 1;
            }

            if ($criteria->isNegating) {
                $query .= '-';
            }

            if (! $criteria->isHideFieldName) {
                $query .= $criteria->field . ':';// "field:" prefix
            }

            if ($addsParentheses) {
                $query .= '(';
            }

            $i = 0;
            while ($i < $countPredicates) {
                $query .= $criteria->predicates[$i];

                if (($i + 1) !== $countPredicates) {
                    $query .= ' ';// field:(a b c d...) This falls upon the solr q.op param (default OR)
                }

                $i++;
            }

            if ($addsParentheses) {
                $query .= ')';
            }

            if ($criteria->boost) {
                $query .= '^' . $criteria->boost;// field:foo^5.0
            }
        }

        return $query;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function processValue($value)
    {
        if ($value === '*') {
            return $value;
        }

        if ($value instanceof DateTime) {
            $value = $value->format('Y-m-d\TH:i:s\Z');
        }

        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        // Sanitize special chars
        $value = \preg_replace('/(\+|-|&&|\|\||!|\(|\)|\{|}|\[|]|\^|"|~|\*|\?|:|\/|\\\)/', '\\\$1', (string) $value);

        // Sanitize multiple words
        if (\strpos($value, ' ')) {
            $value = '"' . $value . '"';
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function processFloat($value)
    {
        $float = (string) $value;

        return \strpos($float, '.') ? $float : $float . '.0';
    }

    /**
     * @param string $value
     * @throws \InvalidArgumentException
     */
    private function assertNotBlanks($value)
    {
        if (\strpos($value, ' ')) {
            throw new InvalidArgumentException(
                'Cannot construct query with white spaces. Use expression or multitple clauses instead.'
            );
        }
    }

    /**
     * @param float $distance
     * @throws \InvalidArgumentException
     */
    private function assertPositiveFloat($distance)
    {
        if ($distance < 0) {
            throw new InvalidArgumentException('Distance must not be negative.');
        }
    }
}
