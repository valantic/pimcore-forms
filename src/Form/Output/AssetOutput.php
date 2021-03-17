<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Output;

use InvalidArgumentException;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Folder;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AssetOutput extends AbstractOutput
{
    public static function name(): string
    {
        return 'asset';
    }

    public function handle(): bool
    {
        $path = Folder::getByPath($this->getPath());

        if ($path === null) {
            throw new InvalidArgumentException(sprintf('Path %s not found', $this->getPath()));
        }

        $subfolderName = $this->getSubfolderName();

        $subfolder = new Folder();
        $subfolder->setKey($subfolderName);
        $subfolder->setParent($path);
        $subfolder->save();

        foreach ($this->getFiles() as $field => $file) {
            if ($file->getRealPath() === false) {
                continue;
            }

            $asset = new Asset();
            $asset->setKey($this->getFilename($file));
            $asset->setParent($subfolder);
            $asset->setData(file_get_contents($file->getRealPath()));
            $asset->save();
        }

        return true;
    }

    /**
     * @return array<string,UploadedFile>
     */
    protected function getFiles(): array
    {
        $files = [];

        foreach ($this->config['fields'] as $field) {
            /** @var string $field */
            $file = $this->form->get($field)->getData();

            if (!$file instanceof UploadedFile) {
                continue;
            }

            $files[$field] = $file;
        }

        return $files;
    }

    protected function getPath(): string
    {
        return $this->config['path'];
    }

    protected function getSubfolderName(): string
    {
        $base = sprintf('%s_%s', $this->form->getName(), date('Ymd-His'));

        if ($this->config['createHashedFolder'] ?? true) {
            return $base . sprintf('_%s', Uuid::uuid4()->toString());
        }

        return $base;
    }

    protected function getFilename(UploadedFile $file): string
    {
        return pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME) . '.' . $file->guessExtension();
    }
}
