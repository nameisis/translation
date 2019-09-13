<?php

namespace Selonia\TranslationBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Selonia\TranslationBundle\Manager\TranslationInterface;
use Selonia\TranslationBundle\Model\Translation as TranslationModel;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @UniqueEntity(fields={
 *     "transUnit",
 *     "locale"
 * })
 * @ORM\Table(
 *     name="nameisis_translation_translation",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(name = "trans_unit_locale_idx", columns = {"trans_unit_id", "locale"})
 *     }
 * )
 * @ORM\Entity(
 *     repositoryClass = "Selonia\TranslationBundle\Repository\TranslationRepository"
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class Translation extends TranslationModel implements TranslationInterface
{
    /**
     * @var int
     * @ORM\Column(name = "id", type = "integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy = "IDENTITY")
     */
    protected $id;

    /**
     * @var TransUnit
     * @ORM\ManyToOne(
     *     targetEntity = "TransUnit",
     *     inversedBy = "translations",
     *     fetch = "EAGER"
     * )
     * @ORM\JoinColumn(
     *     name = "trans_unit_id",
     *     referencedColumnName = "id"
     * )
     */
    protected $transUnit;

    /**
     * @var File
     * @ORM\ManyToOne(
     *     targetEntity = "File",
     *     inversedBy = "translations",
     *     fetch = "EAGER"
     * )
     * @ORM\JoinColumn(
     *     name = "file_id",
     *     referencedColumnName = "id"
     * )
     */
    protected $file;

    /**
     * @var bool
     * @ORM\Column(name = "modified_manually", type = "boolean")
     */
    protected $modifiedManually = false;

    /**
     * @ORM\PrePersist()
     * @throws Exception
     *
     * @return $this
     */
    public function prePersist(): Translation
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
    public function preUpdate(): Translation
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
    public function setId(int $id): Translation
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return TransUnit
     */
    public function getTransUnit(): TransUnit
    {
        return $this->transUnit;
    }

    /**
     * @param TransUnit $transUnit
     *
     * @return $this
     */
    public function setTransUnit(TransUnit $transUnit): Translation
    {
        $this->transUnit = $transUnit;

        return $this;
    }

    /**
     * @return File
     */
    public function getFile(): ?File
    {
        return $this->file;
    }

    /**
     * @param File $file
     *
     * @return $this
     */
    public function setFile(File $file): Translation
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @return bool
     */
    public function isModifiedManually(): bool
    {
        return $this->modifiedManually;
    }

    /**
     * @param bool $modifiedManually
     *
     * @return $this
     */
    public function setModifiedManually(bool $modifiedManually): Translation
    {
        $this->modifiedManually = $modifiedManually;

        return $this;
    }
}
