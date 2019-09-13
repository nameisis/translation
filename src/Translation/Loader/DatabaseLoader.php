<?php

namespace Selonia\TranslationBundle\Translation\Loader;

use Selonia\TranslationBundle\Storage\StorageInterface;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

class DatabaseLoader implements LoaderInterface
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @param StorageInterface $storage
     */
    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        $catalogue = new MessageCatalogue($locale);
        $transUnits = $this->storage->getTransUnitsByLocaleAndDomain($locale, $domain);
        foreach ($transUnits as $transUnit) {
            foreach ($transUnit['translations'] as $translation) {
                if ($translation['locale'] == $locale) {
                    $catalogue->set($transUnit['key'], $translation['content'], $domain);
                }
            }
        }

        return $catalogue;
    }
}
