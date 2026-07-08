<?php

use Domain\WhoseName\Pool;
use Domain\WhoseName\PoolService;
use Domain\WhoseName\QueryService;
use Infrastructure\WhoseName\PoolYamlFileRepository;
use Infrastructure\WhoseName\YamlFileRepository;

use Illuminate\Support\Facades\Cache;


beforeEach(function() {
    $this->file = __DIR__ . '/../pools.yml';
});


gest('usage', 'Given a name, a matching Pool can be found', function () {
    $repo = new PoolYamlFileRepository($this->file);

    $value = $repo->findByName('Everyone');

    expect($value)->toBeInstanceOf(Pool::class);
    expect($value->getField())->toEqual('email');
    expect($value->getNames())->toEqual(['single@example.org', 'other@example.org']);
});


gest('edge', 'If there\'s no matching name, an empty Pool is returned', function () {
    $repo = new PoolYamlFileRepository($this->file);

    $value = $repo->findByName('');

    expect($value)->toBeInstanceOf(Pool::class);
    expect($value->getField())->toEqual('');
    expect($value->getNames())->toEqual([]);
});


gest('behavior', 'Loaded Yaml file persists in the cache', function () {
    Cache::flush();

    $copiedFile = __DIR__ . '/../pools.ignored.yml';

    // Arrange: Copy the file and set its modified and access time in the past
    copy($this->file, $copiedFile);

    $lastSecond = time() - 1;

    touch($copiedFile, $lastSecond, $lastSecond);

    clearstatcache();

    // Assert the access time and modification time was set
    // It hypothetically could fail on some strange file systems.
    expect(filemtime($copiedFile))
        ->toEqual(fileatime($copiedFile))
        ->toEqual($lastSecond);

    // Act: With cache emptied, first access will read the file
    $repo = new PoolYamlFileRepository($copiedFile);
    $repo->findByName('Everyone');

    clearstatcache();

    // Assert: the file was accessed so the times don't match anymore
    expect(filemtime($copiedFile))
        ->toBeLessThan(fileatime($copiedFile));

    // Arrange: set times on the file in the past
    touch($copiedFile, $lastSecond, $lastSecond);

    // Act: With cache set by previous repo call
    // second access doesn't read the file
    $anotherRepo = new PoolYamlFileRepository($copiedFile);
    $anotherRepo->findByName('Everyone');

    clearstatcache();

    // Assert: The atime did not change, because
    // the file was not read the second time
    expect(filemtime($copiedFile))
        ->toEqual(fileatime($copiedFile));
})->skip(function() {
    $copiedFile = __DIR__ . '/../pools.ignored.yml';

    copy($this->file, $copiedFile);

    return !hasFileAtimeChangedOnRead($copiedFile);
}, 'Skipped; filesystem does not support updating access time on read.');


gest('behavior', 'Modifying Yaml file updates the cache', function () {
    Cache::flush();

    $copiedFile = __DIR__ . '/../pools.ignored.yml';

    copy($this->file, $copiedFile);

    // Query the repository
    $oldRepo = new PoolYamlFileRepository($copiedFile);
    $oldPool = $oldRepo->findByName('Everyone');

    // Update file and change it's modification time
    $stat = stat($copiedFile);

    $oldContents = file_get_contents($copiedFile);
    $replaced = str_replace('single@example.org', 'changed@example.org', $oldContents);
    file_put_contents($copiedFile, $replaced);

    touch($copiedFile, $stat['mtime'] + 1);
    clearstatcache();

    // Query the repository once more
    $newRepo = new PoolYamlFileRepository($copiedFile);
    $newPool = $newRepo->findByName('Everyone');

    expect($oldPool)->toBeInstanceOf(Pool::class);
    expect($oldPool->getNames())->toEqual(['single@example.org', 'other@example.org']);

    expect($newPool)->toBeInstanceOf(Pool::class);
    expect($newPool->getNames())->toEqual(['changed@example.org', 'other@example.org']);
});


gest('edge', 'If the file does not exist, a query throws an exception', function () {
    $repo = new PoolYamlFileRepository(__DIR__ . '/itdoesnotexist.yml');

    $repo->findByName('Everyone');

})->throws(Exception::class);


gest('usage', 'A pool resolves real identities into per-member responses, flattened on demand', function () {
    $query = new QueryService(new YamlFileRepository(__DIR__ . '/../whosename.yml'));
    $pools = new PoolYamlFileRepository($this->file);

    $service = new PoolService($pools, $query);

    // Slackers = [U123456, U234567] on slack:
    //   U123456 -> email single@example.org (one name)
    //   U234567 -> email [other@example.org, new@example.org] (several)
    // whatAreTheNamesOf keeps one response per member, shape preserved...
    expect($service->whatAreTheNamesOf('Slackers', 'email'))->toEqual([
        'single@example.org',
        ['other@example.org', 'new@example.org'],
    ]);

    // ...whoseNamesAreThere flattens them into a single list.
    expect($service->whoseNamesAreThere('Slackers', 'email'))->toEqual([
        'single@example.org',
        'other@example.org',
        'new@example.org',
    ]);

    // Asking for the pool's own field short-circuits to its names verbatim.
    expect($service->whatAreTheNamesOf('Slackers', 'slack'))
        ->toEqual(['U123456', 'U234567']);
    expect($service->whoseNamesAreThere('Slackers', 'slack'))
        ->toEqual(['U123456', 'U234567']);
});
