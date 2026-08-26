<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Support\Traits;

use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\Model\Document;

trait MocksPimcoreDocument
{
    /**
     * Creates a mock Pimcore Document object.
     */
    protected function createMockDocument(int $id = 1, string $path = '/test/document'): MockObject
    {
        $doc = $this->createMock(Document::class);
        $doc->method('getId')->willReturn($id);
        $doc->method('getFullPath')->willReturn($path);

        return $doc;
    }
}
