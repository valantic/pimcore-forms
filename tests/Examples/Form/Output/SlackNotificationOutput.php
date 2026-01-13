<?php

declare(strict_types=1);

namespace App\Form\Output;

use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Valantic\PimcoreFormsBundle\Output\OutputInterface;
use Valantic\PimcoreFormsBundle\Output\OutputStatus;

/**
 * Example custom output handler that sends form submissions to Slack.
 *
 * This demonstrates how to create a custom output handler that integrates
 * with external services. In this case, we're sending a formatted message
 * to a Slack webhook URL.
 *
 * Usage in configuration:
 * ```yaml
 * valantic_pimcore_forms:
 *   forms:
 *     contact:
 *       outputs:
 *         - type: slack
 *           webhookUrl: 'https://hooks.slack.com/services/YOUR/WEBHOOK/URL'
 *           channel: '#notifications'
 *           username: 'Form Bot'
 * ```
 *
 * Register the service:
 * ```yaml
 * services:
 *   app.output.slack:
 *     class: App\Form\Output\SlackNotificationOutput
 *     arguments:
 *       - '@http_client'
 *     tags:
 *       - { name: 'valantic.pimcore_forms.output', key: 'slack' }
 * ```
 */
class SlackNotificationOutput implements OutputInterface
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param array{webhookUrl: string, channel?: string, username?: string, icon?: string} $config
     */
    public function execute(FormInterface $form, array $config): OutputStatus
    {
        try {
            $webhookUrl = $config['webhookUrl'] ?? '';

            if (empty($webhookUrl)) {
                return OutputStatus::error('Slack webhook URL is not configured');
            }

            // Build the Slack message payload
            $payload = $this->buildSlackPayload($form, $config);

            // Send to Slack
            $response = $this->httpClient->request('POST', $webhookUrl, [
                'json' => $payload,
                'timeout' => 5,
            ]);

            if ($response->getStatusCode() === 200) {
                return OutputStatus::success('Slack notification sent successfully');
            }

            return OutputStatus::error(
                sprintf('Slack API returned status %d', $response->getStatusCode()),
            );
        } catch (\Exception $e) {
            return OutputStatus::error(
                sprintf('Failed to send Slack notification: %s', $e->getMessage()),
            );
        }
    }

    /**
     * @param array{channel?: string, username?: string, icon?: string} $config
     *
     * @return array<string, mixed>
     */
    private function buildSlackPayload(FormInterface $form, array $config): array
    {
        $formData = $form->getData();
        $formName = $form->getConfig()->getName();

        // Build formatted fields for Slack
        $fields = [];

        foreach ($form->all() as $field) {
            if ($field->getConfig()->getType()->getInnerType()::class === \Symfony\Component\Form\Extension\Core\Type\SubmitType::class) {
                continue;
            }

            $fieldName = $field->getName();
            $value = $formData[$fieldName] ?? '';

            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            $fields[] = [
                'title' => $this->humanize($fieldName),
                'value' => (string) $value,
                'short' => mb_strlen((string) $value) < 40,
            ];
        }

        // Build the Slack message
        $payload = [
            'text' => sprintf('New form submission: *%s*', $formName),
            'attachments' => [
                [
                    'color' => 'good',
                    'fields' => $fields,
                    'footer' => 'Pimcore Forms',
                    'ts' => time(),
                ],
            ],
        ];

        // Add optional configuration
        if (!empty($config['channel'])) {
            $payload['channel'] = $config['channel'];
        }

        if (!empty($config['username'])) {
            $payload['username'] = $config['username'];
        }

        if (!empty($config['icon'])) {
            $payload['icon_emoji'] = $config['icon'];
        }

        return $payload;
    }

    /**
     * Convert a field name to human-readable format.
     * Example: "firstName" => "First Name".
     */
    private function humanize(string $text): string
    {
        // Split camelCase and snake_case
        $text = preg_replace('/([a-z])([A-Z])/', '$1 $2', $text);
        $text = str_replace('_', ' ', $text);

        // Capitalize words
        return ucwords(strtolower($text));
    }
}
