<?php

namespace Selonia\TranslationBundle\Manager;

class LocaleManager implements LocaleManagerInterface
{
    /**
     * @var array
     */
    protected $managedLocales;

    /**
     * @param array $managedLocales
     */
    public function __construct(array $managedLocales)
    {
        $this->managedLocales = $managedLocales;
    }

    /**
     * @return array
     */
    public function getLocales()
    {
        return $this->managedLocales;
    }
}
