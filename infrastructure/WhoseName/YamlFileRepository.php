<?php namespace Infrastructure\WhoseName;

use Domain\WhoseName\IdentityQueryRepository;
use Domain\WhoseName\Identity;

use Cache;

use Symfony\Component\Yaml\Yaml;

class YamlFileRepository implements IdentityQueryRepository {
    protected
        $sourceFilePath,
        $identityMap = null,
        $identityLookupMap = null;
    
    public function __construct() {
        $path = config('whosename.yaml_file');

        if(str_starts_with($path, '/')) {
            $this->sourceFilePath = $path;
        } else {
            $this->sourceFilePath = base_path($path);
        }
    }

    protected function reloadAndCache() {
        $identities = Yaml::parse(file_get_contents($this->sourceFilePath));
        
        $mapping = [];

        foreach($identities as $index => $identity) {
            foreach($identity as $service => $username) {
                if(!isset($mapping[$service])) {
                    $mapping[$service] = [$username => $index];
                } else {
                    $mapping[$service][$username] = $index;
                }
            }
        }

        $data = [$identities, $mapping];

        Cache::put('whosename.identities', $data);

        return $data;

    }

    protected function load() {
        $stat = stat($this->sourceFilePath);

        if(!$stat) {
            throw new \Exception("Yaml file not found!");
        }

        $timestamp = Cache::get('whosename.yaml_timestamp', -1);

        if($stat['mtime'] > $timestamp || !Cache::has('whosename.identities')) {
            $identities = $this->reloadAndCache();

            Cache::put('whosename.yaml_timestamp', $stat['mtime']);
        } else {
            $identities = Cache::get('whosename.identities');
        }

        list(
            $this->identityMap,
            $this->identityLookupMap
        ) = $identities;
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