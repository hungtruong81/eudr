<?php

declare(strict_types=1);

namespace App\Application\Actions\Land;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class ListLandByTransactionTicketAction extends LandAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        // Check Authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->request->getQueryParams();

        $validator = new Validator($this->request);

        $validator->validate('transaction_ticket_code', $formData['transaction_ticket_code'] ?? null, 'required|string');
        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');

        if ($validator->hasErrors()) {
            $errorMessages = [];
            $errors = $validator->getErrors();
            if (is_array($errors)) {
                foreach ($errors as $field => $fieldErrors) {
                    if (is_array($fieldErrors)) {
                        $errorMessages[] = implode(', ', $fieldErrors);
                    } else {
                        $errorMessages[] = (string)$fieldErrors;
                    }
                }
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errorMessages));
        }

        // Sanitize and extract data
        $sanitizeRules = [
            'transaction_ticket_code' => 'string',
            'page' => 'integer',
            'limit' => 'integer'
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $transaction_ticket_code = $cleanData['transaction_ticket_code'];
        $page = $cleanData['page'];
        $limit = $cleanData['limit'];

        $transaction_ticket = $this->transactionTicketRepository->findTransactionTicketOfCode($transaction_ticket_code);
        if (empty($transaction_ticket)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy phiếu giao dịch");
        }

        $params = [
            "page" => $page,
            "page_limit" => $limit
        ];

        $lands = $this->landRepository->listLandByTransactionTicket($transaction_ticket->getId(), $params);

        $res_return = ["result" => "success"];
        $res_return['data'] = $lands;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
