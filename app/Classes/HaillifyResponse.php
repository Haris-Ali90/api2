<?php

namespace App\Classes;

class HaillifyResponse
{

    private static $pagination;

    public static function response($output, $status = true, $message = '', $format = 'json')
    {
        $response = [
            'status' => $status ? true : false,
            'message' => $status ? $message : (is_array($output) ? implode("\n", $output) : $output),
            'paging' => self::$pagination ?: new \stdClass(),
        ];

        if (!$status) {
            $response['error_code'] = $message;
        } else {
            $response['body'] = $output;
        }
        return response()->json($response, 200, ['Content-type' => 'application/json; charset=utf-8'], JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE);
    }


    public static function responsewithCode($output, $status = true, $message = '', $format = 'json')
    {
        $response = [
            'status' => $status ? 200 : 400,
            'message' => $status ? $message : (is_array($output) ? implode("\n", $output) : $output),
        ];

        if (!$status) {
            $status_code = 400;
        } else {
            $status_code = 200;
        }

        return response()->json($response, $status_code);
    }

    public static function messageResponse($output, $status = true, $message = '')
    {
        $status = (bool)$status;

        return $status ?
            self::response(new \stdClass, true, $output) :
            self::response($output, $status, $message);
    }

// new work
    public static function responseForCreateOrder($output, $status = true, $message = '', $format = 'json')
    {
        $response = [
            "copyright"=> "Copyright © 2022 JoeyCo Inc. All rights reserved.",
            "http"=> [
                "code"=> 201,
                "message"=> "Created"
            ],
            // 'status' => $status ? true : false,
            // 'message' => $status ? $message : (is_array($output) ? implode("\n", $output) : $output),
            // 'paging' => self::$pagination ?: new \stdClass(),
        ];

        if (!$status) {
            $response['error_code'] = $message;
            $status_code = 400;
        } else {
            $response['response'] = $output;
            $status_code = 201;
        }
        return response()->json($response, $status_code, ['Content-type' => 'application/json; charset=utf-8'], JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE);
    }

    public static function responseForOrderDetails($output, $status = true, $message = '', $format = 'json')
    {
        $response = [
            "copyright"=> "Copyright © 2022 JoeyCo Inc. All rights reserved.",
            "http"=> [
                "code"=> 200,
                "message"=> "ok"
            ],
            // 'status' => $status ? true : false,
            // 'message' => $status ? $message : (is_array($output) ? implode("\n", $output) : $output),
            // 'paging' => self::$pagination ?: new \stdClass(),
        ];

        if (!$status) {
            $response['error_code'] = $message;
            $status_code = 400;
        } else {
            //$response['body'] = $output;
            $response['response'] = $output;
            $status_code = 200;
        }
        return response()->json($response, $status_code, ['Content-type' => 'application/json; charset=utf-8'], JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE);
    }

    // new work (to show response in message not in body)
      public static function responseforschedule($output, $status = true, $message = '', $format = 'json')
      {
          $response = [
              'status' => $status ? true : false,
              // 'message' => $status ? $message : (is_array($output) ? implode("\n", $output) : $output),
              'message' => $output,
              'paging' => self::$pagination ?: new \stdClass(),
          ];

          if (!$status) {
              $response['error_code'] = $message;
          } else {
              $response['body'] = $message;
          }

          return response()->json($response, 200, ['Content-type' => 'application/json; charset=utf-8'], JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE);
      }

    public static function setPagination(\Illuminate\Pagination\LengthAwarePaginator $paginator)
    {
        self::$pagination = new \stdClass();
        self::$pagination->total_records = $paginator->total();
        self::$pagination->current_page = $paginator->currentPage();
        self::$pagination->total_pages = $paginator->lastPage();
        self::$pagination->limit = intval($paginator->perPage());

        return new static;
    }

    public static function emptyResponse($status = true, $dev_message = '', $format = 'json')
    {

        $response = [
            'status' => $status ? true : false
        ];

        if (!$status) {
            $response['error_code'] = $dev_message;
        }

        return response()->json($response);
    }

}
