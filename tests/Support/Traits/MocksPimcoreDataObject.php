<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Support\Traits;

use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;

trait MocksPimcoreDataObject
{
    /**
     * Creates a mock Pimcore DataObject.
     */
    protected function createMockDataObject(string $class = 'FormSubmission', int $id = 1): MockObject
    {
        $obj = $this->createMock(Concrete::class);
        $obj->method('getId')->willReturn($id);
        $obj->method('save')->willReturn(true);
        $obj->method('getClassName')->willReturn($class);

        return $obj;
    }

    /**
     * Creates a mock Pimcore DataObject Folder.
     */
    protected function createMockDataObjectFolder(string $path = '/Forms'): MockObject
    {
        $folder = $this->createMock(Folder::class);
        $folder->method('getFullPath')->willReturn($path);
        $folder->method('getId')->willReturn(1);

        return $folder;
    }
}
