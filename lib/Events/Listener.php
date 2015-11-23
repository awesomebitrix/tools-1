<?php

namespace Maximaster\Tools\Events;

use Maximaster\Tools\Psr4Autoloader;

class Listener extends Psr4Autoloader
{
    protected $prefixes = array();

    /**
     * Инициирует регистрацию всех событий
     */
    public function register()
    {
        $collection = array();
        foreach ($this->prefixes as $namespace => $directoryList)
        {
            foreach ($directoryList as $directory)
                $collection += $this->collect($namespace, $directory);
        }
        foreach ($collection as $handler)
        {
            $sort = $handler['sort'] ? $handler['sort'] : 100;
            $this->listen($handler['moduleName'], $handler['eventType'], $handler['callback'], $sort);
        }
    }

    /**
     * Регистрирует событие с заданными параметрами
     * @param     $moduleId
     * @param     $eventType
     * @param     $callback
     * @param int $sort
     * @return int
     */
    private function listen($moduleId, $eventType, $callback, $sort = 100)
    {
        return AddEventHandler($moduleId, $eventType, $callback, $sort);
    }

    /**
     * На основании пространства имен собирает все обработчики в массив
     * @param $namespace
     * @param $handlersDirectory
     * @return array
     */
    private function collect($namespace, $handlersDirectory)
    {
        $ns = $namespace;
        $collection = array();
        if (!is_dir($handlersDirectory)) return $collection;

        $dirIterator   = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($handlersDirectory));
        $regexIterator = new \RegexIterator($dirIterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

        foreach ($regexIterator as $file)
        {
            $file = current($file);
            if (!$this->requireFile($file)) continue;

            $relativeClass = str_replace(array($handlersDirectory, '.php'), '', $file);
            if (strpos($relativeClass, '/') !== false)
            {
                list($moduleName, $eventType) = explode('/', $relativeClass);
            }
            else
            {
                //Для хайлоадблоков, динамические классы сущностей которых не имеют связи с модулем
                $moduleName = null;
                $eventType = $relativeClass;
            }

            if (!$eventType || $eventType == 'Base') continue;

            $className = $ns . str_replace('/', '\\', $relativeClass);
            $class = new \ReflectionClass($className);
            foreach ($class->getMethods() as $method)
            {
                if ($method->class == $class->getName())
                    $collection[] = array(
                        'moduleName' => strtolower($moduleName),
                        'eventType' => $eventType,
                        'callback' => array($class->getName(), $method->name)
                    );
            }
        }
        
        return $collection;
    }
}