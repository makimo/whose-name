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

    $copiedFile = __DIR__ . '/../whosename.ignored.yml';

    // Arrange: Copy the file and set its modified and access time in the past
    copy($this->file, $copiedFile);

    $lastSecond = time() - 1;

    touch($copiedFile, $lastSecond, $lastSecond);

    // Assert the access time and modification time was set
    // It hypothetically could fail on some strange file systems.
    expect(filemtime($copiedFile))
        ->toEqual(fileatime($copiedFile))
        ->toEqual($lastSecond);

    // Act: With cache emptied, first access will read the file
    $repo = new YamlFileRepository($copiedFile);
    $repo->findByServiceAndUsername('slack', 'U123456');

    clearstatcache();

    // Assert: the file was accessed so the times don't match anymore
    expect(filemtime($copiedFile))
        ->toBeLessThan(fileatime($copiedFile));

    // Arrange: set times on the file in the past
    touch($copiedFile, $lastSecond, $lastSecond);

    // Act: With cache set by previous repo call
    // second access doesn't read the file
    $anotherRepo = new YamlFileRepository($copiedFile);
    $anotherRepo->findByServiceAndUsername('slack', 'U123456');

    clearstatcache();

    // Assert: The atime did not change, because
    // the file was not read the second time
    expect(filemtime($copiedFile))
        ->toEqual(fileatime($copiedFile));
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
