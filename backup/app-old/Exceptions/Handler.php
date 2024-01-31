<?php

namespace App\Exceptions;

use App\Classes\RestAPI;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }


    public function render($request, Exception $exception)
    {
        if ( $request->is('api/*') || $request->is('debug/*') ) {
            // I'm unsure about this exception will be updated as work will progress
            if ($exception instanceof ModelNotFoundException) {
                return RestAPI::response( 'Object not found.', false, 'object_not_found' );
            }
            if ($exception instanceof ValidationException) {
                return RestAPI::response(array_unique(Arr::flatten($exception->errors())), false, 'validation_error');

            }
            if (!($exception instanceof HttpResponseException) && !($exception instanceof ValidationException)) {
                // if ( env('APP_ENV') === 'production' ) {
                //     return RestAPI::response( 'Something went wrong or you are not allowed to perform this action.', false, Str::snake(class_basename(get_class($exception))) );
                // }
                return RestAPI::response( 'UNTRACKED: ' . $exception->getMessage(), false, Str::snake(class_basename(get_class($exception))) );
            }
        }
        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\RedirectResponse | JsonResponse
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'],401);
        }
        $guard = Arr::get($exception->guards(), 0);
        switch ($guard) {
            case 'admin': $login = 'login';
                break;
            default: $login = 'login';
                break;
        }
        return redirect()->guest(route($login));
    }
}
