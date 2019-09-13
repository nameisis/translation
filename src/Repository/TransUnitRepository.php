<?php

namespace Selonia\TranslationBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Selonia\TranslationBundle\Model\File as ModelFile;
use Vairogs\Utils\Utils\Doctrine\SingleColumnArrayHydrator;

class TransUnitRepository extends EntityRepository
{
    /**
     * @return array
     */
    public function getAllDomainsByLocale()
    {
        return $this->createQueryBuilder('tu')
            ->select('te.locale, td.name as domain')
            ->leftJoin('tu.translations', 'te')
            ->leftJoin('tu.domain', 'td')
            ->addGroupBy('te.locale')
            ->addGroupBy('td.id')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return array
     */
    public function getAllByLocaleAndDomain($locale, $domain)
    {
        return $this->createQueryBuilder('tu')
            ->select('tu, te, td')
            ->leftJoin('tu.translations', 'te')
            ->leftJoin('tu.domain', 'td')
            ->where('td.name = :domain')
            ->andWhere('te.locale = :locale')
            ->setParameter('domain', $domain)
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return array
     */
    public function getAllDomains()
    {
        $this->loadCustomHydrator();

        return $this->createQueryBuilder('tu')
            ->select('DISTINCT td.name as domain')
            ->innerJoin('tu.domain', 'td')
            ->orderBy('td.name', 'ASC')
            ->getQuery()
            ->getResult('SingleColumnArrayHydrator');
    }

    protected function loadCustomHydrator()
    {
        $config = $this->getEntityManager()
            ->getConfiguration();
        $config->addCustomHydrationMode('SingleColumnArrayHydrator', SingleColumnArrayHydrator::class);
    }

    /**
     * @param array $locales
     * @param int $rows
     * @param int $page
     * @param array $filters
     *
     * @return array
     */
    public function getTransUnitList(array $locales = null, $rows = 20, $page = 1, array $filters = null)
    {
        $this->loadCustomHydrator();

        $sortColumn = isset($filters['sidx']) ? $filters['sidx'] : 'id';
        $order = isset($filters['sord']) ? $filters['sord'] : 'ASC';

        $builder = $this->createQueryBuilder('tu')
            ->select('tu.id');

        $this->addTransUnitFilters($builder, $filters);
        $this->addTranslationFilter($builder, $locales, $filters);

        $ids = $builder->orderBy(sprintf('tu.%s', $sortColumn), $order)
            ->setFirstResult($rows * ($page - 1))
            ->setMaxResults($rows)
            ->getQuery()
            ->getResult('SingleColumnArrayHydrator');

        $transUnits = [];

        if (count($ids) > 0) {
            $qb = $this->createQueryBuilder('tu');

            $transUnits = $qb->select('tu, te, td')
                ->leftJoin('tu.translations', 'te')
                ->leftJoin('tu.domain', 'td')
                ->andWhere($qb->expr()
                    ->in('tu.id', $ids))
                ->andWhere($qb->expr()
                    ->in('te.locale', $locales))
                ->orderBy(sprintf('tu.%s', $sortColumn), $order)
                ->getQuery()
                ->getArrayResult();
        }

        return $transUnits;
    }

    /**
     * @param QueryBuilder $builder
     * @param array $filters
     */
    protected function addTransUnitFilters(QueryBuilder $builder, array $filters = null)
    {
        if (isset($filters['_search']) && $filters['_search']) {
            if (!empty($filters['domain'])) {
                $builder->leftJoin('tu.domain', 'td')
                    ->andWhere($builder->expr()
                        ->like('td.name', ':domain'))
                    ->setParameter('domain', sprintf('%%%s%%', $filters['domain']));
            }

            if (!empty($filters['key'])) {
                $builder->andWhere($builder->expr()
                    ->like('tu.key', ':key'))
                    ->setParameter('key', sprintf('%%%s%%', $filters['key']));
            }
        }
    }

    /**
     * @param QueryBuilder $builder
     * @param array $locales
     * @param array $filters
     */
    protected function addTranslationFilter(QueryBuilder $builder, array $locales = null, array $filters = null)
    {
        if (null !== $locales) {
            $qb = $this->createQueryBuilder('tu');
            $qb->select('DISTINCT tu.id')
                ->leftJoin('tu.translations', 't')
                ->where($qb->expr()
                    ->in('t.locale', $locales));

            foreach ($locales as $locale) {
                if (!empty($filters[$locale])) {
                    $qb->andWhere($qb->expr()
                        ->like('t.content', ':content'))
                        ->setParameter('content', sprintf('%%%s%%', $filters[$locale]));

                    $qb->andWhere($qb->expr()
                        ->eq('t.locale', ':locale'))
                        ->setParameter('locale', sprintf('%s', $locale));
                }
            }

            $ids = $qb->getQuery()
                ->getResult('SingleColumnArrayHydrator');

            if (count($ids) > 0) {
                $builder->andWhere($builder->expr()
                    ->in('tu.id', $ids));
            }
        }
    }

    /**
     * @param array $locales
     * @param array $filters
     *
     * @return int
     */
    public function count(array $locales = null, array $filters = null)
    {
        $this->loadCustomHydrator();

        $builder = $this->createQueryBuilder('tu')
            ->select('COUNT(DISTINCT tu.id) AS number');

        $this->addTransUnitFilters($builder, $filters);
        $this->addTranslationFilter($builder, $locales, $filters);

        return (int)$builder->getQuery()
            ->getResult(Query::HYDRATE_SINGLE_SCALAR);
    }

    /**
     * @return array
     */
    public function countByDomains()
    {
        return $this->createQueryBuilder('tu')
            ->select('COUNT(DISTINCT tu.id) AS number, td.name as domain')
            ->leftJoin('tu.domain', 'td')
            ->groupBy('td.id')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @param ModelFile $file
     * @param boolean $onlyUpdated
     *
     * @return array
     */
    public function getTranslationsForFile(ModelFile $file, $onlyUpdated)
    {
        $builder = $this->createQueryBuilder('tu')
            ->select('tu.key, te.content')
            ->leftJoin('tu.translations', 'te')
            ->where('te.file = :file')
            ->setParameter('file', $file->getId())
            ->orderBy('te.id', 'asc');

        if ($onlyUpdated) {
            $builder->andWhere($builder->expr()
                ->gt('te.updatedAt', 'te.createdAt'));
        }

        $results = $builder->getQuery()
            ->getArrayResult();

        $translations = [];
        foreach ($results as $result) {
            $translations[$result['key']] = $result['content'];
        }

        return $translations;
    }
}
