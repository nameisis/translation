<?php

namespace Nameisis\TranslationBundle\EventDispatcher;

use Nameisis\TranslationBundle\EventDispatcher\Event\GetDatabaseResourcesEvent;
use Nameisis\TranslationBundle\Storage\StorageInterface;

class GetDatabaseResourcesListener
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var string
     */
    private $storageType;

    /**
     * @param StorageInterface $storage
     * @param $storageType
     */
    public function __construct(StorageInterface $storage, $storageType)
    {
        $this->storage = $storage;
        $this->storageType = $storageType;
    }

    /**
     * @param GetDatabaseResourcesEvent $event
     */
    public function onGetDatabaseResources(GetDatabaseResourcesEvent $event)
    {
        if (StorageInterface::STORAGE_ORM == $this->storageType && !$this->storage->translationsTablesExist()) {
            $resources = [];
        } else {
            $resources = $this->storage->getTransUnitDomainsByLocale();
        }
        $event->setResources($resources);
    }
}
