<?php

namespace Nameisis\TranslationBundle\Storage;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;

class DoctrineORMStorage extends AbstractDoctrineStorage
{
    /**
     * {@inheritdoc}
     */
    public function getCountTransUnitByDomains()
    {
        $results = $this->getTransUnitRepository()
            ->countByDomains();
        $counts = [];
        foreach ($results as $row) {
            $counts[$row['domain']] = (int)$row['number'];
        }

        return $counts;
    }

    /**
     * {@inheritdoc}
     */
    public function getCountTranslationByLocales($domain)
    {
        $results = $this->getTranslationRepository()
            ->countByLocales($domain);
        $counts = [];
        foreach ($results as $row) {
            $counts[$row['locale']] = (int)$row['number'];
        }

        return $counts;
    }

    /**
     * @return object
     */
    protected function getTranslationRepository()
    {
        return $this->getManager()
            ->getRepository($this->classes['translation']);
    }

    /**
     * {@inheritdoc}
     */
    public function getLatestUpdatedAt()
    {
        if ($this->translationsTablesExist()) {
            return $this->getTranslationRepository()
                ->getLatestTranslationUpdatedAt();
        }

        return null;
    }

    /**
     * @return boolean
     */
    public function translationsTablesExist()
    {
        /** @var EntityManager $em */
        $em = $this->getManager();
        $connection = $em->getConnection();
        if ('pdo_sqlite' !== $connection->getDriver()
                ->getName()) {
            $params = $connection->getParams();
            if (isset($params['master'])) {
                $params = $params['master'];
            }
            unset($params['dbname'], $params['path'], $params['url']);
            $tmpConnection = DriverManager::getConnection($params);
            try {
                $dbExists = in_array($connection->getDatabase(), $tmpConnection->getSchemaManager()
                    ->listDatabases());
            } catch (DBALException $e) {
                $dbExists = false;
            }
            $tmpConnection->close();
            if (!$dbExists) {
                return false;
            }
        }

        $tables = [
            $em->getClassMetadata($this->getModelClass('trans_unit'))
                ->getTableName(),
            $em->getClassMetadata($this->getModelClass('translation'))
                ->getTableName(),
            $em->getClassMetadata($this->getModelClass('domain'))
                ->getTableName(),
        ];

        return $connection->getSchemaManager()
            ->tablesExist($tables);
    }
}
