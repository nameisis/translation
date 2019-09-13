<?php

namespace Selonia\TranslationBundle\Repository;

use DateTime;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;

class TranslationRepository extends EntityRepository
{
    /**
     * @return DateTime|null
     * @throws NonUniqueResultException
     */
    public function getLatestTranslationUpdatedAt()
    {
        $date = $this->createQueryBuilder('t')
            ->select('MAX(t.updatedAt)')
            ->getQuery()
            ->getSingleScalarResult();

        return !empty($date) ? new DateTime($date) : null;
    }

    /**
     * @param string $domain
     *
     * @return array
     */
    public function countByLocales($domain)
    {
        return $this->createQueryBuilder('t')
            ->select('COUNT(DISTINCT t.id) AS number, t.locale')
            ->innerJoin('t.transUnit', 'tu')
            ->leftJoin('tu.domain', 'td')
            ->andWhere('td.name = :domain')
            ->setParameter('domain', $domain)
            ->groupBy('t.locale')
            ->getQuery()
            ->getResult();
    }
}
