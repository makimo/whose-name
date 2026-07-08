<?php

use Domain\WhoseName\PoolService;
use Domain\WhoseName\PoolQueryRepository;
use Domain\WhoseName\QueryService;
use Domain\WhoseName\Pool;


// --- whatAreTheNamesOf: one response per member, index-aligned ---

gest('usage', 'Querying a pool returns one response per member, preserving each member\'s shape', function () {
    $pools = Mockery::mock(PoolQueryRepository::class);
    $query = Mockery::mock(QueryService::class);

    $service = new PoolService($pools, $query);

    $pools->shouldReceive('findByName')
        ->with('Everyone')
        ->andReturn(new Pool('email', ['michal@makimo.pl', 'alice@makimo.pl']));

    // One member resolves to a single name, the other to several.
    $query->shouldReceive('whatIsTheNameOf')
        ->with('michal@makimo.pl', 'email', 'jira')
        ->andReturn('jira1');
    $query->shouldReceive('whatIsTheNameOf')
        ->with('alice@makimo.pl', 'email', 'jira')
        ->andReturn(['jira2', 'jira3']);

    // Index-aligned with the members, mirroring QueryService::whatAreTheNamesOf.
    expect($service->whatAreTheNamesOf('Everyone', 'jira'))
        ->toEqual(['jira1', ['jira2', 'jira3']]);
});


gest('edge', 'A member that does not resolve keeps its place as null', function () {
    $pools = Mockery::mock(PoolQueryRepository::class);
    $query = Mockery::mock(QueryService::class);

    $service = new PoolService($pools, $query);

    $pools->shouldReceive('findByName')
        ->with('Everyone')
        ->andReturn(new Pool('email', ['michal@makimo.pl', 'ghost@makimo.pl']));

    $query->shouldReceive('whatIsTheNameOf')
        ->with('michal@makimo.pl', 'email', 'jira')
        ->andReturn('jira1');
    $query->shouldReceive('whatIsTheNameOf')
        ->with('ghost@makimo.pl', 'email', 'jira')
        ->andReturn(null);

    // null is kept in place so callers can map results and return 207,
    // exactly like the batch query endpoint.
    expect($service->whatAreTheNamesOf('Everyone', 'jira'))
        ->toEqual(['jira1', null]);
});


gest('usage', 'Querying a pool for its own field returns the pool\'s names unchanged', function () {
    $pools = Mockery::mock(PoolQueryRepository::class);
    $query = Mockery::mock(QueryService::class);

    $service = new PoolService($pools, $query);

    $pools->shouldReceive('findByName')
        ->with('Everyone')
        ->andReturn(new Pool('email', ['michal@makimo.pl', 'alice@makimo.pl']));

    // Asking for the pool's own field is a shortcut; no resolution happens.
    $query->shouldNotReceive('whatIsTheNameOf');

    expect($service->whatAreTheNamesOf('Everyone', 'email'))
        ->toEqual(['michal@makimo.pl', 'alice@makimo.pl']);
});


gest('edge', 'An unknown (empty) pool yields an empty list from both methods', function () {
    $pools = Mockery::mock(PoolQueryRepository::class);
    $query = Mockery::mock(QueryService::class);

    $service = new PoolService($pools, $query);

    $pools->shouldReceive('findByName')
        ->with('Nobody')
        ->andReturn(new Pool('', []));

    $query->shouldNotReceive('whatIsTheNameOf');

    expect($service->whatAreTheNamesOf('Nobody', 'jira'))->toEqual([]);
    expect($service->whoseNamesAreThere('Nobody', 'jira'))->toEqual([]);
});


// --- whoseNamesAreThere: whatAreTheNamesOf flattened, nulls dropped ---

gest('usage', 'whoseNamesAreThere flattens the per-member responses into one list', function () {
    $pools = Mockery::mock(PoolQueryRepository::class);
    $query = Mockery::mock(QueryService::class);

    $service = new PoolService($pools, $query);

    $pools->shouldReceive('findByName')
        ->with('Everyone')
        ->andReturn(new Pool('email', ['michal@makimo.pl', 'alice@makimo.pl']));

    $query->shouldReceive('whatIsTheNameOf')
        ->with('michal@makimo.pl', 'email', 'jira')
        ->andReturn('jira1');
    $query->shouldReceive('whatIsTheNameOf')
        ->with('alice@makimo.pl', 'email', 'jira')
        ->andReturn(['jira2', 'jira3']);

    expect($service->whoseNamesAreThere('Everyone', 'jira'))
        ->toEqual(['jira1', 'jira2', 'jira3']);
});


gest('edge', 'whoseNamesAreThere keeps each name once, even when several members share it', function () {
    $pools = Mockery::mock(PoolQueryRepository::class);
    $query = Mockery::mock(QueryService::class);

    $service = new PoolService($pools, $query);

    $pools->shouldReceive('findByName')
        ->with('Everyone')
        ->andReturn(new Pool('email', ['michal@makimo.pl', 'alice@makimo.pl']));

    // Both members resolve (partly) to the same name.
    $query->shouldReceive('whatIsTheNameOf')
        ->with('michal@makimo.pl', 'email', 'jira')
        ->andReturn(['shared', 'jira1']);
    $query->shouldReceive('whatIsTheNameOf')
        ->with('alice@makimo.pl', 'email', 'jira')
        ->andReturn(['shared', 'jira2']);

    // Deduplicated, in first-occurrence order.
    expect($service->whoseNamesAreThere('Everyone', 'jira'))
        ->toEqual(['shared', 'jira1', 'jira2']);
});


gest('edge', 'whoseNamesAreThere drops members that do not resolve', function () {
    $pools = Mockery::mock(PoolQueryRepository::class);
    $query = Mockery::mock(QueryService::class);

    $service = new PoolService($pools, $query);

    $pools->shouldReceive('findByName')
        ->with('Everyone')
        ->andReturn(new Pool('email', ['michal@makimo.pl', 'ghost@makimo.pl']));

    $query->shouldReceive('whatIsTheNameOf')
        ->with('michal@makimo.pl', 'email', 'jira')
        ->andReturn('jira1');
    $query->shouldReceive('whatIsTheNameOf')
        ->with('ghost@makimo.pl', 'email', 'jira')
        ->andReturn(null);

    expect($service->whoseNamesAreThere('Everyone', 'jira'))
        ->toEqual(['jira1']);
});
