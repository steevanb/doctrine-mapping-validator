validateAddRightObject
======================

- create $rightObject
- method_exists($leftObject, 'addFoo');
- method_exists($rightObject, 'getMappedBy');
- call $leftObject->addFoo($rightObject);
- assert $rightObject->getMappedBy() === $leftObject
- call $manager->flush();
- assert $rightObject->getId() !== null

- call $manager->refresh($leftObject) and $manager->refresh($rightObject)
- assert $leftObject->getFoos() contains $rightObject

- la même avec setFoos()
