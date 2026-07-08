<?php namespace Infrastructure\WhoseName;

use Domain\WhoseName\PoolQueryRepository;
use Domain\WhoseName\Pool;

use Cache;

use Symfony\Component\Yaml\Yaml;

class PoolYamlFileRepository implements PoolQueryRepository {
    protected
        $prefix,
        $sourceFilePath,
        $poolMap = null,
        $poolLookupMap = null;

    protected static function loadYamlFile(string $path): array {
        return Yaml::parse(file_get_contents($path));
    }

    protected static function transformPoolListToIndex(array $list): array {
        $mapping = [];

        foreach($list as $index => $pool) {
            $mapping[$pool['name']] = $index;
        }

        return $mapping;
    }

    public function __construct(?string $path = null) {
        if(!$path) {
            $path = config('whosename.pools_file');
        }

        if(str_starts_with($path, '/')) {
            $this->sourceFilePath = $path;
        } else {
            $this->sourceFilePath = base_path($path);
        }

        $this->prefix = hash("crc32b", $this->sourceFilePath);
    }

    protected function load(): void {
        $modificationTime = filemtime($this->sourceFilePath);

        if(!$modificationTime) {
            throw new \RuntimeException("Yaml file not found!");
        }

        $cachedModificationTime = Cache::get($this->cacheKey('timestamp'), -1);

        $sourceFileNeedsReload =
            $modificationTime > $cachedModificationTime
            || !Cache::has($this->cacheKey('pools'));

        if($sourceFileNeedsReload) {
            $this->poolMap = static::loadYamlFile($this->sourceFilePath);
            $this->poolLookupMap = static::transformPoolListToIndex(
                $this->poolMap
            );

            Cache::put($this->cacheKey('pools'), [
                $this->poolMap,
                $this->poolLookupMap,
            ]);

            Cache::put($this->cacheKey('timestamp'), $modificationTime);

            return;
        }

        list(
            $this->poolMap,
            $this->poolLookupMap
        ) = Cache::get($this->cacheKey('pools'));
    }

    protected function cacheKey($property) {
        return "whosename.pools.$this->prefix.$property";
    }

    public function findByName(string $name): Pool {
        if(!$this->poolMap) {
            $this->load();
        }

        if(!isset($this->poolLookupMap[$name])) {
            return new Pool('', []);
        }

        $pool = $this->poolMap[$this->poolLookupMap[$name]];

        return new Pool($pool['field'], $pool['names']);
    }

}
