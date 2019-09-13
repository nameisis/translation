<?php

namespace Selonia\TranslationBundle\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass()
 */
abstract class Domain
{
    /**
     * @var string
     * @ORM\Column(name = "name", type = "string", length = 255)
     */
    protected $name;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): Domain
    {
        $this->name = $name;

        return $this;
    }

    public function __toString()
    {
        return (string)$this->name;
    }
}
