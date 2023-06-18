<?php

namespace Onetech\Pattern\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ThrowExceptionResponse
{
    /**
     * @param
     * @return JsonResponse
     *
     * @Description Throw permission denied exception
     *
     * @Author minhluc
     *
     * @Date 3/22/23
     */
    public function throwPermissionDeniedException(): JsonResponse
    {
        return response()->json(
            [
                'success' => false,
                'data' => trans('errors.permission_denied'),
            ],
            Response::HTTP_FORBIDDEN,
        );
    }

    /**
     * @param
     * @return JsonResponse
     *
     * @Description Throw unauthorized exception
     *
     * @Author minhluc
     *
     * @Date 3/22/23
     */
    public function throwUnauthorizedException(): JsonResponse
    {
        return response()->json(
            [
                'success' => false,
                'data' => trans('errors.unauthorized'),
            ],
            Response::HTTP_UNAUTHORIZED,
        );
    }

    /**
     * @param
     * @return JsonResponse
     *
     * @Description Throw internal exception
     *
     * @Author minhluc
     *
     * @Date 3/22/23
     */
    public function throwInternalServerException(): JsonResponse
    {
        return response()->json(
            [
                'success' => false,
                'message' => trans('errors.unexpected_error'),
            ],
            Response::HTTP_INTERNAL_SERVER_ERROR,
        );
    }

    /**
     * @param  \Exception  $error
     * @param  int  $status
     * @return JsonResponse
     *
     * @Description
     *
     * @Author minhluc
     *
     * @Date 2023/04/11
     */
    public function throwDefaultErrorException(
        \Exception $error,
        int $status = Response::HTTP_INTERNAL_SERVER_ERROR,
    ): JsonResponse {
        if (app()->isProduction()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => trans('errors.default_error'),
                ],
                $status,
            );
        }

        return response()->json(
            [
                'success' => false,
                'message' => $error->getMessage(),
                'file' => $error->getFile(),
                'line' => $error->getLine(),
            ],
            $status,
        );
    }
}
