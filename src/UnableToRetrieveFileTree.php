<?php

declare(strict_types=1);

namespace InfernalMedia\FlysystemGitea;

use League\Flysystem\FilesystemOperationFailed;
use RuntimeException;

/**
 * Class UnableToRetrieveFileTree
 *
 * @package InfernalMedia\FlysystemGitea
 */
final class UnableToRetrieveFileTree extends RuntimeException implements FilesystemOperationFailed
{
    /**
     * @return string
     */
    public function operation(): string
    {
        return FilesystemOperationFailed::OPERATION_READ;
    }
}
