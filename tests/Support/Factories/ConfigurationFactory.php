<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Support\Factories;

class ConfigurationFactory
{
    /**
     * Creates a valid form configuration for testing.
     */
    public static function createValidFormConfig(string $name = 'contact_form'): array
    {
        return [
            'forms' => [
                $name => [
                    'csrf' => true,
                    'method' => 'POST',
                    'translate' => [
                        'field_labels' => true,
                        'inline_choices' => false,
                    ],
                    'fields' => [
                        'name' => [
                            'type' => 'TextType',
                            'options' => [
                                'label' => 'Name',
                                'required' => true,
                            ],
                            'constraints' => ['NotBlank'],
                            'provider' => null,
                        ],
                        'email' => [
                            'type' => 'EmailType',
                            'options' => [
                                'label' => 'Email Address',
                                'required' => true,
                            ],
                            'constraints' => ['NotBlank', 'Email'],
                            'provider' => null,
                        ],
                    ],
                    'outputs' => [
                        'email_admin' => [
                            'type' => 'email',
                            'options' => [
                                'to' => 'admin@example.com',
                                'document' => 5,
                            ],
                        ],
                    ],
                    'redirect_handler' => null,
                    'input_handler' => null,
                    'api_error_message_template' => null,
                ],
            ],
        ];
    }

    /**
     * Creates a form configuration with multiple outputs.
     */
    public static function createMultipleOutputsConfig(string $name = 'multi_output_form'): array
    {
        return [
            'forms' => [
                $name => [
                    'csrf' => true,
                    'method' => 'POST',
                    'translate' => [
                        'field_labels' => false,
                        'inline_choices' => false,
                    ],
                    'fields' => [
                        'message' => [
                            'type' => 'TextareaType',
                            'options' => ['label' => 'Message'],
                            'constraints' => ['NotBlank'],
                            'provider' => null,
                        ],
                    ],
                    'outputs' => [
                        'email' => [
                            'type' => 'email',
                            'options' => [
                                'to' => 'admin@example.com',
                                'document' => 5,
                            ],
                        ],
                        'log' => [
                            'type' => 'log',
                            'options' => [],
                        ],
                        'http' => [
                            'type' => 'http',
                            'options' => [
                                'url' => 'https://example.com/webhook',
                            ],
                        ],
                    ],
                    'redirect_handler' => null,
                    'input_handler' => null,
                    'api_error_message_template' => null,
                ],
            ],
        ];
    }

    /**
     * Creates a contact form configuration for testing.
     */
    public static function createContactFormConfig(): array
    {
        return [
            'forms' => [
                'contact' => [
                    'csrf' => false,
                    'method' => 'POST',
                    'translate' => [
                        'field_labels' => true,
                        'inline_choices' => false,
                    ],
                    'fields' => [
                        'name' => [
                            'type' => 'TextType',
                            'options' => [
                                'label' => 'Name',
                                'required' => true,
                            ],
                            'constraints' => ['NotBlank'],
                            'provider' => null,
                        ],
                        'email' => [
                            'type' => 'EmailType',
                            'options' => [
                                'label' => 'Email Address',
                                'required' => true,
                            ],
                            'constraints' => ['NotBlank', 'Email'],
                            'provider' => null,
                        ],
                        'message' => [
                            'type' => 'TextareaType',
                            'options' => [
                                'label' => 'Message',
                                'required' => false,
                            ],
                            'constraints' => [],
                            'provider' => null,
                        ],
                    ],
                    'outputs' => [
                        'email_admin' => [
                            'type' => 'email',
                            'options' => [
                                'to' => 'admin@example.com',
                                'document' => 5,
                            ],
                        ],
                    ],
                    'redirect_handler' => null,
                    'input_handler' => null,
                    'api_error_message_template' => null,
                ],
            ],
        ];
    }
}
