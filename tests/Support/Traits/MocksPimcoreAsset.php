<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Support\Traits;

use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\Model\Asset;

trait MocksPimcoreAsset
{
    /**
     * Creates a mock Pimcore Asset object.
     */
    protected function createMockAsset(int $id = 1, string $filename = 'test.txt'): MockObject
    {
        $asset = $this->createMock(Asset::class);
        $asset->method('getId')->willReturn($id);
        $asset->method('getFilename')->willReturn($filename);
        $asset->method('save')->willReturn(true);

        return $asset;
    }

    /**
     * Creates a mock Pimcore Asset Folder.
     */
    protected function createMockAssetFolder(string $path = '/uploads'): MockObject
    {
        $folder = $this->createMock(Asset\Folder::class);
        $folder->method('getFullPath')->willReturn($path);
        $folder->method('getId')->willReturn(100);
        $folder->method('save')->willReturnSelf();

        return $folder;
    }
}
