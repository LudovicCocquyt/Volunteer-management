<?php

namespace App\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Format unique des erreurs de l'API : {status: "Error", message, errors?: [{field, message}]}.
 */
final class ApiError
{
    public static function response(string $message, int $status, array $errors = []): JsonResponse
    {
        $body = ['status' => 'Error', 'message' => $message];
        if ([] !== $errors) {
            $body['errors'] = $errors;
        }

        return new JsonResponse($body, $status);
    }

    public static function fromViolations(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = ['field' => $violation->getPropertyPath(), 'message' => $violation->getMessage()];
        }

        return self::response($errors[0]['message'] ?? 'Données invalides.', JsonResponse::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }
}
