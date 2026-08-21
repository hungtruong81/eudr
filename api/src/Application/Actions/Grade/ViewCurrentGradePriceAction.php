<?php

declare(strict_types=1);

namespace App\Application\Actions\Grade;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use App\Application\Utility\Utils;
use App\Application\Utility\Validator;

class ViewCurrentGradePriceAction extends GradeAction
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
        /*
        $scope = Utils::resolveScope($permissions, 'grade', 'view');
        if (empty($scope)) {
            throw new HttpForbiddenException($this->request, 'Thiếu quyền truy cập');
        }
        */
        
        $scope = "all"; // Allow all scopes to view list of grades

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

        $formData = $this->request->getQueryParams();
        $validator = new Validator($this->request);
        $validator->validate('at_datetime', $formData['at_datetime'] ?? null, 'date');
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

        $at_datetime = null;
        if (!empty($formData['at_datetime'])) {
            $at_datetime = date('Y-m-d H:i:s', strtotime((string)$formData['at_datetime']));
        }

        $currentPrice = $this->gradeRepository->getCurrentPriceOfGrade((int)$grade->getId(), $at_datetime);

        return $this->respondWithData([
            'result' => 'success',
            'trace_id' => $trace_id,
            'data' => [
                'grade_id' => $grade->getId(),
                'grade_code' => $grade->getCode(),
                'at_datetime' => $at_datetime ?: date('Y-m-d H:i:s'),
                'price' => $currentPrice,
            ],
        ]);
    }
}
