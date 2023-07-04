<?php

if (!function_exists('moduleWriteLogException')) {
    /**
     * write log when exception
     *
     * @param  \Exception  $e
     * @return void
     */
    function moduleWriteLogException(Exception $e): void
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

if (!function_exists('moduleDateNow')) {
    /**
     * Date format YYYY-MM-DD H:M:S
     */
    function moduleDateNow($format = 'Y-m-d H:i:s'): string
    {
        return \Carbon\Carbon::now(config('app.timezone', 'Asia/Tokyo'))->format($format);
    }
}

if (!function_exists('moduleLogInfo')) {
    /**
     * @param $log
     * Quick write log info
     *
     * @Description
     *
     * @Author DuyLBP
     */
    function moduleLogInfo($log)
    {
        \Log::channel(config('logging.default'))->info('================================================');
        \Log::channel(config('logging.default'))->info($log);
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

if (!function_exists('arrayCastRecursive')) {
    function arrayCastRecursive($array)
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $array[$key] = arrayCastRecursive($value);
                }
                if ($value instanceof stdClass) {
                    $array[$key] = arrayCastRecursive((array) $value);
                }
            }
        }
        if ($array instanceof stdClass) {
            return arrayCastRecursive((array) $array);
        }

        return $array;
    }
}

if (!function_exists('formatSheetName')) {
    function formatSheetName($originName): string
    {
        $originName = preg_replace("/\s+/", '', $originName);
        $originName = preg_replace('/{[^}]+}/', '', $originName);
        $originName = preg_replace("/[^#\w]/", '_', $originName);
        $originName = preg_replace('/_+/', '_', $originName);

        return rtrim($originName, '_');
    }
}

if (!function_exists('formatSheetId')) {
    function formatSheetId($sheetIndex, int $digit = 3, $padString = '0'): string
    {
        return str_pad($sheetIndex, $digit, $padString, STR_PAD_LEFT);
    }
}

if (!function_exists('formatPath')) {
    function formatPath($url): string
    {
        $paths = explode('/', $url);

        foreach ($paths as $index => &$path) {
            if (is_numeric($path)) {
                if (isset($paths[$index - 1])) {
                    $name = $paths[$index - 1];
                    if (!\Illuminate\Support\Str::contains($name, 'Id')) {
                        $name .= 'Id';
                    }
                    $name = \Illuminate\Support\Str::camel($name);
                    $path = "{{$name}}";
                } else {
                    $path = '{id}';
                }
            }
        }

        return implode('/', $paths);
    }
}
