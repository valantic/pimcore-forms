<?php

namespace Valantic\PimcoreFormsBundle\Controller;

use Limenius\Liform\Liform;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Valantic\PimcoreFormsBundle\Service\FormService;

class FormController extends AbstractController
{
    /**
     * @Route("/debug")
     * @param FormService $builder
     *
     * @return Response
     */
    public function debugAction(FormService $builder): Response
    {
        $form = $builder->build('form1')->getForm();

        return $this->render('@ValanticPimcoreForms/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/form")
     * @param FormService $builder
     * @param Liform $liform
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function formAction(FormService $builder, Liform $liform, Request $request): JsonResponse
    {
        $name = $request->request->get('form')[FormService::INPUT_FORM_NAME] ?? null;

        if (empty($name)) {
            // TODO: remove form1
            $form = $builder->build('form1')->getForm();

            return new JsonResponse($liform->transform($form));
        }

        $form = $builder->build($name)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            return new JsonResponse($data);
        }

        return new JsonResponse($builder->errors($form));
    }
}
