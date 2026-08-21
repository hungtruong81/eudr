<?php

declare(strict_types=1);

namespace App\Application\Actions\TransactionTicket;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class ListPurchaseTicketsUnroutedAction extends TransactionTicketAction
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

        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('start_date', $formData['start_date'] ?? null, 'date');
        $validator->validate('end_date', $formData['end_date'] ?? null, 'date');
        $validator->validate('contract_code', $formData['contract_code'] ?? null, 'string');
        $validator->validate('search', $formData['search'] ?? null, 'string');
        $validator->validate('account_type', $formData['account_type'] ?? null, 'in:farmer,purchaser,trader,company');

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
            'page' => 'integer',
            'limit' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'contract_code' => 'string',
            'search' => 'string',
            'account_type' => 'string',
            'target_user_id' => 'integer'
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $transaction_ticket_type = 'purchase';
        $page = $cleanData['page'];
        $limit = $cleanData['limit'];

        $start_date = $cleanData['start_date'] ?? null;
        $end_date = $cleanData['end_date'] ?? null;
        $contract_code = $cleanData['contract_code'] ?? null;
        $search = $cleanData['search'] ?? null;
        $account_type = $cleanData['account_type'] ?? null;
        $target_user_id = $cleanData['target_user_id'] ?? null;

        $params = [
            "page" => $page,
            "page_limit" => $limit,
            "transaction_ticket_type" => $transaction_ticket_type,
            "user_id" => $this->auth_data['user_id'],
            "start_date" => $start_date,
            "end_date" => $end_date,
            "contract_code" => $contract_code,
            "account_type" => $account_type,
            "search" => $search,
            "target_user_id" => $target_user_id
        ];

        $transaction_ticket = $this->transactionTicketRepository->findPurchaseTicketsUnrouted($params);

        $res_return = ["result" => "success"];
        $res_return['data'] = $transaction_ticket;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
