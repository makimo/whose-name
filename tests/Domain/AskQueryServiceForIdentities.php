<?php

use Domain\WhoseName\QueryService;
use Domain\WhoseName\IdentityQueryRepository;
use Domain\WhoseName\Identity;


test('Querying an existing user identity for a known service (e.g. GMail) returns username on that service', function () {
    $identity = new Identity([
        'jira' => 'test@makimo.pl',
        'slack' => 'U12345',
    ]);

    $repo = Mockery::mock(IdentityQueryRepository::class);

    $service = new QueryService($repo);

    // Ask in one direction...
    $repo->shouldReceive('findByServiceAndUsername')
        ->with('jira', 'test@makimo.pl')
        ->andReturn($identity);

    $slackUsername = $service->whatIsTheNameOf('test@makimo.pl', 'jira', 'slack');
    expect($slackUsername)->toBeString()->toEqual('U12345');

    // Or in the other one...
    $repo->shouldReceive('findByServiceAndUsername')
        ->with('slack', 'U12345')
        ->andReturn($identity);

    $jiraUsername = $service->whatIsTheNameOf('U12345', 'slack', 'jira');
    expect($jiraUsername)->toBeString()->toEqual('test@makimo.pl');

});


test('Querying an Identity for a not known service returns a null value', function () {
    $identity = new Identity([
        'jira' => 'test@makimo.pl',
    ]);

    $repo = Mockery::mock(IdentityQueryRepository::class);

    $service = new QueryService($repo);

    $repo->shouldReceive('findByServiceAndUsername')
        ->with('jira', 'test@makimo.pl')
        ->andReturn($identity);
    
    $result = $service->whatIsTheNameOf('test@makimo.pl', 'jira', 'slack');
    expect($result)->toBeNull();
});


test('Querying a non-existent Identity for any service returns a null value', function () {
    $identity = new Identity([]);
    
    $repo = Mockery::mock(IdentityQueryRepository::class);
    
    $service = new QueryService($repo);

    $repo->shouldReceive('findByServiceAndUsername')
        ->with('jira', 'test@makimo.pl')
        ->andReturn($identity);

    $result = $service->whatIsTheNameOf('test@makimo.pl', 'jira', 'slack');
    expect($result)->toBeNull();
});


test('Querying an existing Identity for the same known service returns the same username as provided', function () {
    $identity = new Identity([
        'jira' => 'test@makimo.pl',
    ]);

    $repo = Mockery::mock(IdentityQueryRepository::class);
    
    $service = new QueryService($repo);

    $repo->shouldReceive('findByServiceAndUsername')
        ->with('jira', 'test@makimo.pl')
        ->andReturn($identity);

    $jiraUsername = $service->whatIsTheNameOf('test@makimo.pl', 'jira', 'jira');
    expect($jiraUsername)->toBeString()->toEqual('test@makimo.pl');
});
