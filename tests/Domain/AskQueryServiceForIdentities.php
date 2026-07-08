<?php

use Domain\WhoseName\QueryService;
use Domain\WhoseName\IdentityQueryRepository;
use Domain\WhoseName\Identity;


gest('usage', 'Querying an existing user identity for a known service (e.g. GMail) returns username on that service', function () {
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


gest('usage', 'Querying a service that holds several names returns all of them as a list', function () {
    $identity = new Identity([
        'slack' => 'U234567',
        'email' => ['other@example.org', 'new@example.org'],
    ]);

    $repo = Mockery::mock(IdentityQueryRepository::class);

    $service = new QueryService($repo);

    $repo->shouldReceive('findByServiceAndUsername')
        ->with('slack', 'U234567')
        ->andReturn($identity);

    $emails = $service->whatIsTheNameOf('U234567', 'slack', 'email');
    expect($emails)->toBeArray()->toEqual(['other@example.org', 'new@example.org']);
});


gest('usage', 'An identity queried by one of its many names resolves other services', function () {
    $identity = new Identity([
        'slack' => 'U234567',
        'email' => ['other@example.org', 'new@example.org'],
    ]);

    $repo = Mockery::mock(IdentityQueryRepository::class);

    $service = new QueryService($repo);

    // The repository is what knows every name maps to this identity;
    // the service simply asks by whichever name it was given.
    $repo->shouldReceive('findByServiceAndUsername')
        ->with('email', 'new@example.org')
        ->andReturn($identity);

    $slackUsername = $service->whatIsTheNameOf('new@example.org', 'email', 'slack');
    expect($slackUsername)->toBeString()->toEqual('U234567');
});


gest('edge', 'Querying an Identity for a not known service returns a null value', function () {
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


gest('edge', 'Querying a non-existent Identity for any service returns a null value', function () {
    $identity = new Identity([]);
    
    $repo = Mockery::mock(IdentityQueryRepository::class);
    
    $service = new QueryService($repo);

    $repo->shouldReceive('findByServiceAndUsername')
        ->with('jira', 'test@makimo.pl')
        ->andReturn($identity);

    $result = $service->whatIsTheNameOf('test@makimo.pl', 'jira', 'slack');
    expect($result)->toBeNull();
});


gest('edge', 'Querying an existing Identity for the same known service returns the same username as provided', function () {
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


gest('usage', 'Querying multiple identities at once returns a username for each, in order', function () {
    $identity = new Identity([
        'jira' => 'test@makimo.pl',
        'slack' => 'U12345',
    ]);

    $repo = Mockery::mock(IdentityQueryRepository::class);

    $service = new QueryService($repo);

    $repo->shouldReceive('findByServiceAndUsername')
        ->with('jira', 'test@makimo.pl')
        ->andReturn($identity);
    $repo->shouldReceive('findByServiceAndUsername')
        ->with('slack', 'U12345')
        ->andReturn($identity);

    $results = $service->whatAreTheNamesOf([
        ['username' => 'test@makimo.pl', 'service' => 'jira',  'askedService' => 'slack'],
        ['username' => 'U12345',         'service' => 'slack', 'askedService' => 'jira'],
    ]);

    expect($results)->toEqual(['U12345', 'test@makimo.pl']);
});


gest('edge', 'Batch querying preserves order and returns null for unknown identities', function () {
    $repo = Mockery::mock(IdentityQueryRepository::class);

    $service = new QueryService($repo);

    $repo->shouldReceive('findByServiceAndUsername')
        ->with('jira', 'test@makimo.pl')
        ->andReturn(new Identity(['jira' => 'test@makimo.pl', 'slack' => 'U12345']));
    $repo->shouldReceive('findByServiceAndUsername')
        ->with('unknown', 'unknown')
        ->andReturn(new Identity([]));

    $results = $service->whatAreTheNamesOf([
        ['username' => 'test@makimo.pl', 'service' => 'jira',    'askedService' => 'slack'],
        ['username' => 'unknown',        'service' => 'unknown', 'askedService' => 'unknown'],
    ]);

    expect($results)->toEqual(['U12345', null]);
});


gest('edge', 'Batch querying an empty list returns an empty result set', function () {
    $repo = Mockery::mock(IdentityQueryRepository::class);

    $service = new QueryService($repo);

    expect($service->whatAreTheNamesOf([]))->toEqual([]);
});
