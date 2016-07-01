<?php

namespace steevanb\DoctrineMappingValidator\Bridge\Symfony3\DoctrineMappingValidatorBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OneToManyValidatorsCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function process(ContainerBuilder $container)
    {
        $oneToManyValidator = $container->getDefinition('doctrine_mapping_validator.one_to_many');
        foreach ($container->findTaggedServiceIds('doctrine_mapping_validator.one_to_many') as $id => $config) {
            $reference = new Reference($id);
            $oneToManyValidator->addMethodCall('addValidator', [  ]);
        }
    }
}
