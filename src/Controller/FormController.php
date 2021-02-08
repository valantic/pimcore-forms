<?php

namespace Valantic\PimcoreFormsBundle\Controller;

use Limenius\Liform\Liform;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Valantic\PimcoreFormsBundle\Form\Builder;

class FormController extends AbstractController
{
    /**
     * @Route("/debug")
     * @param Builder $builder
     *
     * @return Response
     */
    public function debugAction(Builder $builder): Response
    {

        $form = $builder->get('form1')->getForm();

        return $this->render('@ValanticPimcoreForms/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/form")
     * @param Builder $builder
     * @param Liform $liform
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function formAction(Builder $builder, Liform $liform, Request $request): JsonResponse
    {
        $name = $request->request->get('form')['_form'] ?? null;
        if (empty($name)) {
            $form = $builder->get('form1')->getForm();

            return new JsonResponse($liform->transform($form));
        }

        $form = $builder->get($name)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            return new JsonResponse($data);
        }

        return new JsonResponse($builder->getErrors($form));
    }
}
