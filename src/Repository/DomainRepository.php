<?php

namespace Nameisis\TranslationBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Vairogs\Utils\Utils\Doctrine\SingleColumnArrayHydrator;

class DomainRepository extends EntityRepository
{
    /**
     * @return array
     */
    public function getAllDomains()
    {
        $this->loadCustomHydrator();

        return $this->createQueryBuilder('td')
            ->select('DISTINCT td.name as domain')
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
}
