<?php

declare(strict_types=1);

namespace App\Application\Handlers;

use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpNotImplementedException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Handlers\ErrorHandler as SlimErrorHandler;
use App\Application\Exception\HttpNotificationException;

use Throwable;

class HttpErrorHandler extends SlimErrorHandler
{
    /**
     * @inheritdoc
     */
    protected function respond(): Response
    {
        $exception = $this->exception;
        $statusCode = 500;
        $error = new ActionError(
            ActionError::SERVER_ERROR,
            'An internal error has occurred while processing your request.'
        );

        if ($exception instanceof HttpException) {
            $statusCode = $exception->getCode();
            $error->setDescription($exception->getMessage());

            if ($exception instanceof HttpNotFoundException) {
                $error->setType(ActionError::RESOURCE_NOT_FOUND);
            } elseif ($exception instanceof HttpMethodNotAllowedException) {
                $error->setType(ActionError::NOT_ALLOWED);
            } elseif ($exception instanceof HttpUnauthorizedException) {
                $error->setType(ActionError::UNAUTHENTICATED);
            } elseif ($exception instanceof HttpForbiddenException) {
                $error->setType(ActionError::INSUFFICIENT_PRIVILEGES);
            } elseif ($exception instanceof HttpBadRequestException) {
                $error->setType(ActionError::BAD_REQUEST);
            } elseif ($exception instanceof HttpNotImplementedException) {
                $error->setType(ActionError::NOT_IMPLEMENTED);
            } elseif ($exception instanceof HttpNotificationException) {
                $error->setType(ActionError::API_ERROR);
            }
        }

        if (
            !($exception instanceof HttpException)
            && $exception instanceof Throwable

        ) {
            if ($this->displayErrorDetails) {
                $error->setDescription($exception->getMessage());
                $error->setLine($exception->getLine());
                $error->setFile($exception->getFile());
            }
            $this->logger->error("Error: " . $exception->getMessage());
        }

        if ($statusCode != 203) {
            $err = $error->jsonSerialize();

            $log = array(
                "milliseconds" => floor(microtime(true) * 1000),
                "path" => '',
                "region" => '',
                "game_id" => '',
                "user_id" => '',
                "ga_id" => '',
                "utm_source" => '',
                "utm_medium" => '',
                "utm_campaign" => '',
                "payment_category" => '',
                "channel" => '',
                "amount" => '',
                "message" => '',
                "log_action" => 'SERVER_ERROR',
                "log_group" =>  "Error",
                "extra1" => $err['type'],
                "extra2" =>  $err['file'] ?? '',
                "extra3" =>  $err['line'] ?? '',
                "extra4" => str_replace(array("\r\n", "\r"), "", $err['description']),
            );

            $log_save = implode("|", array_values($log));
            $this->logger->notice($log_save);
        }

        $payload = new ActionPayload($statusCode, null, $error);
        $encodedPayload = json_encode($payload, JSON_PRETTY_PRINT);

        $response = $this->responseFactory->createResponse($statusCode);
        $response->getBody()->write($encodedPayload);

        return $response->withHeader('Content-Type', 'application/json');
    }
}
