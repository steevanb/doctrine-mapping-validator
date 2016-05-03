validateAddRightObject
======================

- create $rightObject
- method_exists($leftObject, 'addFoo');
- method_exists($rightObject, 'getMappedBy');
- call $leftObject->addFoo($rightObject);
- method_exists $leftObject->getFoos()
- assert $leftObject->getFoos() contains $rightObject
- assert $rightObject->getMappedBy() === $leftObject
- call $manager->flush();
- assert $rightObject->getId() !== null
- call $manager->refresh($leftObject) and $manager->refresh($rightObject)
- method_exists $leftObject->getFoos()
- assert $leftObject->getFoos() contains $rightObject
- assert $rightObject->getMappedBy() === $leftObject
