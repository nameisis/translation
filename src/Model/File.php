<?php

namespace Nameisis\TranslationBundle\Model;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\MappedSuperclass()
 */
abstract class File
{
    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(name = "locale", type = "string", length = 10)
     */
    protected $locale;

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(name = "extension", type = "string", length = 10)
     */
    protected $extension;

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(name = "path", type = "string", length = 255)
     */
    protected $path;

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(name = "hash", type = "string", length = 255)
     */
    protected $hash;

    /**
     * @var DateTime
     * @ORM\Column(name = "updated_at", type = "datetime")
     */
    protected $updatedAt;

    /**
     * @var DateTime
     * @ORM\Column(name = "created_at", type = "datetime")
     */
    protected $createdAt;

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     *
     * @return $this
     */
    public function setLocale(string $locale): File
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * @param string $extension
     *
     * @return $this
     */
    public function setExtension(string $extension): File
    {
        $this->extension = $extension;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setPath(string $path): File
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     *
     * @return $this
     */
    public function setHash(string $hash): File
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime $updatedAt
     *
     * @return File
     */
    public function setUpdatedAt(DateTime $updatedAt): File
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     *
     * @return File
     */
    public function setCreatedAt(DateTime $createdAt): File
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
