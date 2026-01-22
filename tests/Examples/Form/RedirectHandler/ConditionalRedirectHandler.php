<?php

declare(strict_types=1);

namespace App\Form\RedirectHandler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Valantic\PimcoreFormsBundle\RedirectHandler\RedirectHandlerInterface;

/**
 * Example custom redirect handler that redirects to different URLs based on conditions.
 *
 * This demonstrates how to create conditional redirects based on form data,
 * output status, or other criteria. Useful for multi-path workflows.
 *
 * Usage in configuration:
 * ```yaml
 * valantic_pimcore_forms:
 *   forms:
 *     contact:
 *       redirectHandlers:
 *         - type: conditional
 *           conditions:
 *             - field: inquiry_type
 *               value: sales
 *               url: /thank-you/sales
 *             - field: inquiry_type
 *               value: support
 *               url: /thank-you/support
 *           defaultUrl: /thank-you
 * ```
 *
 * Register the service:
 * ```yaml
 * services:
 *   app.redirect_handler.conditional:
 *     class: App\Form\RedirectHandler\ConditionalRedirectHandler
 *     tags:
 *       - { name: 'valantic.pimcore_forms.redirect_handler', key: 'conditional' }
 * ```
 */
class ConditionalRedirectHandler implements RedirectHandlerInterface
{
    /**
     * @param array{conditions?: array<array{field: string, value: mixed, url: string}>, defaultUrl?: string} $config
     */
    public function getRedirectUrl(Request $request, FormInterface $form, bool $success, array $config): ?string
    {
        // If form submission failed, use default error URL or return null
        if (!$success) {
            return $config['errorUrl'] ?? null;
        }

        $conditions = $config['conditions'] ?? [];
        $defaultUrl = $config['defaultUrl'] ?? null;

        $formData = $form->getData();

        // Check each condition
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $expectedValue = $condition['value'] ?? null;
            $url = $condition['url'] ?? null;

            if ($field === null || $url === null) {
                continue;
            }

            $actualValue = $formData[$field] ?? null;

            // Check if condition matches
            if ($this->matchesCondition($actualValue, $expectedValue)) {
                return $url;
            }
        }

        // No conditions matched, return default URL
        return $defaultUrl;
    }

    /**
     * Check if actual value matches expected value.
     */
    private function matchesCondition($actual, $expected): bool
    {
        // Handle array values (e.g., multi-choice fields)
        if (is_array($actual)) {
            return in_array($expected, $actual, true);
        }

        // Handle string comparison (case-insensitive)
        if (is_string($actual) && is_string($expected)) {
            return strcasecmp($actual, $expected) === 0;
        }

        // Handle exact match for other types
        return $actual === $expected;
    }
}
