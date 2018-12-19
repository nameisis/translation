<?php

namespace Nameisis\TranslationBundle\Translation;

use Symfony\Component\Config\Resource\SelfCheckingResourceInterface;

class DatabaseFreshResource implements SelfCheckingResourceInterface
{
    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $domain;

    /**
     *
     * @param string $locale
     * @param string $domain
     */
    public function __construct($locale, $domain)
    {
        $this->locale = $locale;
        $this->domain = $domain;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->getResource();
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return sprintf('%s:%s', $this->locale, $this->domain);
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($timestamp)
    {
        return true;
    }
}
