<?php

namespace Selonia\TranslationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TranslatorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $loaders = [];
        $loadersReferences = [];
        $loadersReferencesById = [];
        foreach ($container->findTaggedServiceIds('translation.loader', true) as $id => $attributes) {
            $loaders[$id][] = $attributes[0]['alias'];
            $loadersReferencesById[$id] = new Reference($id);
            $loadersReferences[$attributes[0]['alias']] = new Reference($id);
        }
        if ($container->hasDefinition('nameisis_translation.translator')) {
            $serviceRefs = array_merge($loadersReferencesById, ['event_dispatcher' => new Reference('event_dispatcher')]);
            $container->findDefinition('nameisis_translation.translator')
                ->replaceArgument(0, ServiceLocatorTagPass::register($container, $serviceRefs))
                ->replaceArgument(3, $loaders);
        }
        if ($container->hasDefinition('nameisis_translation.importer.file')) {
            $container->findDefinition('nameisis_translation.importer.file')
                ->replaceArgument(0, $loadersReferences);
        }

        if ($container->hasDefinition('nameisis_translation.exporter_collector')) {
            foreach ($container->findTaggedServiceIds('nameisis_translation.exporter') as $id => $attributes) {
                $container->getDefinition('nameisis_translation.exporter_collector')
                    ->addMethodCall('addExporter', [$id, new Reference($id)]);
            }
        }
    }
}
