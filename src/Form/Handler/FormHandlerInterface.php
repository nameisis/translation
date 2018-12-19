<?php

namespace Nameisis\TranslationBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

interface FormHandlerInterface
{
    /**
     * @return mixed
     */
    public function createFormData();

    /**
     * @return array
     */
    public function getFormOptions();

    /**
     * @param FormInterface $form
     * @param Request $request
     *
     * @return boolean
     */
    public function process(FormInterface $form, Request $request);
}
