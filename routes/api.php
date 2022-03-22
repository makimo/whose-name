<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Domain\WhoseName\QueryService;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



Route::middleware('auth:sanctum', 'ability:whose-name')
    ->prefix('whose-name')
    ->group(function () {
        Route::get('/query', function(Request $request, QueryService $service) {
            $username = $service->whatIsTheNameOf(
                $request->input('u', ''),
                $request->input('s', ''),
                $request->input('q', '')
            );

            return response()->json(
                ['username' => $username], 
                $username === null ? 404: 200
            );
        });
    });
