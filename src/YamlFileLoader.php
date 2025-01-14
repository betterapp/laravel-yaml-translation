<?php

namespace betterapp\LaravelYamlTranslation;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Symfony\Component\Yaml\Parser;

class YamlFileLoader extends FileLoader
{
    protected function getAllowedFileExtensions()
    {
        return ['php', 'yml', 'yaml'];
    }
    
    /**
     * @param $paths
     * @param $locale
     * @param $group
     * @return mixed
     */
    protected function loadPaths($paths, $locale, $group)
    {
        return collect($paths)
            ->reduce(function ($output, $path) use ($locale, $group) {
                
                foreach ($this->getAllowedFileExtensions() as $extension) {
                    if ($this->files->exists($full = "{$path}/{$locale}/{$group}." . $extension)) {
                        $output = array_replace_recursive($output, $this->parseContent($extension, $full));
                    }
                }
                
                return $output;
            }, []);
    }
    
    /**
     * @param array $lines
     * @param $locale
     * @param $group
     * @param $namespace
     * @return array
     */
    protected function loadNamespaceOverrides(array $lines, $locale, $group, $namespace)
    {
        foreach ($this->getAllowedFileExtensions() as $extension) {
            $file = "{$this->path}/packages/{$locale}/{$namespace}/{$group}." . $extension;
            
            if ($this->files->exists($file)) {
                return $this->replaceLines($extension, $lines, $file);
            }
        }
        
        return $lines;
    }
    
    protected function replaceLines($format, $lines, $file)
    {
        return array_replace_recursive($lines, $this->parseContent($format, $file));
    }
    
    protected function parseContent($format, $file)
    {
        $content = null;
        
        switch ($format) {
            case 'php':
                $content = $this->files->getRequire($file);
                break;
            case 'yml':
            case 'yaml':
                $content = $this->parseYamlOrLoadFromCache($file);
                break;
        }
        
        return $content;
    }
    
    protected function parseYamlOrLoadFromCache($file)
    {
        $cachefile = storage_path() . '/framework/cache/yaml.lang.cache.' . md5($file) . '.php';
        
        if (@filemtime($cachefile) < filemtime($file)) {
            $parser = new Parser();
            $content = $parser->parse(file_get_contents($file));
            file_put_contents($cachefile, "<?php" . PHP_EOL . PHP_EOL . "return " . var_export($content, true) . ";");
            return $content;
        }
        
        return $this->files->getRequire($cachefile);
    }
}
