<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use Domain\WhoseName\QueryService;
use Domain\WhoseName\PoolService;
use Domain\WhoseName\PoolQueryRepository;

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

            $answers = $service->whatAreTheNamesOf(array_map(fn ($query) => [
                'username'     => $query['u'],
                'service'      => $query['s'],
                'askedService' => $query['q'],
            ], $validated['queries']));

            $results = array_map(fn ($query, $answer) => [
                'u' => $query['u'],
                's' => $query['s'],
                'q' => $query['q'],
                'a' => $answer,
            ], $validated['queries'], $answers);

            $allResolved = !in_array(null, $answers, true);

            return response()->json($results, $allResolved ? 200 : 207);
        });

        Route::get('/pool', function (Request $request, PoolService $service, PoolQueryRepository $pools) {
            $poolName = $request->input('p', '');
            $askedService = $request->input('q', '');

            $pool = $pools->findByName($poolName);
            $answers = $service->whatAreTheNamesOf($poolName, $askedService);

            // Echo each member as a query (u = member, s = pool field,
            // q = asked service) alongside its answer, like the batch endpoint.
            $results = array_map(fn ($member, $answer) => [
                'u' => $member,
                's' => $pool->getField(),
                'q' => $askedService,
                'a' => $answer,
            ], $pool->getNames(), $answers);

            if (empty($results)) {
                return response()->json($results, 404);
            }

            $allResolved = !in_array(null, $answers, true);

            return response()->json($results, $allResolved ? 200 : 207);
        });

        Route::get('/pool/names', function (Request $request, PoolService $service) {
            $names = $service->whoseNamesAreThere(
                $request->input('p', ''),
                $request->input('q', '')
            );

            return response()->json(
                ['names' => $names],
                empty($names) ? 404 : 200
            );
        });
    });
