<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Valantic\PimcoreFormsBundle\Service\FormService;

class FormController extends AbstractController
{
    /**
     * @Route("/ui/{name}")
     *
     * @param string $name
     * @param FormService $formService
     * @param Request $request
     *
     * @return Response
     */
    public function uiAction(string $name, FormService $formService, Request $request): Response
    {
        return $this->render('@ValanticPimcoreForms/form.html.twig', [
            'form' => $formService->buildForm($name)->createView(),
        ]);
    }

    /**
     * @Route("/api/{name}")
     *
     * @param string $name
     * @param FormService $formService
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function apiAction(string $name, FormService $formService, Request $request): JsonResponse
    {
        $form = $formService->buildForm($name);
        $form->handleRequest($request);

        $data = json_decode((string)$request->getContent(false), true);

        if (!empty($data)) {
            $form->submit($data);
        }

        if (!$form->isSubmitted()) {
            return new JsonResponse($formService->buildJson($name));
        }

        if ($form->isValid()) {
            $data = $form->getData();

            return new JsonResponse(
                $data,
                $formService->outputs($form)
                    ? JsonResponse::HTTP_OK
                    : JsonResponse::HTTP_PRECONDITION_FAILED
            );
        }

        return new JsonResponse($formService->errors($form));
    }
}
