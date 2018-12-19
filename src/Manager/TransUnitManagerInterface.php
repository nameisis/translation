<?php

namespace Nameisis\TranslationBundle\Manager;

interface TransUnitManagerInterface
{
    /**
     * @param array $locales
     *
     * @return TransUnitInterface
     */
    public function newInstance($locales = []);

    /**
     * @param string $keyName
     * @param string $domainName
     * @param boolean $flush
     *
     * @return TransUnitInterface
     */
    public function create($keyName, $domainName, $flush = false);

    /**
     * @param TransUnitInterface $transUnit
     * @param string $locale
     * @param string $content
     * @param FileInterface $file
     * @param boolean $flush
     *
     * @return TranslationInterface
     */
    public function addTranslation(TransUnitInterface $transUnit, $locale, $content, FileInterface $file = null, $flush = false);

    /**
     * @param TransUnitInterface $transUnit
     * @param string $locale
     * @param string $content
     * @param boolean $flush
     * @param boolean $merge
     *
     * @return TranslationInterface
     */
    public function updateTranslation(TransUnitInterface $transUnit, $locale, $content, $flush = false, $merge = false);

    /**
     * @param TransUnitInterface $transUnit
     * @param array $translations
     * @param boolean $flush
     */
    public function updateTranslationsContent(TransUnitInterface $transUnit, array $translations, $flush = false);
}
