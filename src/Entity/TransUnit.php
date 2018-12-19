<?php

namespace Nameisis\TranslationBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Nameisis\TranslationBundle\Manager\TranslationInterface;
use Nameisis\TranslationBundle\Manager\TransUnitInterface;
use Nameisis\TranslationBundle\Model\Domain as DomainModel;
use Nameisis\TranslationBundle\Model\TransUnit as TransUnitModel;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @UniqueEntity(fields={
 *     "key",
 *     "domain"
 * })
 * @ORM\Table(
 *     name="nameisis_translation_trans_unit",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(name = "key_domain_idx", columns = {"key_name", "domain_id"})
 *     }
 * )
 * @ORM\Entity(
 *     repositoryClass = "Nameisis\TranslationBundle\Repository\TransUnitRepository"
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class TransUnit extends TransUnitModel implements TransUnitInterface
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
     *     mappedBy = "transUnit",
     *     cascade = {
     *          "persist"
     *     }
     * )
     */
    protected $translations;

    /**
     * @var Domain
     * @ORM\ManyToOne(
     *     targetEntity = "Domain",
     *     inversedBy = "transUnits",
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
     * @ORM\PrePersist()
     * @throws Exception
     *
     * @return $this
     */
    public function prePersist(): TransUnit
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
    public function preUpdate(): TransUnit
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
    public function setId(int $id): TransUnit
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param Translation $translation
     *
     * @return $this
     */
    public function removeTranslation(Translation $translation): TransUnit
    {
        $this->translations->removeElement($translation);

        return $this;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function filterNotBlankTranslations()
    {
        return $this->getTranslations()
            ->filter(function (TranslationInterface $translation) {
                $content = $translation->getContent();

                return !empty($content);
            });
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param Collection $collection
     *
     * @return $this
     */
    public function setTranslations(Collection $collection): TransUnit
    {
        $this->translations = new ArrayCollection();
        foreach ($collection as $translation) {
            $this->addTranslation($translation);
        }

        return $this;
    }

    /**
     * @param Translation $translation
     *
     * @return $this
     */
    public function addTranslation(Translation $translation): TransUnit
    {
        $translation->setTransUnit($this);
        $this->translations[] = $translation;

        return $this;
    }

    /**
     * @param string $locale
     *
     * @return bool
     */
    public function hasTranslation($locale): bool
    {
        return null !== $this->getTranslation($locale);
    }

    /**
     * @param string $locale
     *
     * @return TranslationInterface|null
     */
    public function getTranslation($locale): ?TranslationInterface
    {
        foreach ($this->getTranslations() as $translation) {
            if ($translation->getLocale() === $locale) {
                return $translation;
            }
        }

        return null;
    }

    /**
     * @return DomainModel|null
     */
    public function getDomain(): ?DomainModel
    {
        return $this->domain;
    }

    /**
     * @param DomainModel $domain
     *
     * @return TransUnit
     */
    public function setDomain(DomainModel $domain): TransUnit
    {
        $this->domain = $domain;

        return $this;
    }
}
