<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Document\Areabrick;

use Pimcore\Extension\Document\Areabrick\AbstractTemplateAreabrick;
use Pimcore\Extension\Document\Areabrick\EditableDialogBoxConfiguration;
use Pimcore\Extension\Document\Areabrick\EditableDialogBoxInterface;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Editable\Area\Info;
use Valantic\PimcoreFormsBundle\Repository\ConfigurationRepository;

class Form extends AbstractTemplateAreabrick implements EditableDialogBoxInterface
{
    protected ConfigurationRepository $configurationRepository;

    public function __construct(ConfigurationRepository $configurationRepository)
    {
        $this->configurationRepository = $configurationRepository;
    }

    public function getTemplateLocation(): string
    {
        return static::TEMPLATE_LOCATION_BUNDLE;
    }

    public function getTemplateSuffix(): string
    {
        return static::TEMPLATE_SUFFIX_TWIG;
    }

    public function getHtmlTagOpen(Info $info): string
    {
        return '';
    }

    public function getHtmlTagClose(Info $info): string
    {
        return '';
    }

    public function getName(): string
    {
        return 'Form';
    }

    public function getDescription(): string
    {
        return 'Choose a form provided by valantic/pimcore-forms';
    }

    public function getIcon()
    {
        return '/bundles/pimcoreadmin/img/flat-color-icons/view_details.svg';
    }

    public function getEditableDialogBoxConfiguration(Document\Editable $area, ?Info $info): EditableDialogBoxConfiguration
    {
        $config = new EditableDialogBoxConfiguration();
        $config->setWidth(300);

        $config->setItems([
            [
                'type' => 'select',
                'label' => 'Form',
                'name' => 'form',
                'config' => [
                    'store' => array_map(
                        fn(string $name): array => [$name, $name],
                        array_keys($this->configurationRepository->get()['forms'])
                    ),
                ],
            ],
        ]);

        return $config;
    }
}
