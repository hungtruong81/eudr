<?php

declare(strict_types=1);

namespace App\Application\Actions\TransportationRoute;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;


class ListTransportationRouteAction extends TransportationRouteAction
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

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);

        // Check permission to view transportation routes
        $scope = Utils::resolveScope($permissions, 'transportation_route', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, "Thiếu quyền truy cập");
        }
        
        $formData = $this->request->getQueryParams();

        $validator = new Validator($this->request);

        $validator->validate('page', $formData['page'] ?? null, 'required|integer|min:1');
        $validator->validate('limit', $formData['limit'] ?? null, 'required|integer|min:1|max:100');
        $validator->validate('vehicle_id', $formData['vehicle_id'] ?? null, 'integer|min:1');
        $validator->validate('destination_factory_id', $formData['destination_factory_id'] ?? null, 'integer|min:1');
        $validator->validate('status', $formData['status'] ?? null, 'in:pending,arrived,cancelled,unloaded,all');
        $validator->validate('start_date', $formData['start_date'] ?? null, 'date');
        $validator->validate('end_date', $formData['end_date'] ?? null, 'date');
        $validator->validate('transport_date_from', $formData['transport_date_from'] ?? null, 'date');
        $validator->validate('transport_date_to', $formData['transport_date_to'] ?? null, 'date');

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
            'vehicle_id' => 'integer',
            'destination_factory_id' => 'integer',
            'status' => 'string',
            'start_date' => 'date',
            'end_date' => 'date',
            'transport_date_from' => 'date',
            'transport_date_to' => 'date'
        ];

        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $page = $cleanData['page'];
        $limit = $cleanData['limit'];
        $vehicle_id = $cleanData['vehicle_id'] ?? 0;
        $destination_factory_id = $cleanData['destination_factory_id'] ?? 0;
        $status = $cleanData['status'] ?? 'all';
        $start_date = $cleanData['start_date'] ?? null;
        $end_date = $cleanData['end_date'] ?? null;
        $transport_date_from = $cleanData['transport_date_from'] ?? null;
        $transport_date_to = $cleanData['transport_date_to'] ?? null;
        
        $params = [
            "page" => $page,
            "page_limit" => $limit,
            "vehicle_id" => $vehicle_id,
            "destination_factory_id" => $destination_factory_id,
            "status" => $status,
            "start_date" => $start_date,
            "end_date" => $end_date,
            "transport_date_from" => $transport_date_from,
            "transport_date_to" => $transport_date_to,
        ];

        $transportation_routes = $this->transportationRouteRepository->findAll(
            $params,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        $res_return = ["result" => "success"];
        $res_return['data'] = $transportation_routes;
        $res_return['trace_id'] = $trace_id;

        return $this->respondWithData($res_return);
    }

}
