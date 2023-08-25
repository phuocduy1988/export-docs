<?php

namespace Onetech\ExportDocs\Services;


use Onetech\ExportDocs\Facades\OpenAI;

class OpenAIService
{
    public static function translateString($string = '', $origin = 'en', $lang = 'ja', $context = '', $model = "gpt-3.5-turbo")
    {
        try {

            return $string;

            $result = OpenAI::chat()->create([
                "model" => $model,
                "messages" => [
                    [
                        "role" => "system", "content" => self::promptSystem($context)
                    ],
                    [
                        "role" => "user", "content" => self::promptHeader($origin, $lang)
                    ],
                    [
                        "role" => "user", "content" => $string
                    ]
                ],
                "temperature" => 0.4,
                "n" => 1,
            ]);
            // if the result is not empty, return the translated string
            if ($result->choices && count($result->choices) > 0 && $result->choices[0]->message) {
                $translation = $result->choices[0]->message->content ?? $string;
                moduleLogInfo('$translation');
                moduleLogInfo($translation);
                return self::syncVars($string, $translation);

            } else {
                moduleLogInfo($string);
                return $string;
            }
        } catch (\Exception $e) {
            moduleWriteLogException($e);
            return $string;
        }
    }

    public static function promptSystem($context = '')
    {
        if($context != '') {
            return "You are a translator. Your job is to translate the following text into the specified language, using the given context: $context.";
        } else {
            return "You are a translator. Your job is to translate the following text to the language specified in the prompt.";
        }
    }

    public static function promptHeader($origin = 'en', $lang = 'es')
    {
        switch ($origin) {
            case 'en':
                $strOrigin = "english";
                break;
            case 'vi':
                $strOrigin = "vietnamese";
                break;
            case 'fr':
                $strOrigin = "french";
                break;
            case 'de':
                $strOrigin = "german";
                break;
            case 'it':
                $strOrigin = "italian";
                break;
            case 'pt':
                $strOrigin = "portuguese";
                break;
            default:
                $strOrigin = "vietnamese";
                break;
        }

        switch ($lang) {
            case 'en':
                $strLang = "english";
                break;
            case 'ja':
                $strLang = "japanese";
                break;
            case 'fr':
                $strLang = "french";
                break;
            case 'de':
                $strLang = "german";
                break;
            case 'it':
                $strLang = "italian";
                break;
            case 'pt':
                $strLang = "portuguese";
                break;
            default:
                $strLang = "japanese";
                break;
        }
        return "Translate the following text from $strOrigin to $strLang, Make sure this translation preserves the json structure";
    }

    public static function syncVars($str1, $str2) {

        // find all variables with subfix :
        preg_match_all('/:(\w+)/', $str1, $matches);
        if ($matches && isset($matches[0])) {
            // for each variable with subfix : found in str1, replace it with the same variable in str2
            foreach ($matches[0] as $match) {
                $str2 = preg_replace('/' . $match . '/', $match, $str2, 1);
            }
        }
        // return new string with replaced variables
        return $str2;
    }

}
