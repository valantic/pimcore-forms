<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Valantic\PimcoreFormsBundle\Constant\MessageConstants;
use Valantic\PimcoreFormsBundle\Http\ApiResponse;
use Valantic\PimcoreFormsBundle\Model\Message;
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
            $content = (string) $request->getContent();
            $data = json_decode($content, true, flags: \JSON_THROW_ON_ERROR);

            if (!empty($content) && !empty($data)) {
                $form->submit($data);
            }
        }

        if (!$form->isSubmitted()) {
            return new ApiResponse($formService->buildJson($name));
        }

        if ($form->isValid()) {
            $data = $form->getData();

            $outputResponse = $formService->outputs($form);

            $redirectUrl = $formService->getRedirectUrl($form, $outputResponse->getOverallStatus());

            $messages = $outputResponse->getOverallStatus()
                ? [
                    (new Message())
                        ->setType(MessageConstants::MESSAGE_TYPE_SUCCESS)
                        ->setMessage($translator->trans('valantic.pimcoreForms.formSubmitSuccess')),
                ]
                : [
                    (new Message())
                        ->setType(MessageConstants::MESSAGE_TYPE_ERROR)
                        ->setMessage($translator->trans('valantic.pimcoreForms.formSubmitError')),
                ];


            if (!empty($outputResponse->getMessages())) {
                $messages = $outputResponse->getMessages();
            }

            $statusCode = $outputResponse->getOverallStatus()
                ? Response::HTTP_OK
                : Response::HTTP_PRECONDITION_FAILED;

            if ($statusCode === Response::HTTP_OK && $redirectUrl !== null) {
                return new ApiResponse($data, [], $statusCode, $redirectUrl);
            }

            return new ApiResponse($data, $messages, $statusCode, $redirectUrl);
        }

        return new ApiResponse([], $formService->errors($form), Response::HTTP_PRECONDITION_FAILED);
    }

    /**
     * Since support for placeholders was removed in Pimcore X,
     * this workaround passes the parameter set on the Mail instance
     * to the Twig document.
     *
     * Otherwise, Twig would need to be written in the document (in Pimcore)
     * itself instead of in a Twig file.
     *
     * Sample usage: create a Twig document with the contents:
     * `{{ form_contents | raw }}`
     *
     * @Template
     *
     * @return array<string,mixed>
     */
    public function mailDocumentAction(Request $request): array
    {
        return array_filter(
            $request->attributes->all(),
            fn($key): bool => is_string($key) && !str_starts_with($key, '_'),
            \ARRAY_FILTER_USE_KEY
        );
    }
}
