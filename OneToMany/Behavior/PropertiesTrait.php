<?php

namespace steevanb\DoctrineMappingValidator\OneToMany\Behavior;

use Doctrine\ORM\EntityManagerInterface;
use steevanb\DoctrineMappingValidator\Report\ValidationReport;
use steevanb\DoctrineMappingValidator\Report\Report;

trait PropertiesTrait
{
    /** @var EntityManagerInterface */
    protected $manager;

    /** @var string */
    protected $managerClass;

    /** @var Report */
    protected $report;

    /** @var ValidationReport */
    protected $validationReport;

    /** @var string */
    protected $initializationTestName = 'Initialization';

    /** @var object */
    protected $leftEntity;

    /** @var string */
    protected $leftEntityClass;

    /** @var string */
    protected $leftEntityProperty;

    /** @var string */
    protected $leftEntityAdder;

    /** @var string */
    protected $leftEntityAdderTestName;

    /** @var string */
    protected $leftEntitySetter;

    /** @var string */
    protected $leftEntitySetterTestName;

    /** @var string */
    protected $leftEntityGetter;

    /** @var string */
    protected $leftEntityRemover;

    /** @var string */
    protected $leftEntityRemoverTestName;

    /** @var string */
    protected $leftEntityClearer;

    /** @var string */
    protected $leftEntityClearerTestName;

    /** @var object */
    protected $rightEntity;

    /** @var object */
    protected $rightEntity2;

    /** @var string */
    protected $rightEntityClass;

    /** @var string */
    protected $rightEntityProperty;

    /** @var string */
    protected $rightEntityGetter;

    /** @var string */
    protected $rightEntitySetter;

    /** @var string */
    protected $rightEntitySetterParameterName;

    /** @var string */
    protected $rightEntityIdGetter;
}
