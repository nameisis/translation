<?php

namespace Selonia\TranslationBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Selonia\TranslationBundle\DependencyInjection\Compiler\TranslatorPass;
use Selonia\TranslationBundle\DependencyInjection\NameisisTranslationBundleExtension;
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
            $container->addCompilerPass(DoctrineOrmMappingsPass::createAnnotationMappingDriver(['Selonia\TranslationBundle\Entity'], [$dir]));
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
