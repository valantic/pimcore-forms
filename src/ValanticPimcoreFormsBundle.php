<?php

namespace Valantic\PimcoreFormsBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use RuntimeException;

class ValanticPimcoreFormsBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public function getJsPaths()
    {
        return [
            '/bundles/valanticpimcoreforms/js/pimcore/startup.js',
        ];
    }

    protected function getComposerPackageName(): string
    {
        $composer = file_get_contents(__DIR__ . '/../composer.json');
        if ($composer === false) {
            throw new RuntimeException();
        }

        return json_decode($composer)->name;
    }
}
