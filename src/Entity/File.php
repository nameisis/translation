<?php

namespace Nameisis\TranslationBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Nameisis\TranslationBundle\Manager\FileInterface;
use Nameisis\TranslationBundle\Model\File as FileModel;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @UniqueEntity(fields = {
 *     "hash"
 * })
 * @ORM\Table(
 *     name = "nameisis_translation_file",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(name = "hash_idx", columns = {"hash"})
 *     }
 * )
 * @ORM\Entity(
 *     repositoryClass = "Nameisis\TranslationBundle\Repository\FileRepository"
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class File extends FileModel implements FileInterface
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
     *     targetEntity = "Translation",
     *     mappedBy = "file",
     *     cascade = {
     *          "persist"
     *     }
     * )
     */
    protected $translations;

    /**
     * @var string|Domain
     * @ORM\ManyToOne(
     *     targetEntity = "Domain",
     *     inversedBy = "files",
     *     fetch = "EAGER"
     * )
     * @ORM\JoinColumn(
     *     name = "domain_id",
     *     referencedColumnName = "id"
     * )
     */
    protected $domain;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    /**
     * @return string|Domain
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param Domain $domain
     *
     * @return $this
     */
    public function setDomain(Domain $domain): File
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * @ORM\PrePersist()
     * @throws Exception
     *
     * @return $this
     */
    public function prePersist(): File
    {
        $this->setUpdatedAt(new DateTime());
        $this->setCreatedAt(new DateTime());

        return $this;
    }

    /**
     * @ORM\PreUpdate()
     * @throws Exception
     *
     * @return $this
     */
    public function preUpdate(): File
    {
        $this->setUpdatedAt(new DateTime());

        return $this;
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
    public function setId(int $id): File
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param ArrayCollection|Collection $translations
     *
     * @return $this
     */
    public function setTranslations($translations): File
    {
        $this->translations = $translations;

        return $this;
    }

    /**
     * @param Translation $translation
     *
     * @return $this
     */
    public function addTranslation(Translation $translation): File
    {
        $translation->setFile($this);
        $this->translations[] = $translation;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return \sprintf('%s.%s.%s', $this->domain->getName(), $this->locale, $this->extension);
    }

    /**
     * @param $name
     *
     * @return $this
     */
    public function setName($name): File
    {
        [, $locale, $extension] = explode('.', $name);

        $this->locale = $locale;
        $this->extension = $extension;

        return $this;
    }
}
