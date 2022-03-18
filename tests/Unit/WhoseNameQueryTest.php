<?php

use Domain\WhoseName\QueryService;
use Domain\WhoseName\IdentityQueryRepository;
use Domain\WhoseName\Identity;


test('given an Identity with two or more usernames, when asked what is the username in the other service, a correct username is returned', function () {
    $identity = new Identity([
        'jira' => 'test@makimo.pl',
        'slack' => 'U12345',
    ]);

    $repo = Mockery::mock(IdentityQueryRepository::class);

    $service = new QueryService($repo);

    $repo->shouldReceive('findByServiceAndUsername')
        ->with('jira', 'test@makimo.pl')
        ->andReturn($identity);

    $slackUsername = $service->whatIsTheNameOf('test@makimo.pl', 'jira', 'slack');
    expect($slackUsername)->toBeString()->toEqual('U12345');

    $repo->shouldReceive('findByServiceAndUsername')
        ->with('slack', 'U12345')
        ->andReturn($identity);

    $jiraUsername = $service->whatIsTheNameOf('U12345', 'slack', 'jira');
    expect($jiraUsername)->toBeString()->toEqual('test@makimo.pl');

});


test('given no Identity, QueryService returns null', function () {
    $repo = Mockery::mock(IdentityQueryRepository::class);

    $repo->shouldReceive('findByServiceAndUsername')
        ->with('jira', 'test@makimo.pl')
        ->andReturn(new Identity([]));
    
    $service = new QueryService($repo);
    $result = $service->whatIsTheNameOf('test@makimo.pl', 'jira', 'slack');
    expect($result)->toBeNull();
});


test('given an Identity with one username, asking about it confirms that it exist', function () {
    $identity = new Identity([
        'jira' => 'test@makimo.pl',
    ]);

    $repo = Mockery::mock(IdentityQueryRepository::class);

    $repo->shouldReceive('findByServiceAndUsername')
        ->with('jira', 'test@makimo.pl')
        ->andReturn($identity);
    
    $service = new QueryService($repo);

    $jiraUsername = $service->whatIsTheNameOf('test@makimo.pl', 'jira', 'jira');
    expect($jiraUsername)->toBeString()->toEqual('test@makimo.pl');
});


test('given an Identity, asking about nonexisting service returns null', function () {
    $identity = new Identity([
        'jira' => 'test@makimo.pl',
    ]);

    $repo = Mockery::mock(IdentityQueryRepository::class);

    $repo->shouldReceive('findByServiceAndUsername')
        ->with('jira', 'test@makimo.pl')
        ->andReturn($identity);
    
    $service = new QueryService($repo);

    $result = $service->whatIsTheNameOf('test@makimo.pl', 'jira', 'slack');
    expect($result)->toBeNull();
});