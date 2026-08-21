<?php

declare(strict_types=1);

namespace App\Application\Actions\Price;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;

class UpdatePriceAction extends PriceAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'price', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $code = addslashes(trim((string)$this->resolveArg('code')));
        $current = $this->priceRepository->findPriceOfCodeWithPermission(
            $code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($current)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy bảng giá');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('price_name', $formData['price_name'] ?? null, 'required|string');
        $validator->validate('price_type', $formData['price_type'] ?? null, 'required|string');
        $validator->validate('domestic_price', $formData['domestic_price'] ?? null, 'required|numeric|min:0');
        $validator->validate('international_price', $formData['international_price'] ?? null, 'required|numeric|min:0');

        if ($validator->hasErrors()) {
            $errors = [];
            foreach ((array)$validator->getErrors() as $fieldErrors) {
                $errors[] = is_array($fieldErrors) ? implode(', ', $fieldErrors) : (string)$fieldErrors;
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errors));
        }

        $clean = $validator->sanitize($formData, [
            'price_name' => 'string',
            'price_type' => 'string',
            'domestic_price' => 'numeric',
            'international_price' => 'numeric',
        ]);

        $updated = $this->priceRepository->updatePriceWithPermission(
            (int)$current->getId(),
            [
                'price_name' => $clean['price_name'],
                'price_type' => $clean['price_type'],
                'domestic_price' => $clean['domestic_price'],
                'international_price' => $clean['international_price'],
                'updated_at' => date('Y-m-d H:i:s', time()),
                'updated_by' => (int)$this->auth_data['user_id'],
            ],
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );

        Utils::save_log($this->logger, [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'price',
            'action' => 'update',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$updated->getId(),
        ]);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'price' => $updated->jsonSerialize(),
        ]);
    }
}
