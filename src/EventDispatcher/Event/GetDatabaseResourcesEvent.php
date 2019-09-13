<?php

namespace Selonia\TranslationBundle\EventDispatcher\Event;

use Symfony\Component\EventDispatcher\Event;

class GetDatabaseResourcesEvent extends Event
{
    /**
     * @var array
     */
    private $resources;

    /**
     * @return array
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * @param $resources
     */
    public function setResources($resources)
    {
        $this->resources = $resources;
    }
}
