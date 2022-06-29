<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Constant;

interface MessageConstants
{
    public const MESSAGE_TYPE_SUCCESS = 'success';
    public const MESSAGE_TYPE_ERROR = 'error';
    public const MESSAGE_TYPE_WARNING = 'warning';
    public const MESSAGE_TYPE_INFO = 'info';

    public const MESSAGE_TYPES = [
        self::MESSAGE_TYPE_SUCCESS,
        self::MESSAGE_TYPE_ERROR,
        self::MESSAGE_TYPE_WARNING,
        self::MESSAGE_TYPE_INFO,
    ];
}
