<?php namespace Infrastructure\WhoseName;

use Domain\WhoseName\IdentityQueryRepository;
use Domain\WhoseName\Identity;

use Cache;

use Symfony\Component\Yaml\Yaml;

class YamlFileRepository implements IdentityQueryRepository {
    protected
        $prefix,
        $sourceFilePath,
        $identityMap = null,
        $identityLookupMap = null;
    
    protected static function loadYamlFile(string $path): array {
        return Yaml::parse(file_get_contents($path));
    }

    protected static function transformIdentityListToIndex(array $list): array {
        $mapping = [];

        foreach($list as $index => $identity) {
            foreach($identity as $service => $username) {
                if(!isset($mapping[$service])) {
                    $mapping[$service] = [$username => $index];
                } else {
                    $mapping[$service][$username] = $index;
                }
            }
        }

        return $mapping;
    }

    public function __construct(?string $path = null) {
        if(!$path) {
            $path = config('whosename.yaml_file');
        }

        if(str_starts_with($path, '/')) {
            $this->sourceFilePath = $path;
        } else {
            $this->sourceFilePath = base_path($path);
        }

        $this->prefix = hash("crc32b", $this->sourceFilePath);
    }

    protected function load(): void {
        $stat = stat($this->sourceFilePath);

        if(!$stat) {
            throw new \RuntimeException("Yaml file not found!");
        }

        $timestamp = Cache::get($this->cacheKey('timestamp'), -1);

        if($stat['mtime'] > $timestamp || !Cache::has($this->cacheKey('identities'))) {
            $this->identityMap = static::loadYamlFile($this->sourceFilePath);
            $this->identityLookupMap = static::transformIdentityListToIndex($this->identityMap);

            Cache::put($this->cacheKey('identities'), [
                $this->identityMap,
                $this->identityLookupMap,
            ]);

            Cache::put($this->cacheKey('timestamp'), $stat['mtime']);

            return;
        }

        list(
            $this->identityMap,
            $this->identityLookupMap
        ) = Cache::get($this->cacheKey('identities'));
    }

    protected function cacheKey($property) {
        return "whosename.$this->prefix.$property";
    }

    public function findByServiceAndUsername(string $service, string $username): Identity {
        if(!$this->identityMap) {
            $this->load();
        }

        if(!isset($this->identityLookupMap[$service]) || !isset($this->identityLookupMap[$service][$username])) {
            return new Identity([]);
        }

        return new Identity($this->identityMap[
            $this->identityLookupMap[$service][$username]
        ]);
    }

}