<?php

namespace App\Http\Middleware\Api;

//use App\Models\User;
use Closure;
use Illuminate\Support\Str;

//use Illuminate\Contracts\Routing\TerminableMiddleware;

class WebserviceLogger
{
    private static $loggerType = null;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $loggerType='json')
    {
        static::$loggerType = $loggerType;
        return $next($request);
    }

    public function terminate($request, $response)
    {

        if ( env('LOG_WEBSERVICE', false) ) {

            $disllowLoggingUrls = [
                'update/location'
            ];

            foreach ($disllowLoggingUrls as $tempUrl) {
                if (Str::endsWith($request->fullUrl(), $tempUrl)) {
                    return;
                }
            }

            if ( static::$loggerType === 'json' && method_exists($response, 'getData') ) {
                $output   = (array) $response->getData();

                if ( is_array($output) )
                    $output = json_encode($output);
            } else if ( static::$loggerType === 'content' ) {
                $output = $response->getContent();
            } else {
                return;
            }

            // TODO: Uncomment password when shipped to production.
            $inputExcept = [
                'password',
            ];

            $input = [];
            foreach ($request->request->all() as $key => $value) {
                $input[$key] = $value;
                if (in_array($key, $inputExcept, true)) {
                    $input[$key] = '********';
                }
            }

         //   $input = User::getEncryptedValues($input);

            $filename = 'webservice_' . now()->toDateString() . '.log';

            $dataToLog  = '[' . now()->toDateTimeString() . '] log.DEBUG: ';
            $dataToLog .= 'URL: '    . $request->fullUrl() . "\n";
            $dataToLog .= 'Method: ' . $request->method() . "\n";
            $dataToLog .= 'Input: '  . print_r((array) $input, true) . "\n";
            $dataToLog .= 'Output: ' . $output . "\n";

            // Finally write log
            \File::append( storage_path('logs' . DIRECTORY_SEPARATOR . $filename), $dataToLog . "\n" . str_repeat("=", 20) . "\n\n");
        }
    }
}
