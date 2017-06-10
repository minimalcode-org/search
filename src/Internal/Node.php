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
namespace Minimalcode\Search\Internal;

use Minimalcode\Search\Criteria;

/**
 * Node tree rappresentation for Criteria.
 *
 * This tree-like logic is needeed for features like nesting and connecting.
 *
 * The class is actually a clear abstract+impls architecture, but for the lack of
 * private classes in PHP language (and to keep the classes count low) the
 * implementation is just marked as internal and switched at runtime through
 * the TYPE property.
 *
 * @author Fabio Piro
 * @internal
 */
/* private abstract */class Node
{
    const OPERATOR_AND      = 'AND';
    const OPERATOR_OR       = 'OR';
    const OPERATOR_BLANK    = '';

    const TYPE_LEAF         = 0;
    const TYPE_CROTCH       = 1;

    /******* private abstract class Node *********************************** */

    /** @var string ("AND"|"OR"|"") */
    private $operator;
    
    /** @var integer (0|1) */
    private $type;

    /******* private class Leaf extends Node ******************************* */

    /** @var Criteria */
    private $criteria;

    /******* private class Crotch extends Node ***************************** */

    /** @var Node[] */
    private $children = [];

    /** @var Criteria */
    private $mostRecentCriteria;

    /** @var boolean */
    private $isNegatingWholeChildren;

    /** ******************************************************************** */

    /**
     * Node constructor.
     *
     * @param int $type (0|1)
     * @param string $operator ("AND"|"OR"|"")
     * @param Criteria $criteria make sense only for leaf types
     */
    public function __construct($type, $operator, Criteria $criteria = null)
    {
        $this->operator = $operator;
        $this->criteria = $criteria;
        $this->type = $type;
    }

    /**
     * Injects a (potentially complex tree-like) node crotch into this node.
     *
     * @param string $operator ("AND"|"OR"|"")
     * @param Node $root
     * @return $this
     */
    public function inject($operator, Node $root)
    {
        $crotch = new Node(self::TYPE_CROTCH, $operator);
        $crotch->children[] = $root;
        $this->children[] = $crotch;

        return $this;
    }

    /**
     * Appends a leaf node to the children of this node.
     *
     * @param string $operator ("AND"|"OR"|"")
     * @param Criteria $criteria
     * @return $this
     */
    public function append($operator, Criteria $criteria)
    {
        $this->children[] = new Node(self::TYPE_LEAF, $operator, $criteria);
        $this->mostRecentCriteria = $criteria;

        return $this;
    }

    /**
     * Connects the children to a new parent tree.
     *
     * @return $this
     */
    public function connect()
    {
        $crotch = new Node(self::TYPE_CROTCH, self::OPERATOR_BLANK, null);
        $crotch->children = \array_merge($crotch->children, $this->children);
        $crotch->isNegatingWholeChildren = $this->isNegatingWholeChildren;
        $this->isNegatingWholeChildren = false;
        $this->children = [$crotch];
        
        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return Criteria
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * @return Node[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return Criteria
     */
    public function getMostRecentCriteria()
    {
        return $this->mostRecentCriteria;
    }

    /**
     * @return boolean
     */
    public function isNegatingWholeChildren()
    {
        return $this->isNegatingWholeChildren;
    }

    /**
     * @param boolean $negatingWholeChildren
     */
    public function setNegatingWholeChildren($negatingWholeChildren)
    {
        $this->isNegatingWholeChildren = $negatingWholeChildren;
    }

    /**
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }
}
