<?php

/**
 * Container Not-Found Exception
 *
 * Thrown by the Container when a requested identifier is not bound and
 * cannot be autowired.
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
use Psr\Container\NotFoundExceptionInterface;

/**
 * Exception thrown when an entry is not found in the container.
 */
class NotFoundException extends Exception implements NotFoundExceptionInterface
{
}
