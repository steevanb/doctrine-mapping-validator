<?php

namespace steevanb\DoctrineMappingValidator\OneToMany\Behavior;

use Doctrine\ORM\EntityManagerInterface;
use steevanb\DoctrineMappingValidator\Report\PassedReport;
use steevanb\DoctrineMappingValidator\Report\Report;

trait PropertiesTrait
{
    /** @var EntityManagerInterface */
    protected $manager;

    /** @var string */
    protected $managerClass;

    /** @var Report */
    protected $report;

    /** @var PassedReport */
    protected $passedReport;

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
    protected $rightEntityIdGetter;
}
