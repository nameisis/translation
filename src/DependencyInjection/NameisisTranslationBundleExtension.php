<?php

namespace Selonia\TranslationBundle\DependencyInjection;

use Doctrine\ORM\Events;
use Exception;
use Selonia\TranslationBundle\Storage\StorageInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Vairogs\Utils\Utils\Iter;

class NameisisTranslationBundleExtension extends Extension implements PrependExtensionInterface
{
    public const ALIAS = 'selonia.translation';
    public const EXTENSION = 'nameisis_translation';

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('twig')) {
            return;
        }

        $container->prependExtensionConfig('twig', [
            'paths' => [
                \sprintf('%s/../Resources/views', __DIR__) => 'NameisisTranslationBundle',
            ],
        ]);
    }

    public function getAlias(): string
    {
        return self::EXTENSION;
    }

    /**
     * @param array $configs
     * @param ContainerBuilder $container
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration($this->getAlias());
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        sort($config['locales']);
        $config['storage'] = StorageInterface::STORAGE_ORM;

        foreach (Iter::makeOneDimension($config, self::EXTENSION) as $key => $value) {
            $container->setParameter($key, $value);
        }

        $objectManager = $config['object_manager'] ?? null;

        $this->buildTranslatorDefinition($container);
        $this->buildTranslationStorageDefinition($container, $objectManager);

        if (true === $container->getParameter(\sprintf('%s.dev_tools.enable', self::EXTENSION))) {
            $this->buildDevServicesDefinition($container);
        }
        $this->registerTranslatorConfiguration($config, $container);
    }

    public function buildTranslatorDefinition(ContainerBuilder $container)
    {
        $translator = new Definition();
        $translator->setClass('%nameisis_translation.translator.class%');
        $arguments = [
            new Reference('service_container'),
            new Reference('translator.formatter.default'),
            new Parameter('kernel.default_locale'),
            [],
            new Parameter('nameisis_translation.translator.options'),
        ];
        $translator->setPublic(true);
        $translator->setArguments($arguments);
        $translator->addMethodCall('setConfigCacheFactory', [
            new Reference('config_cache_factory'),
        ]);
        $container->setDefinition('nameisis_translation.translator', $translator);
    }

    protected function buildTranslationStorageDefinition(ContainerBuilder $container, $objectManager)
    {
        $args = [
            new Reference('doctrine'),
            $objectManager ?? 'default',
        ];
        $this->createDoctrineMappingDriver($container, 'nameisis_translation.orm.metadata.xml', '%doctrine.orm.metadata.xml.class%');
        $metadataListener = new Definition();
        $metadataListener->setClass('%nameisis_translation.orm.listener.class%');
        $metadataListener->addTag('doctrine.event_listener', [
            'event' => Events::loadClassMetadata,
        ]);
        $container->setDefinition('nameisis_translation.orm.listener', $metadataListener);
        $args[] = [
            'trans_unit' => new Parameter(sprintf('nameisis_translation.%s.trans_unit.class', StorageInterface::STORAGE_ORM)),
            'translation' => new Parameter(sprintf('nameisis_translation.%s.translation.class', StorageInterface::STORAGE_ORM)),
            'file' => new Parameter(sprintf('nameisis_translation.%s.file.class', StorageInterface::STORAGE_ORM)),
            'domain' => new Parameter(sprintf('nameisis_translation.%s.domain.class', StorageInterface::STORAGE_ORM)),
        ];
        $storageDefinition = new Definition();
        $storageDefinition->setClass($container->getParameter(sprintf('nameisis_translation.%s.translation_storage.class', StorageInterface::STORAGE_ORM)));
        $storageDefinition->setArguments($args);
        $storageDefinition->setPublic(true);
        $container->setDefinition('nameisis_translation.translation_storage', $storageDefinition);
    }

    protected function buildDevServicesDefinition(ContainerBuilder $container)
    {
        $container->getDefinition('nameisis_translation.data_grid.request_handler')
            ->addMethodCall('setProfiler', [new Reference('profiler')]);
        $tokenFinderDefinition = new Definition();
        $tokenFinderDefinition->setClass($container->getParameter('nameisis_translation.token_finder.class'));
        $tokenFinderDefinition->setArguments([
            new Reference('profiler'),
            new Parameter('nameisis_translation.token_finder.limit'),
        ]);
        $container->setDefinition('nameisis_translation.token_finder', $tokenFinderDefinition);
    }

    protected function registerTranslatorConfiguration(array $config, ContainerBuilder $container)
    {
        $alias = $container->setAlias('translator', 'nameisis_translation.translator');
        $alias->setPublic(true);
        $translator = $container->findDefinition('nameisis_translation.translator');
        $translator->addMethodCall('setFallbackLocales', [[$config['locale']]]);
        $registration = $config['resources_registration'];
        // Discover translation directories
        if ('all' === $registration['type'] || 'files' === $registration['type']) {
            $dirs = [];
            if (class_exists('Symfony\Component\Validator\Validation')) {
                $r = new \ReflectionClass('Symfony\Component\Validator\Validation');
                $dirs[] = dirname($r->getFilename()).'/Resources/translations';
            }
            if (class_exists('Symfony\Component\Form\Form')) {
                $r = new \ReflectionClass('Symfony\Component\Form\Form');
                $dirs[] = dirname($r->getFilename()).'/Resources/translations';
            }
            if (class_exists('Symfony\Component\Security\Core\Exception\AuthenticationException')) {
                $r = new \ReflectionClass('Symfony\Component\Security\Core\Exception\AuthenticationException');
                if (is_dir($dir = dirname($r->getFilename()).'/../Resources/translations')) {
                    $dirs[] = $dir;
                }
            }
            $overridePath = $container->getParameter('kernel.root_dir').'/Resources/%s/translations';
            foreach ($container->getParameter('kernel.bundles') as $bundle => $class) {
                $reflection = new \ReflectionClass($class);
                if (is_dir($dir = dirname($reflection->getFilename()).'/Resources/translations')) {
                    $dirs[] = $dir;
                }
                if (is_dir($dir = sprintf($overridePath, $bundle))) {
                    $dirs[] = $dir;
                }
            }
            if (is_dir($dir = $container->getParameter('kernel.root_dir').'/Resources/translations')) {
                $dirs[] = $dir;
            }

            if (is_dir($dir = $container->getParameter('kernel.project_dir').'/translations')) {
                $dirs[] = $dir;
            }

            // Register translation resources
            if (count($dirs) > 0) {
                foreach ($dirs as $dir) {
                    $container->addResource(new DirectoryResource($dir));
                }
                $finder = Finder::create();
                $finder->files();
                $finder->filter(function (\SplFileInfo $file) {
                    return 2 === substr_count($file->getBasename(), '.') && preg_match('/\.\w+$/', $file->getBasename());
                });
                $finder->in($dirs);
                foreach ($finder as $file) {
                    // filename is domain.locale.format
                    list($domain, $locale, $format) = explode('.', $file->getBasename(), 3);
                    $translator->addMethodCall('addResource', [$format, (string)$file, $locale, $domain]);
                }
            }
        }
        // add resources from database
        if ('all' === $registration['type'] || 'database' === $registration['type']) {
            $translator->addMethodCall('addDatabaseResources', []);
        }
    }

    protected function createDoctrineMappingDriver(ContainerBuilder $container, $driverId, $driverClass)
    {
        $driverDefinition = new Definition($driverClass, [
            [dirname(__DIR__).'/Resources/config/model' => 'Selonia\TranslationBundle\Model'],
        ]);
        $driverDefinition->setPublic(false);
        $container->setDefinition($driverId, $driverDefinition);
    }
}
