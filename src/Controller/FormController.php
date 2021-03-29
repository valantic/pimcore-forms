<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Valantic\PimcoreFormsBundle\Http\ApiResponse;
use Valantic\PimcoreFormsBundle\Service\FormService;

class FormController extends AbstractController
{
    /**
     * @Route("/ui/{name}")
     *
     * @param string $name
     * @param FormService $formService
     *
     * @return Response
     */
    public function uiAction(string $name, FormService $formService): Response
    {
        return $this->render('@ValanticPimcoreForms/vue.html.twig', [
            'form' => $formService->buildForm($name)->createView(),
        ]);
    }

    /**
     * @Route("/html/{name}")
     *
     * @param string $name
     * @param FormService $formService
     *
     * @return Response
     */
    public function htmlAction(string $name, FormService $formService): Response
    {
        return $this->render('@ValanticPimcoreForms/html.html.twig', [
            'form' => $formService->buildForm($name)->createView(),
        ]);
    }

    /**
     * @Route("/api/{name}")
     *
     * @param string $name
     * @param FormService $formService
     * @param Request $request
     * @param TranslatorInterface $translator
     *
     * @throws SerializerException
     *
     * @return ApiResponse
     */
    public function apiAction(string $name, FormService $formService, Request $request, TranslatorInterface $translator): ApiResponse
    {
        $form = $formService->buildForm($name);
        $form->handleRequest($request);

        if (!$form->isSubmitted() && $request->getContentType() === 'json') {
            $content = (string) $request->getContent(false);
            $data = json_decode($content, true);

            if (!empty($content) && !empty($data)) {
                $form->submit($data);
            }
        }

        if (!$form->isSubmitted()) {
            return new ApiResponse($formService->buildJson($name));
        }

        if ($form->isValid()) {
            $data = $form->getData();

            $outputSuccess = $formService->outputs($form);

            $redirectUrl = $formService->getRedirectUrl($form, $outputSuccess);

            return new ApiResponse(
                $data,
                $outputSuccess
                    ? [
                        ApiResponse::MESSAGE_TYPE_SUCCESS => $translator->trans('valantic.pimcoreForms.formSubmitSuccess'),
                    ]
                    : [
                        ApiResponse::MESSAGE_TYPE_ERROR => $translator->trans('valantic.pimcoreForms.formSubmitError'),
                    ],
                $outputSuccess
                    ? JsonResponse::HTTP_OK
                    : JsonResponse::HTTP_PRECONDITION_FAILED,
                $redirectUrl
            );
        }

        return new ApiResponse([], $formService->errors($form), JsonResponse::HTTP_PRECONDITION_FAILED);
    }
}
