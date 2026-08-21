<?php

declare(strict_types=1);

namespace App\Application\Actions\Price;

use App\Application\Utility\Utils;
use App\Application\Utility\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

class CreatePriceAction extends PriceAction
{
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'price', 'create');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
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

        /*
        $existing = $this->priceRepository->findPriceOfCodeWithPermission(
            (string)$clean['price_code'],
            (int)$this->auth_data['user_id'],
            'own',
            $this->auth_data['company_id'] ?? null
        );
        if (!empty($existing)) {
            throw new HttpBadRequestException($this->request, 'Mã bảng giá đã tồn tại');
        }
        */

        $item = $this->priceRepository->createPrice([
            'price_code' => $this->priceRepository->generateCode(),
            'price_name' => $clean['price_name'],
            'price_type' => $clean['price_type'],
            'domestic_price' => $clean['domestic_price'],
            'international_price' => $clean['international_price'],
            'company_id' => (int)($this->auth_data['company_id'] ?? 0),
            'created_at' => date('Y-m-d H:i:s', time()),
            'created_by' => (int)$this->auth_data['user_id'],
            'updated_at' => null,
            'updated_by' => 0,
            'deleted_at' => null,
            'deleted_by' => 0,
        ]);

        if (empty($item)) {
            throw new HttpBadRequestException($this->request, 'Không thể tạo bảng giá');
        }

        Utils::save_log($this->logger, [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'price',
            'action' => 'create',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$item->getId(),
        ]);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'price' => $item->jsonSerialize(),
        ]);
    }
}
