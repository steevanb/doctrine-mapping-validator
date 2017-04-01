[![status](https://img.shields.io/badge/status-dev-red.svg)](https://github.com/steevanb/doctrine-mapping-validator)

doctrine-mapping-validator
--------------------------

Validate your Doctrine mapping informations, and your related PHP code.

/!\ This code is in development, do not use it ! /!\

Installation
------------

doctrine.orm.metadata.yml.class: steevanb\DoctrineMappingValidator\MappingValidator\Yaml\ValidatedMappingYamlDriver

$validator = new MappingValidator($mapping);
$validator->setNamingStrategy(new DefaultNamingStrategy());
$validator->validate();
