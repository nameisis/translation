<?php

namespace Selonia\TranslationBundle\Utils;

trait CsrfCheckerTrait
{
    protected function checkCsrf($id = 'selonia-translation', $query = '_token')
    {
        if (!$this->has('security.csrf.token_manager')) {
            return;
        }
        $request = $this->get('request_stack')
            ->getCurrentRequest();
        if (!$this->isCsrfTokenValid($id, $request->get($query))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }
    }
}
