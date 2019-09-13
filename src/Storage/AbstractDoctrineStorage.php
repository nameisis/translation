<?php

namespace Selonia\TranslationBundle\Storage;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bridge\Doctrine\ManagerRegistry;

abstract class AbstractDoctrineStorage implements StorageInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $managerName;

    /**
     * @var array
     */
    protected $classes;

    /**
     * @param ManagerRegistry $registry
     * @param array $managerName
     * @param array $classes
     */
    public function __construct(ManagerRegistry $registry, $managerName, array $classes)
    {
        $this->registry = $registry;
        $this->managerName = $managerName;
        $this->classes = $classes;
    }

    /**
     * {@inheritdoc}
     */
    public function clear($entityName = null)
    {
        $this->getManager()
            ->clear($entityName);
    }

    /**
     * @return ObjectManager
     */
    public function getManager()
    {
        return $this->registry->getManager($this->managerName);
    }

    /**
     * {@inheritdoc}
     */
    public function countTransUnits(array $locales = null, array $filters = null)
    {
        if ($this->translationsTablesExist()) {
            return $this->getTransUnitRepository()
                ->count($locales, $filters);
        }

        return 0;
    }

    /**
     * @return object
     */
    protected function getTransUnitRepository()
    {
        return $this->getManager()
            ->getRepository($this->classes['trans_unit']);
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $this->getManager()
            ->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getFileByHash($hash)
    {
        return $this->getFileRepository()
            ->findOneBy(['hash' => $hash]);
    }

    /**
     * @return object
     */
    protected function getFileRepository()
    {
        return $this->getManager()
            ->getRepository($this->classes['file']);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilesByLocalesAndDomains(array $locales, array $domains)
    {
        return $this->getFileRepository()
            ->findForLocalesAndDomains($locales, $domains);
    }

    /**
     * {@inheritdoc}
     */
    public function getModelClass($name)
    {
        if (!isset($this->classes[$name])) {
            throw new \RuntimeException(sprintf('No class defined for name "%s".', $name));
        }

        return $this->classes[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getTransUnitById($id)
    {
        return $this->getTransUnitRepository()
            ->findOneById($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getTransUnitByKeyAndDomain($key, $domain)
    {
        $key = mb_substr($key, 0, 255, 'UTF-8');
        $fields = [
            'key' => $key,
            'domain' => $domain,
        ];

        return $this->getTransUnitRepository()
            ->findOneBy($fields);
    }

    /**
     * {@inheritdoc}
     */
    public function getTransUnitDomains()
    {
        if ($this->translationsTablesExist()) {
            return $this->getDomainRepository()
                ->getAllDomains();
        }

        return [];
    }

    /**
     * @return object
     */
    protected function getDomainRepository()
    {
        return $this->getManager()
            ->getRepository($this->classes['domain']);
    }

    /**
     * {@inheritdoc}
     */
    public function getTransUnitDomainsByLocale()
    {
        return $this->getTransUnitRepository()
            ->getAllDomainsByLocale();
    }

    /**
     * {@inheritdoc}
     */
    public function getTransUnitList(array $locales = null, $rows = 20, $page = 1, array $filters = null)
    {
        if ($this->translationsTablesExist()) {
            return $this->getTransUnitRepository()
                ->getTransUnitList($locales, $rows, $page, $filters);
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getTransUnitsByLocaleAndDomain($locale, $domain)
    {
        return $this->getTransUnitRepository()
            ->getAllByLocaleAndDomain($locale, $domain);
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslationsFromFile($file, $onlyUpdated)
    {
        return $this->getTransUnitRepository()
            ->getTranslationsForFile($file, $onlyUpdated);
    }

    /**
     * {@inheritdoc}
     */
    public function persist($entity)
    {
        $this->getManager()
            ->persist($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($entity)
    {
        $this->getManager()
            ->remove($entity);
    }
}
