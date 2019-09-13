<?php

namespace Selonia\TranslationBundle\Translation;

use Selonia\TranslationBundle\EventDispatcher\Event\GetDatabaseResourcesEvent;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Translation\Translator as BaseTranslator;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use function in_array;

class Translator extends BaseTranslator implements TranslatorInterface
{
    public function addDatabaseResources()
    {
        $this->registerMissing();
        $file = sprintf('%s/database.resources.php', $this->options['cache_dir']);
        $cache = new ConfigCache($file, $this->options['debug']);
        if (!$cache->isFresh()) {
            $event = new GetDatabaseResourcesEvent();
            $this->container->get('event_dispatcher')
                ->dispatch('nameisis_translation.event.get_database_resources', $event);
            $resources = $event->getResources();
            $metadata = [];
            foreach ($resources ?? [] as $resource) {
                $metadata[] = new DatabaseFreshResource($resource['locale'], $resource['domain']);
            }
            $content = sprintf("<?php return %s;", var_export($resources, true));
            $cache->write($content, $metadata);
        } else {
            $resources = include $file;
        }
        foreach ($resources ?? [] as $resource) {
            $this->addResource('database', 'DB', $resource['locale'], $resource['domain']);
        }
    }

    private function registerMissing()
    {
        $this->addLoader('xliff', new XliffFileLoader());
        $this->addLoader('yml', new YamlFileLoader());
    }

    /**
     * @param string $format
     *
     * @throws RuntimeException
     * @return LoaderInterface
     */
    public function getLoader($format)
    {
        $this->registerMissing();
        $loader = null;
        $i = 0;
        if (in_array($format, ['yml', 'xliff'])) {
            if ($format === 'yml') {
                $loader = $this->container->get(array_search(['yaml'], $this->loaderIds, true));
            } else {
                $loader = $this->container->get(array_search(['xlf'], $this->loaderIds, true));
            }
        } else {
            $ids = array_keys($this->loaderIds);
            while ($i < count($ids) && null === $loader) {
                if (in_array($format, $this->loaderIds[$ids[$i]])) {
                    $loader = $this->container->get($ids[$i]);
                }
                $i++;
            }
        }
        if (!($loader instanceof LoaderInterface)) {
            throw new RuntimeException(sprintf('No loader found for "%s" format.', $format));
        }

        return $loader;
    }

    /**
     * @return array
     */
    public function getFormats()
    {
        $allFormats = [];
        foreach ($this->loaderIds as $id => $formats) {
            foreach ($formats as $format) {
                if ('database' !== $format) {
                    $allFormats[] = $format;
                }
            }
        }

        return $allFormats;
    }

    /**
     * @param array $locales
     */
    public function removeLocalesCacheFiles(array $locales)
    {
        foreach ($locales as $locale) {
            $this->removeCacheFile($locale);
        }
        $file = sprintf('%s/database.resources.php', $this->options['cache_dir']);
        if (file_exists($file)) {
            $this->invalidateSystemCacheForFile($file);
            unlink($file);
        }
        $metadata = $file.'.meta';
        if (file_exists($metadata)) {
            $this->invalidateSystemCacheForFile($metadata);
            unlink($metadata);
        }
    }

    /**
     * @param string $locale
     *
     * @return boolean
     */
    public function removeCacheFile($locale)
    {
        $localeExploded = explode('_', $locale);
        $finder = new Finder();
        $finder->files()
            ->in($this->options['cache_dir'])
            ->name(sprintf('/catalogue\.%s.*\.php$/', $localeExploded[0]));
        $deleted = true;
        foreach ($finder as $file) {
            $path = $file->getRealPath();
            $this->invalidateSystemCacheForFile($path);
            $deleted = unlink($path);
            $metadata = $path.'.meta';
            if (file_exists($metadata)) {
                $this->invalidateSystemCacheForFile($metadata);
                unlink($metadata);
            }
        }

        return $deleted;
    }

    /**
     * @param string $path
     *
     * @throws RuntimeException
     */
    protected function invalidateSystemCacheForFile($path)
    {
        if (ini_get('apc.enabled') && function_exists('apc_delete_file')) {
            if (apc_exists($path) && !apc_delete_file($path)) {
                throw new RuntimeException(sprintf('Failed to clear APC Cache for file %s', $path));
            }
        } elseif ('cli' === php_sapi_name() ? ini_get('opcache.enable_cli') : ini_get('opcache.enable')) {
            if (function_exists("opcache_invalidate") && !opcache_invalidate($path, true)) {
                throw new RuntimeException(sprintf('Failed to clear OPCache for file %s', $path));
            }
        }
    }
}
