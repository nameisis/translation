<?php

namespace Nameisis\TranslationBundle\Repository;

use Doctrine\ORM\EntityRepository;

class FileRepository extends EntityRepository
{
    /**
     * @param array $locales
     * @param array $domains
     *
     * @return array
     */
    public function findForLocalesAndDomains(array $locales, array $domains)
    {
        $builder = $this->createQueryBuilder('f');
        $builder->addSelect('d');
        $builder->leftJoin('f.domain', 'd');

        if (count($locales) > 0) {
            $builder->andWhere($builder->expr()
                ->in('f.locale', $locales));
        }

        if (count($domains) > 0) {
            $builder->andWhere($builder->expr()
                ->in('d.name', $domains));
        }

        return $builder->getQuery()
            ->getResult();
    }
}
