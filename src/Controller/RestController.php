<?php

namespace Nameisis\TranslationBundle\Controller;

use Nameisis\TranslationBundle\Utils\CsrfCheckerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class RestController extends AbstractController
{
    use CsrfCheckerTrait;

    /**
     * @Route("/api/list", name="nameisis_translation_list", methods={"GET"})
     */
    public function list(Request $request)
    {
        list($transUnits, $count) = $this->get('nameisis_translation.data_grid.request_handler')
            ->getPage($request);

        return $this->get('nameisis_translation.data_grid.formatter')
            ->createListResponse($transUnits, $count);
    }

    /**
     * @Route("/api/list/{token}", name="nameisis_translation_profiler", methods={"GET"})
     */
    public function listByProfile(Request $request, $token)
    {
        list($transUnits, $count) = $this->get('nameisis_translation.data_grid.request_handler')
            ->getPageByToken($request, $token);

        return $this->get('nameisis_translation.data_grid.formatter')
            ->createListResponse($transUnits, $count);
    }

    /**
     * @Route("/api/update/{id}", name="nameisis_translation_update", methods={"PUT"})
     */
    public function update(Request $request, $id)
    {
        $this->checkCsrf();
        $transUnit = $this->get('nameisis_translation.data_grid.request_handler')
            ->updateFromRequest($id, $request);

        return $this->get('nameisis_translation.data_grid.formatter')
            ->createSingleResponse($transUnit);
    }

    /**
     * @Route("/api/delete/{id}", name="nameisis_translation_delete", methods={"DELETE"})
     */
    public function delete($id)
    {
        $this->checkCsrf();
        $transUnit = $this->get('nameisis_translation.translation_storage')
            ->getTransUnitById($id);
        if (!$transUnit) {
            throw $this->createNotFoundException(sprintf('No TransUnit found for id "%s".', $id));
        }
        $deleted = $this->get('nameisis_translation.trans_unit.manager')
            ->delete($transUnit);

        return new JsonResponse(['deleted' => $deleted], $deleted ? 200 : 400);
    }

    /**
     * @Route("/api/delete/{id}/{locale}", name="nameisis_translation_delete_locale", methods={"DELETE"})
     */
    public function deleteTranslation($id, $locale)
    {
        $this->checkCsrf();
        $transUnit = $this->get('nameisis_translation.translation_storage')
            ->getTransUnitById($id);
        if (!$transUnit) {
            throw $this->createNotFoundException(sprintf('No TransUnit found for id "%s".', $id));
        }
        $deleted = $this->get('nameisis_translation.trans_unit.manager')
            ->deleteTranslation($transUnit, $locale);

        return new JsonResponse(['deleted' => $deleted], $deleted ? 200 : 400);
    }
}
