<?php

namespace Nameisis\TranslationBundle\Model;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\MappedSuperclass()
 */
abstract class TransUnit
{
    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(name = "key_name", type = "string", length = 255)
     */
    protected $key;

    /**
     * @var DateTime
     * @ORM\Column(name = "created_at", type = "datetime", nullable = false)
     */
    protected $createdAt;

    /**
     * @var DateTime
     * @ORM\Column(name = "updated_at", type = "datetime", nullable = true)
     */
    protected $updatedAt;

    /**
     * @return string
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public function setKey(string $key): TransUnit
    {
        $this->key = $key;

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
     * @return $this
     */
    public function setCreatedAt(DateTime $createdAt): TransUnit
    {
        $this->createdAt = $createdAt;

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
     * @return $this
     */
    public function setUpdatedAt(DateTime $updatedAt): TransUnit
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
