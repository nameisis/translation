<?php

namespace Selonia\TranslationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpKernel\Kernel;

class Configuration implements ConfigurationInterface
{
    private const STORAGE_TYPES = [
        'all',
        'files',
        'database',
    ];
    private const INPUT_TYPES = [
        'text',
        'textarea',
    ];
    private $alias;

    public function __construct(string $alias)
    {
        $this->alias = $alias;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        if (Kernel::VERSION_ID >= 40200) {
            $treeBuilder = new TreeBuilder($this->getAlias());
            $node = $treeBuilder->getRootNode();
        } else {
            $treeBuilder = new TreeBuilder();
            $node = $treeBuilder->root($this->getAlias());
        }

        // @formatter:off
        $node
            ->canBeEnabled()
            ->children()
                ->scalarNode('layout')
                    ->cannotBeEmpty()
                    ->defaultValue('@NameisisTranslationBundle/layout.html.twig')
                ->end()
                ->scalarNode('locale')
                    ->cannotBeEmpty()
                    ->defaultValue('en')
                ->end()
                ->arrayNode('locales')
                    ->cannotBeEmpty()
                    ->defaultValue(['en'])
                    ->requiresAtLeastOneElement()
                    ->prototype('scalar')->end()
                ->end()
                ->scalarNode('grid_input_type')
                    ->cannotBeEmpty()
                    ->defaultValue('textarea')
                    ->validate()
                        ->ifNotInArray(self::INPUT_TYPES)
                        ->thenInvalid('The input type "%s" is not supported. Please use one of the following types: '.implode(', ', self::INPUT_TYPES))
                    ->end()
                ->end()
                ->booleanNode('grid_toggle_similar')
                    ->defaultValue(false)
                ->end()
                ->scalarNode('object_manager')
                ->end()
                ->arrayNode('resources_registration')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('type')
                            ->cannotBeEmpty()
                            ->defaultValue('all')
                            ->validate()
                                ->ifNotInArray(self::STORAGE_TYPES)
                                ->thenInvalid('Invalid registration type "%s". Please use one of the following types: '.implode(', ', self::STORAGE_TYPES))
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('exporter')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('json_hierarchical_format')
                            ->defaultValue(false)
                        ->end()
                        ->booleanNode('use_yml_tree')
                            ->defaultValue(true)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('dev_tools')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enable')
                            ->defaultValue(false)
                        ->end()
                        ->booleanNode('create_missing')
                            ->defaultValue(false)
                        ->end()
                        ->scalarNode('file_format')
                            ->defaultValue('yml')
                        ->end()
                    ->end()
                ->end()
        ->end();
        // @formatter:on

        return $treeBuilder;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }
}
