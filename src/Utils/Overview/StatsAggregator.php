<?php

namespace Nameisis\TranslationBundle\Utils\Overview;

use Nameisis\TranslationBundle\Manager\LocaleManagerInterface;
use Nameisis\TranslationBundle\Storage\StorageInterface;

class StatsAggregator
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var LocaleManagerInterface
     */
    private $localeManager;

    /**
     * @param StorageInterface $storage
     * @param LocaleManagerInterface $localeManager
     */
    public function __construct(StorageInterface $storage, LocaleManagerInterface $localeManager)
    {
        $this->storage = $storage;
        $this->localeManager = $localeManager;
    }

    /**
     * @return array
     */
    public function getStats()
    {
        $stats = [];
        if ($this->storage->translationsTablesExist()) {
            foreach ($this->storage->getCountTransUnitByDomains() as $domain => $total) {
                $stats[$domain] = [];
                $byLocale = $this->storage->getCountTranslationByLocales($domain);
                foreach ($this->localeManager->getLocales() as $locale) {
                    $localeCount = isset($byLocale[$locale]) ? $byLocale[$locale] : 0;
                    $stats[$domain][$locale] = [
                        'keys' => $total,
                        'translated' => $localeCount,
                        'completed' => ($total > 0) ? floor(($localeCount / $total) * 100) : 0,
                    ];
                }
            }
        }

        return $stats;
    }
}
