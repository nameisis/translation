<?php

namespace Nameisis\TranslationBundle\Model;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\MappedSuperclass()
 */
abstract class Translation
{
    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(name = "locale", type = "string", length = 10)
     */
    protected $locale;

    /**
     * @var string
     * @Assert\NotBlank(groups = {
     *     "contentNotBlank"
     * })
     * @ORM\Column(name = "content", type = "text")
     */
    protected $content;

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
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @param $locale
     *
     * @return $this
     */
    public function setLocale($locale): Translation
    {
        $this->locale = $locale;
        $this->content = '';

        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return $this
     */
    public function setContent(string $content): Translation
    {
        $this->content = $content;

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
    public function setCreatedAt(DateTime $createdAt): Translation
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
    public function setUpdatedAt(DateTime $updatedAt): Translation
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
