<?php

declare(strict_types=1);

namespace App\Form\InputHandler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Valantic\PimcoreFormsBundle\InputHandler\InputHandlerInterface;

/**
 * Example custom input handler that pre-populates form fields from URL query parameters.
 *
 * This is useful for scenarios where you want to pre-fill forms based on
 * tracking parameters, referral codes, or other data passed via URL.
 *
 * Usage in configuration:
 * ```yaml
 * valantic_pimcore_forms:
 *   forms:
 *     contact:
 *       inputHandlers:
 *         - type: query_string
 *           mapping:
 *             utm_source: source
 *             utm_campaign: campaign
 *             ref: referralCode
 * ```
 *
 * Register the service:
 * ```yaml
 * services:
 *   app.input_handler.query_string:
 *     class: App\Form\InputHandler\QueryStringInputHandler
 *     tags:
 *       - { name: 'valantic.pimcore_forms.input_handler', key: 'query_string' }
 * ```
 *
 * Example URL:
 * /contact?utm_source=newsletter&utm_campaign=spring2024&ref=FRIEND123
 *
 * Will populate form fields:
 * - source: "newsletter"
 * - campaign: "spring2024"
 * - referralCode: "FRIEND123"
 */
class QueryStringInputHandler implements InputHandlerInterface
{
    /**
     * @param array{mapping?: array<string, string>} $config
     */
    public function handle(Request $request, FormInterface $form, array $config): void
    {
        $mapping = $config['mapping'] ?? [];

        if (empty($mapping)) {
            return;
        }

        $data = [];

        // Map query parameters to form field names
        foreach ($mapping as $queryParam => $formField) {
            $value = $request->query->get($queryParam);

            if ($value !== null && $form->has($formField)) {
                $data[$formField] = $value;
            }
        }

        // Only set data if we have any
        if (!empty($data)) {
            // Merge with existing data to avoid overwriting
            $existingData = $form->getData() ?? [];
            $mergedData = array_merge($existingData, $data);

            $form->setData($mergedData);
        }
    }
}
