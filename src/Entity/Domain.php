<?php

namespace Selonia\TranslationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Selonia\TranslationBundle\Model\Domain as DomainModel;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @UniqueEntity(fields = {
 *     "name"
 * })
 * @ORM\Table(
 *     name = "nameisis_translation_domain",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(name = "domain_idx", columns = {"name"})
 *     }
 * )
 * @ORM\Entity(
 *     repositoryClass = "Selonia\TranslationBundle\Repository\DomainRepository"
 * )
 */
class Domain extends DomainModel
{
    /**
     * @var int
     * @ORM\Column(name = "id", type = "integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy = "IDENTITY")
     */
    protected $id;

    /**
     * @var ArrayCollection|Collection
     * @ORM\OneToMany(
     *     targetEntity = "File",
     *     mappedBy = "domain",
     *     cascade = {
     *          "persist"
     *     }
     * )
     */
    protected $files;

    /**
     * @var ArrayCollection|Collection
     * @ORM\OneToMany(
     *     targetEntity = "TransUnit",
     *     mappedBy = "domain",
     *     cascade = {
     *          "persist"
     *     }
     * )
     */
    protected $transUnits;

    public function __construct()
    {
        $this->files = new ArrayCollection();
        $this->transUnits = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId(int $id): Domain
    {
        $this->id = $id;

        return $this;
    }

}
