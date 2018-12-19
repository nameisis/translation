<?php

namespace Nameisis\TranslationBundle\Manager;

use Nameisis\TranslationBundle\Model\Domain;

interface TransUnitInterface
{
    /**
     * @return TranslationInterface[]
     */
    public function getTranslations();

    /**
     * @param string $locale
     *
     * @return bool
     */
    public function hasTranslation($locale);

    /**
     * @param string $locale
     *
     * @return TranslationInterface
     */
    public function getTranslation($locale);

    /**
     * @param string $key
     */
    public function setKey(string $key);

    /**
     * @param Domain $domain
     */
    public function setDomain(Domain $domain);
}
