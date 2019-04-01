[![status](https://img.shields.io/badge/status-dev-red.svg)](https://github.com/steevanb/doctrine-mapping-validator)

doctrine-mapping-validator
--------------------------

Validate your Doctrine mapping informations, and your related PHP code.

/!\ This code is in development, do not use it ! /!\

Installation
------------

Validate mapping each time _.orm.yml_ is readed:

```yml
# app/config/config.yml

doctrine.orm.metadata.yml.class: steevanb\DoctrineMappingValidator\MappingValidator\Yaml\ValidatedMappingYamlDriver
```

Validate your mappings :

```php
$validator = new MappingValidator();
$validator->setNamingStrategy(new DefaultNamingStrategy());

$finder = (new Finder())
    ->files()
    ->name('*.orm.yml')
    ->in('/var/www/foo');
/** @var SplFileInfo $file */
foreach ($finder as $file) {
    $yamlToMapping = new YamlToMapping($file->getPathname(), $validator);
    $errors = $yamlToMapping->validate();
    if (count($errors) > 0) {
        dump($file->getFilename(), $errors);
    }

    $validation = $validator->validate($mapping);
    if ($validation->hasErrorsOrWarnings()) {
        dump($file->getFilename(), $validation->getErrorsAndWarnings());
    }
}
```
