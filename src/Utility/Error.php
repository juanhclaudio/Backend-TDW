<?php

/**
 * src/Utility/Error.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\IPanel\Utility;

use Slim\Http\Interfaces\ResponseInterface;
use Slim\Http\Response;
use Fig\Http\Message\StatusCodeInterface as StatusCode;

/**
 * Class Error
 */
final readonly class Error
{
    // Error messages
    public const array MESSAGES = [
        StatusCode::STATUS_BAD_REQUEST            // 400
            => 'BAD REQUEST: name or e-mail already exists, or role does not exist',
        StatusCode::STATUS_UNAUTHORIZED           // 401
            => 'UNAUTHORIZED: invalid Authorization header',
        StatusCode::STATUS_FORBIDDEN              // 403
            => 'FORBIDDEN You don\'t have permission to access',
        StatusCode::STATUS_NOT_FOUND              // 404
            => 'NOT FOUND: Resource not found',
        StatusCode::STATUS_NOT_ACCEPTABLE         // 406
            => 'NOT ACCEPTABLE: Requested resource not found',
        StatusCode::STATUS_UNPROCESSABLE_ENTITY   // 422
            => 'UNPROCESSABLE ENTITY: Mandatory parameters are missing or the data provided does not meet the requirements',
        StatusCode::STATUS_PRECONDITION_REQUIRED  // 428
            => "PRECONDITION REQUIRED: This request is required to be conditional; try using `If-Match`.",
        StatusCode::STATUS_METHOD_NOT_ALLOWED     // 405
            => 'METHOD NOT ALLOWED',
        StatusCode::STATUS_INTERNAL_SERVER_ERROR  // 500
            => 'INTERNAL SERVER ERROR',
        StatusCode::STATUS_NOT_IMPLEMENTED        // 501
            => 'METHOD NOT IMPLEMENTED',
    ];

    /**
     * @param Response|ResponseInterface $response Original Response
     * @param int $statusCode Response Status code
     * @param array<string, int|string>|null $error Error parameters
     *
     * @return Response Error response
     */
    public static function createResponse(
        Response|ResponseInterface $response,
        int $statusCode,
        array|null $error = null
    ): Response {
        $msgdata = $error ?? [ 'code' => $statusCode, 'message' => self::MESSAGES[$statusCode] ];

        return $response
            ->withJson($msgdata, $statusCode);
    }
}
