<?php

/**
 * Container Exception
 *
 * Thrown by the Container when an entry cannot be resolved for reasons
 * other than being absent (e.g. an unresolvable constructor parameter).
 *
 * PHP version 8.1
 *
 * @category  Support
 * @package   Core\Model
 * @author    Suman Banerjee <contact@isumanbanerjee.com>
 * @copyright 2025 Suman Banerjee. All rights reserved.
 * @license   Proprietary
 * @link      https://isumanbanerjee.com
 * @since     1.0.0
 */

declare(strict_types=1);

namespace Core\Model;

use Exception;
use Psr\Container\ContainerExceptionInterface;

/**
 * Exception thrown when the container fails to resolve an entry.
 */
class ContainerException extends Exception implements ContainerExceptionInterface
{
}
