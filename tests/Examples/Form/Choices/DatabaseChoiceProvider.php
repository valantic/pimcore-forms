<?php

declare(strict_types=1);

namespace App\Form\Choices;

use Doctrine\DBAL\Connection;
use Valantic\PimcoreFormsBundle\Choices\ChoicesInterface;

/**
 * Example custom choice provider that loads options from a database.
 *
 * This demonstrates how to create a dynamic choice provider that pulls
 * data from a database table. Useful for dropdowns that need to be
 * populated from external data sources.
 *
 * Usage in configuration:
 * ```yaml
 * valantic_pimcore_forms:
 *   forms:
 *     contact:
 *       fields:
 *         - name: department
 *           type: choice
 *           options:
 *             choices: '@database_departments'
 * ```
 *
 * Register the service:
 * ```yaml
 * services:
 *   database_departments:
 *     class: App\Form\Choices\DatabaseChoiceProvider
 *     arguments:
 *       - '@doctrine.dbal.default_connection'
 *       - 'departments'  # table name
 *       - 'id'           # value column
 *       - 'name'         # label column
 *     tags:
 *       - { name: 'valantic.pimcore_forms.choices', key: 'database_departments' }
 * ```
 */
class DatabaseChoiceProvider implements ChoicesInterface
{
    private Connection $connection;
    private string $table;
    private string $valueColumn;
    private string $labelColumn;
    private ?string $orderBy;

    public function __construct(
        Connection $connection,
        string $table,
        string $valueColumn,
        string $labelColumn,
        ?string $orderBy = null,
    ) {
        $this->connection = $connection;
        $this->table = $table;
        $this->valueColumn = $valueColumn;
        $this->labelColumn = $labelColumn;
        $this->orderBy = $orderBy ?? $labelColumn;
    }

    /**
     * @return array<string, mixed>
     */
    public function getChoices(): array
    {
        try {
            $qb = $this->connection->createQueryBuilder();

            $qb->select($this->valueColumn, $this->labelColumn)
                ->from($this->table)
                ->orderBy($this->orderBy, 'ASC')
            ;

            $results = $qb->executeQuery()->fetchAllAssociative();

            // Build choices array in Symfony format: ['label' => 'value']
            $choices = [];

            foreach ($results as $row) {
                $label = $row[$this->labelColumn] ?? '';
                $value = $row[$this->valueColumn] ?? '';
                $choices[$label] = $value;
            }

            return $choices;
        } catch (\Exception $e) {
            // Log error and return empty choices
            // In production, you might want to use a logger here
            return [];
        }
    }
}
