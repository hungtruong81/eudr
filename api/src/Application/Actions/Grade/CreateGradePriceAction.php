<?php

declare(strict_types=1);

namespace App\Application\Actions\Grade;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class CreateGradePriceAction extends GradeAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $trace_id = Utils::generateRandomString(25);

        if (empty($this->auth_data['user_id'])) {
            throw new HttpUnauthorizedException($this->request, 'Thiếu quyền truy cập');
        }

        $permissions = $this->userRepository->getUserPermissions((int)$this->auth_data['user_id']);
        $scope = Utils::resolveScope($permissions, 'grade', 'update');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }

        $grade_code = addslashes(trim((string)$this->resolveArg('code')));
        $grade = $this->gradeRepository->findGradeOfCodeWithPermission(
            $grade_code,
            (int)$this->auth_data['user_id'],
            (string)$scope,
            $this->auth_data['company_id'] ?? null
        );
        if (empty($grade)) {
            throw new HttpNotFoundException($this->request, 'Không tìm thấy grade');
        }

        $formData = $this->getFormData();
        $validator = new Validator($this->request);

        $validator->validate('domestic_price', $formData['domestic_price'] ?? null, 'required|numeric|min:0');
        $validator->validate('international_price', $formData['international_price'] ?? null, 'required|numeric|min:0');
        $validator->validate('effective_from', $formData['effective_from'] ?? null, 'required|date');
        $validator->validate('effective_to', $formData['effective_to'] ?? null, 'date');
        $validator->validate('note', $formData['note'] ?? null, 'string');

        if ($validator->hasErrors()) {
            $errorMessages = [];
            $errors = $validator->getErrors();
            if (is_array($errors)) {
                foreach ($errors as $fieldErrors) {
                    if (is_array($fieldErrors)) {
                        $errorMessages[] = implode(', ', $fieldErrors);
                    } else {
                        $errorMessages[] = (string)$fieldErrors;
                    }
                }
            }
            throw new HttpBadRequestException($this->request, implode('; ', $errorMessages));
        }

        $sanitizeRules = [
            'domestic_price' => 'float',
            'international_price' => 'float',
            'effective_from' => 'string',
            'effective_to' => 'string',
            'note' => 'string',
        ];
        $cleanData = $validator->sanitize($formData, $sanitizeRules);

        $effective_from = date('Y-m-d H:i:s', strtotime((string)$cleanData['effective_from']));
        $effective_to = !empty($cleanData['effective_to'])
            ? date('Y-m-d H:i:s', strtotime((string)$cleanData['effective_to']))
            : null;

        if (!empty($effective_to) && strtotime($effective_to) <= strtotime($effective_from)) {
            throw new HttpBadRequestException($this->request, 'effective_to phải lớn hơn effective_from');
        }

        $priceData = [
            'domestic_price' => (float)$cleanData['domestic_price'],
            'international_price' => (float)$cleanData['international_price'],
            'effective_from' => $effective_from,
            'effective_to' => $effective_to,
            'note' => $cleanData['note'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => (int)$this->auth_data['user_id'],
        ];

        $createdPrice = $this->gradeRepository->createGradePrice((int)$grade->getId(), $priceData);
        if (empty($createdPrice)) {
            throw new HttpBadRequestException($this->request, 'Không thể tạo dữ liệu giá grade');
        }

        $log = [
            'milliseconds' => floor(microtime(true) * 1000),
            'trace_id' => $trace_id,
            'log_type' => 'grade',
            'action' => 'create_price',
            'user_id' => (string)$this->auth_data['user_id'],
            'extra_1' => (string)$grade->getId(),
            'extra_2' => (string)$createdPrice['grade_price_id'],
        ];
        Utils::save_log($this->logger, $log);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => $createdPrice,
        ]);
    }
}
