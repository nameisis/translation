<?php

namespace Nameisis\TranslationBundle\Storage;

use DateTime;
use Nameisis\TranslationBundle\Manager\FileInterface;
use Nameisis\TranslationBundle\Manager\TransUnitInterface;
use Nameisis\TranslationBundle\Model\TransUnit;

interface StorageInterface
{
    public const STORAGE_ORM = 'orm';

    /**
     * @param object $entity
     */
    public function persist($entity);

    /**
     * @param object $entity
     */
    public function remove($entity);

    public function flush();

    /**
     * @param string $entityName
     */
    public function clear($entityName = null);

    /**
     * @param string $name
     */
    public function getModelClass($name);

    /**
     * @param array $locales
     * @param array $domains
     *
     * @return array
     */
    public function getFilesByLocalesAndDomains(array $locales, array $domains);

    /**
     * @param string $hash
     */
    public function getFileByHash($hash);

    /**
     * @return array
     */
    public function getTransUnitDomains();

    /**
     * @return array
     */
    public function getTransUnitDomainsByLocale();

    /**
     * @param int $id
     *
     * @return TransUnit
     */
    public function getTransUnitById($id);

    /**
     * @param string $key
     * @param string $domain
     *
     * @return TransUnitInterface
     */
    public function getTransUnitByKeyAndDomain($key, $domain);

    /**
     * @param string $locale
     * @param string $domain
     *
     * @return array
     */
    public function getTransUnitsByLocaleAndDomain($locale, $domain);

    /**
     * @param array $locales
     * @param int $rows
     * @param int $page
     * @param array $filters
     *
     * @return array
     */
    public function getTransUnitList(array $locales = null, $rows = 20, $page = 1, array $filters = null);

    /**
     * @param array $locales
     * @param array $filters
     *
     * @return int
     */
    public function countTransUnits(array $locales = null, array $filters = null);

    /**
     * @param FileInterface $file
     * @param boolean $onlyUpdated
     *
     * @return array
     */
    public function getTranslationsFromFile($file, $onlyUpdated);

    /**
     * @return DateTime|null
     */
    public function getLatestUpdatedAt();

    /**
     * @return array
     */
    public function getCountTransUnitByDomains();

    /**
     * @param string $domain
     *
     * @return array
     */
    public function getCountTranslationByLocales($domain);
}
