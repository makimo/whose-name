<?php

use Domain\WhoseName\Identity;
use Infrastructure\WhoseName\YamlFileRepository;

use Illuminate\Support\Facades\Cache;


beforeEach(function() {
    $this->file = __DIR__ . '/../whosename.yml';
});


test('Given a service name (e.g. GMail) and a username, a matching Identity can be found', function () {
    $repo = new YamlFileRepository($this->file);

    $value = $repo->findByServiceAndUsername('slack', 'U123456');

    expect($value)
        ->toBeInstanceOf(Identity::class)
        ->username('slack')->toEqual('U123456')
        ->username('jira')->toEqual('test@example.org');
});


test('If there\'s no matching service/username, an empty Identity is returned', function () {
    $repo = new YamlFileRepository($this->file);

    $value = $repo->findByServiceAndUsername('', '');

    expect($value)->toBeInstanceOf(Identity::class);
});


test('Loaded Yaml file persists in the cache', function () {
    Cache::flush();

    $repo = new YamlFileRepository($this->file);
    $loadedFromFile = $repo->load();

    $anotherRepo = new YamlFileRepository($this->file);
    $loadedForTheSecondTime = $anotherRepo->load();

    expect($loadedFromFile)->toBeTrue();
    expect($loadedForTheSecondTime)->toBeFalse();
});


test('Modifying Yaml file updates the cache', function () {
    Cache::flush();

    $copiedFile = __DIR__ . '/../whosename.ignored.yml';

    copy($this->file, $copiedFile);

    // Query the repository
    $oldRepo = new YamlFileRepository($copiedFile);
    $oldIdentity = $oldRepo->findByServiceAndUsername('slack', 'U123456');

    // Update file and change it's modification time
    $stat = stat($copiedFile);

    $oldContents = file_get_contents($copiedFile);
    $replaced = str_replace('test@example.org', 'changed@example.org', $oldContents);
    file_put_contents($copiedFile, $replaced);

    touch($copiedFile, $stat['mtime'] + 1);
    clearstatcache();

    // Query the repository once more
    $newRepo = new YamlFileRepository($copiedFile);
    $newIdentity = $newRepo->findByServiceAndUsername('slack', 'U123456');

    expect($oldIdentity)
        ->toBeInstanceOf(Identity::class)
        ->username('slack')->toEqual('U123456')
        ->username('jira')->toEqual('test@example.org');
    
    expect($newIdentity)
        ->toBeInstanceOf(Identity::class)
        ->username('slack')->toEqual('U123456')
        ->username('jira')->toEqual('changed@example.org');

});


test('If the file does not exist, a query throws an exception', function () {
    $repo = new YamlFileRepository(__DIR__ . '/itdoesnotexist.yml');

    $repo->findByServiceAndUsername('slack', 'U123456');

})->throws(Exception::class);
