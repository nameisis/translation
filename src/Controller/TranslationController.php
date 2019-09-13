<?php

namespace Selonia\TranslationBundle\Controller;

use Selonia\TranslationBundle\Form\Type\DomainType;
use Selonia\TranslationBundle\Form\Type\TransUnitType;
use Selonia\TranslationBundle\Storage\StorageInterface;
use Selonia\TranslationBundle\Utils\CsrfCheckerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TranslationController extends AbstractController
{
    use CsrfCheckerTrait;

    /**
     * @Route("/overview", name="nameisis_translation_overview", methods={"GET"})
     */
    public function overview()
    {
        /** @var StorageInterface $storage */
        $storage = $this->get('nameisis_translation.translation_storage');
        $stats = $this->get('nameisis_translation.overview.stats_aggregator')
            ->getStats();

        return $this->render('@NameisisTranslation/Translation/overview.html.twig', [
            'layout' => $this->container->getParameter('nameisis_translation.layout'),
            'locales' => $this->getManagedLocales(),
            'domains' => $storage->getTransUnitDomains(),
            'latestTrans' => $storage->getLatestUpdatedAt(),
            'stats' => $stats,
        ]);
    }

    protected function getManagedLocales()
    {
        return $this->get('nameisis_translation.locale.manager')
            ->getLocales();
    }

    /**
     * @Route("/grid", name="nameisis_translation_grid", methods={"GET"})
     */
    public function grid()
    {
        $tokens = null;
        if ($this->container->getParameter('nameisis_translation.dev_tools.enable')) {
            $tokens = $this->get('nameisis_translation.token_finder')
                ->find();
        }

        return $this->render('@NameisisTranslation/Translation/grid.html.twig', [
            'layout' => $this->container->getParameter('nameisis_translation.layout'),
            'inputType' => $this->container->getParameter('nameisis_translation.grid_input_type'),
            'toggleSimilar' => $this->container->getParameter('nameisis_translation.grid_toggle_similar'),
            'locales' => $this->getManagedLocales(),
            'tokens' => $tokens,
        ]);
    }

    /**
     * @Route("/invalidate-cache", name="nameisis_translation_invalidate_cache", methods={"GET"})
     */
    public function invalidateCache(Request $request)
    {
        $this->checkCsrf();
        $this->get('nameisis_translation.translator')
            ->removeLocalesCacheFiles($this->getManagedLocales());
        $message = $this->get('translator')
            ->trans('translations.cache_removed', [], 'NameisisTranslationBundle');
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['message' => $message]);
        }

        return $this->redirect($this->generateUrl('nameisis_translation_grid'));
    }

    /**
     * @Route("/new", name="nameisis_translation_new", methods={"GET","POST"})
     */
    public function new(Request $request)
    {
        $handler = $this->get('nameisis_translation.form.handler.trans_unit');
        $form = $this->createForm(TransUnitType::class, $handler->createFormData(), $handler->getFormOptions());
        if ($handler->process($form, $request)) {
            $message = $this->get('translator')
                ->trans('translations.successfully_added', [], 'NameisisTranslationBundle');
            $this->get('session')
                ->getFlashBag()
                ->add('success', $message);
            $redirectUrl = $form->get('save_add')
                ->isClicked() ? 'nameisis_translation_new' : 'nameisis_translation_grid';

            return $this->redirect($this->generateUrl($redirectUrl));
        }

        return $this->render('@NameisisTranslation/Translation/new.html.twig', [
            'layout' => $this->container->getParameter('nameisis_translation.layout'),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/domain/new", name="nameisis_translation_domain_new", methods={"GET","POST"})
     */
    public function newDomain(Request $request)
    {
        $form = $this->createForm(DomainType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $message = $this->get('translator')
                ->trans('domain.successfully_added', [], 'NameisisTranslationBundle');
            $redirectUrl = $form->get('save_add')
                ->isClicked() ? 'nameisis_translation_domain_new' : 'nameisis_translation_grid';
        }

        return $this->render('@NameisisTranslation/Translation/new_domain.html.twig', [
            'layout' => $this->container->getParameter('nameisis_translation.layout'),
            'form' => $form->createView(),
        ]);
    }
}
