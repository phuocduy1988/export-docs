<?php

namespace Onetech\ExportDocs\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as LaravelController;
use Onetech\Pattern\Traits\ThrowExceptionResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Class   BaseController
 *
 * @Description
 *
 * @Author  DuyLBP
 *
 * @Date    3/6/23
 */
class BaseController extends LaravelController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    use ThrowExceptionResponse;

    /**
     * @param  array|object  $data
     * @param  int  $status
     * @return JsonResponse
     *
     * @author duylbp
     */
    public function jsonData($data, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()
            ->json(
                [
                    'success' => true,
                    'data' => $data,
                ],
                $status
            )
            ->header('Cache-Control', 'no-store, no-cache');
    }

    /**
     * @param $data
     * @param $message
     * @param $status
     * @return \Illuminate\Http\JsonResponse
     *
     * @Description
     *
     * @Author DuyLBP
     *
     * @Date   3/6/23
     */
    public function jsonDataAndMessage($data, $message, $status = Response::HTTP_OK): JsonResponse
    {
        return response()
            ->json(
                [
                    'success' => true,
                    'message' => $message,
                    'data' => $data,
                ],
                $status
            )
            ->header('Cache-Control', 'no-store, no-cache');
    }

    /**
     * @param    $data
     * @param  int  $status
     * @return JsonResponse
     *
     * @author duylbp
     */
    public function jsonTable($data, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()
            ->json(
                [
                    'success' => true,
                    'data' => $data['data'],
                    'count' => $data['total'],
                ],
                $status
            )->header('Cache-Control', 'no-store, no-cache');
    }

    /**
     * @param    $error
     * @param  int  $status
     * @return JsonResponse
     *
     * @author duylbp
     */
    public function jsonError($error, int $status = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        $message = $error;
        $file = '';
        $line = '';
        if (is_object($error)) {
            write_log_exception($error);
            $message = $error->getMessage();
            $file = $error->getFile();
            $line = $error->getLine();
        }

        return response()
            ->json(
                [
                    'success' => false,
                    'message' => $message,
                    'file' => $file,
                    'line' => $line,
                ],
                $status
            )
            ->header('Cache-Control', 'no-store, no-cache');
    }

    /**
     * @param    $error
     * @param  int  $status
     *
     * @Description
     *
     * @Author DuyLBP
     *
     * @Date   3/6/23
     */
    public function forceJsonError($error, int $status = Response::HTTP_INTERNAL_SERVER_ERROR)
    {
        $message = $error;
        $file = '';
        $line = '';
        if (is_object($error)) {
            write_log_exception($error);
            $message = $error->getMessage();
            $file = $error->getFile();
            $line = $error->getLine();
        }

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: *');
        header('Access-Control-Allow-Headers: *');

        // header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
        // header('Access-Control-Allow-Credentials: true');
        // header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Secret');
        // header('Access-Control-Max-Age: 120');

        //Return http json
        $response = response()
            ->json(
                [
                    'message' => $message,
                    'file' => $file,
                    'line' => $line,
                ], $status
            )->header('Cache-Control', 'no-store, no-cache');
        $response->send();
        exit();
    }

    /**
     * @param    $message
     * @param  bool  $success
     * @param  int  $status
     * @return JsonResponse
     *
     * @author duylbp
     */
    public function jsonMessage($message, bool $success = true, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()
            ->json(
                [
                    'success' => $success,
                    'message' => $message,
                ],
                $status
            )
            ->header('Cache-Control', 'no-store, no-cache');
    }

    /**
     * @param    $message
     * @param  bool  $success
     * @param  int  $status
     * @return JsonResponse
     *
     * @author duylbp
     */
    public function jsonErrorMessage($message, bool $success = false, int $status = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        return response()
            ->json(
                [
                    'success' => $success,
                    'message' => $message,
                ],
                $status
            )
            ->header('Cache-Control', 'no-store, no-cache');
    }

    /**
     * @param    $errors
     * @param  bool  $success
     * @param  int  $status
     * @return \Illuminate\Http\JsonResponse
     *
     * @Description
     *
     * @Author DuyLBP
     *
     * @Date   3/6/23
     */
    public function jsonErrorValidate($errors, bool $success = false, int $status = Response::HTTP_UNPROCESSABLE_ENTITY): JsonResponse
    {
        return response()
            ->json(
                [
                    'success' => $success,
                    'errors' => $errors,
                ],
                $status
            )
            ->header('Cache-Control', 'no-store, no-cache');
    }

    /**
     * @param    $message
     * @param  bool  $success
     * @param  int  $status
     * @return JsonResponse
     *
     * @author duylbp
     */
    public function jsonPermissionDenied(int $status = Response::HTTP_NOT_ACCEPTABLE): JsonResponse
    {
        return response()
            ->json(
                [
                    'success' => false,
                    'message' => trans('PERMISSION_DENIED'),
                ],
                $status
            )
            ->header('Cache-Control', 'no-store, no-cache');
    }

    /**
     * @param    $errors
     * @param  bool  $success
     * @param  int  $status
     * @return JsonResponse
     */
    public function jsonValidate($errors, bool $success = false, int $status = Response::HTTP_UNPROCESSABLE_ENTITY): JsonResponse
    {
        return response()
            ->json(
                [
                    'success' => $success,
                    'errors' => $errors,
                ],
                $status
            )
            ->header('Cache-Control', 'no-store, no-cache');
    }

    public function writeLogException($e): void
    {
        moduleWriteLogException($e);
    }
}
