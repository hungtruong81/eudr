<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace App\Application\Exception;

use Slim\Exception\HttpSpecializedException;

class HttpNotificationException extends HttpSpecializedException
{
    /**
     * @var int
     */
    protected $code = 203;

    /**
     * @var string
     */
    protected $message = 'Request not complete';

    protected $title = '203 Not Complete';
    protected $description = 'Please try re-login again.';
}
