<?php

namespace Valantic\PimcoreFormsBundle\Form\Output;

use InvalidArgumentException;
use Pimcore\Model\DataObject\Concrete;

class DataObjectOutput extends AbstractOutput
{
    public static function name(): string
    {
        return 'data_object';
    }

    public function handle(): bool
    {
        $objClass = 'Pimcore\\Model\\DataObject\\' . $this->config['class'];
        if (!class_exists($objClass)) {
            throw new InvalidArgumentException(sprintf('DataObject %s does not exist', $objClass));
        }

        $path = Concrete::getByPath($this->getPath());
        if ($path === null) {
            throw new InvalidArgumentException(sprintf('Path %s not found', $this->getPath()));
        }

        /** @var Concrete $obj */
        $obj = new $objClass();

        foreach ($this->getData() as $key => $value) {
            $obj->set($key, $value);
        }

        $obj->setPath($this->getPath());
        $obj->setKey($this->getKey());
        $obj->setParentId($path->getId());
        $obj->save();

        return true;
    }

    /**
     * @return array<string,mixed>
     */
    protected function getData(): array
    {
        return $this->form->getData();
    }

    protected function getPath(): string
    {
        return $this->config['path'];
    }

    protected function getKey(): string
    {
        return implode('-', [$this->form->getName(), time()]);
    }
}
