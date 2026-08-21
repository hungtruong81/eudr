<?php

declare(strict_types=1);

namespace App\Application\Actions\Land;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class SellerLandListAction extends LandAction
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

        $validator->validate('seller_user_id', $formData['seller_user_id'] ?? null, 'required|integer');
        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('search', $formData['search'] ?? null, 'string');

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
            'seller_user_id' => 'integer',
            'page' => 'integer',
            'limit' => 'integer',
            'search' => 'string'
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $seller_user_id = $cleanData['seller_user_id'];
        $page = $cleanData['page'];
        $limit = $cleanData['limit'];
        $search = $cleanData['search'] ?? null;

        $data_connection = $this->connectionRepository->findConnectionBetweenUsers($this->auth_data['user_id'], $seller_user_id, 'accepted');
        if (empty($data_connection)) {
            throw new HttpForbiddenException($this->request, "Bạn chưa kết nối với người bán này. Vui lòng kết nối và thử lại");
        }
        
        $params = [
            //"seller_user_id" => $seller_user_id,
            "page" => $page,
            "page_limit" => $limit,
            "search" => $search
        ];

        $lands = $this->landRepository->listLandOfSeller($seller_user_id, $params);
        
        $res_return = ["result" => "success"];
        $res_return['data'] = $lands;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
