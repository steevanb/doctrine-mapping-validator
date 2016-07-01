<?php

namespace steevanb\DoctrineMappingValidator\Bridge\Symfony3\DoctrineMappingValidatorBundle;

use steevanb\DoctrineMappingValidator\Bridge\Symfony3\DoctrineMappingValidatorBundle\DependencyInjection\CompilerPass\OneToManyValidatorsCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DoctrineMappingValidatorBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new OneToManyValidatorsCompilerPass());
    }
}
