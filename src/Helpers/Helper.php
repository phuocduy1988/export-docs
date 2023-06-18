<?php

if (!function_exists('writeLogException')) {
    /**
     * write log when exception
     *
     * @param  \Exception  $e
     * @return void
     */
    function writeLogException(Exception $e): void
    {
        $content = '';
        $r = explode('#0', $e->getTraceAsString());
        if (isset($r[1])) {
            $r = explode('#10', $r[1]);
            $content = $r[0];
        }
        \Log::error($e->getMessage() . PHP_EOL . '#0 More exception::' . $content . PHP_EOL . PHP_EOL);
    }
}

if (!function_exists('dateNow')) {
    /**
     * Date format YYYY-MM-DD H:M:S
     */
    function dateNow($format = 'Y-m-d H:i:s'): string
    {
        return \Carbon\Carbon::now(config('app.timezone', 'Asia/Tokyo'))->format($format);
    }
}

if (!function_exists('logInfo')) {
    /**
     * @param $log
     * Quick write log info
     *
     * @Description
     *
     * @Author DuyLBP
     */
    function logInfo($log)
    {
        \Log::channel(config('logging.default'))->info('================================================');
        \Log::channel(config('logging.default'))->info($log);
    }
}

if (!function_exists('forceJsonError')) {
    function forceJsonError($error, int $status = \Symfony\Component\HttpFoundation\Response::HTTP_INTERNAL_SERVER_ERROR)
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

        //Return http json
        $response = response()->json(
            [
                'message' => $message,
                'file' => $file,
                'line' => $line,
            ],
            $status,
        );
        $response->send();
        exit();
    }
}
if (!function_exists('authParam')) {
    function authParam($param)
    {
        $user = auth()->user();
        if ($user) {
            return $user?->{$param};
        }

        return null;
    }
}

if (!function_exists('extractArrayValueFromEnumCases')) {
    /**
     * @param $log
     * Quick write log info
     *
     * @Description
     *
     * @Author DuyLBP
     */
    function extractArrayValueFromEnumCases($data, $getValue = false)
    {
        $newArray = [];
        foreach ($data as $item) {
            if ($getValue) {
                $newArray[] = $item->value;

            } else {
                $newArray[$item->name] = $item->value;
            }
        }

        return $newArray;
    }
}
