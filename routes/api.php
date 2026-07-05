<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

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


Route::post('/request-token', function(Request $request) {
    $validated = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
        'title' => 'required',
        'abilities' => 'array',
        'abilities.*' => 'string|in:whose-name',
    ]);

    $credentials = [
        'email' => $validated['email'],
        'password' => $validated['password'],
    ];

    $tokenTitle = $validated['title'];
    $tokenAbilities = isset($validated['abilities'])
        ? $validated['abilities']
        : [];

    if (Auth::attempt($credentials)) {
        $user = Auth::user();

        $token = $user->createToken(
            $tokenTitle,
            $tokenAbilities
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'title' => $tokenTitle,
            'abilities' => $tokenAbilities,
        ], 201);
    }

    return response()->json([
        'errors' => [
            'login' => [
                'Email and password does not match'
            ]
        ],
        'message' => 'Username and password does not match',
    ], 401);
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

        Route::post('/query/batch', function (Request $request, QueryService $service) {
            $validated = $request->validate([
                'queries'     => 'required|array|min:1|max:100',
                'queries.*.u' => 'required|string',
                'queries.*.s' => 'required|string',
                'queries.*.q' => 'required|string',
            ]);

            $usernames = $service->whatAreTheNamesOf(array_map(fn ($query) => [
                'username'     => $query['u'],
                'service'      => $query['s'],
                'askedService' => $query['q'],
            ], $validated['queries']));

            $results = array_map(fn ($username) => ['username' => $username], $usernames);

            $allResolved = !in_array(null, $usernames, true);

            return response()->json($results, $allResolved ? 200 : 207);
        });
    });
