<?php

namespace Nameisis\TranslationBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Nameisis\TranslationBundle\DependencyInjection\Compiler\TranslatorPass;
use Nameisis\TranslationBundle\DependencyInjection\NameisisTranslationBundleExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class NameisisTranslationBundle extends Bundle
{
    public function getParent(): ?string
    {
        return \null;
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new TranslatorPass());
        $this->addEntities($container);
    }

    private function addEntities(ContainerBuilder $container): void
    {
        $dir = \sprintf('%s/Entity', __DIR__);
        if (\is_dir($dir)) {
            $container->addCompilerPass(DoctrineOrmMappingsPass::createAnnotationMappingDriver(['Nameisis\TranslationBundle\Entity'], [$dir]));
        }
    }

    public function getContainerExtension()
    {
        if ($this->extension === \null) {
            return new NameisisTranslationBundleExtension();
        }

        return $this->extension;
    }
}
