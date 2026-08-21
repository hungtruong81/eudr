<?php

declare(strict_types=1);

namespace App\Application\Actions\TransactionTicket;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class ListPurchaseTicketsBySaleTicketAction extends TransactionTicketAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // trace_id tracking request
        $trace_id = Utils::generateRandomString(25);

        // Check authentication
        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, "Thiếu quyền truy cập");
        }

        $formData = $this->request->getQueryParams();

        $transaction_ticket_code = addslashes(trim((string)$this->resolveArg('code')));

        $validator = new Validator($this->request);

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

        // Find sale ticket
        $sale_ticket = $this->transactionTicketRepository->findTransactionTicketOfCode($transaction_ticket_code);
        if (empty($sale_ticket)) {
            throw new HttpNotFoundException($this->request, "Không tìm thấy phiếu bán");
        }

        // Sanitize and extract data
        $sanitizeRules = [
            'page' => 'integer',
            'limit' => 'integer',
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $page = $cleanData['page'];
        $limit = $cleanData['limit'];
        
        $params = [
            "page" => $page,
            "page_limit" => $limit,
            "user_id" => $this->auth_data['user_id'],
        ];

        $transaction_ticket = $this->transactionTicketRepository->getPurchaseTicketsBySaleTicket($sale_ticket->getId(), $params);

        $res_return = ["result" => "success"];
        $res_return['data'] = $transaction_ticket;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
