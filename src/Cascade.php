<?php
/**
 * This file is part of the Monolog Cascade package.
 *
 * (c) Raphael Antonmattei <rantonmattei@theorchard.com>
 * (c) The Orchard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cascade;

use Monolog\Logger;
use Monolog\Registry;

use Cascade\Config;
use Cascade\Config\ConfigLoader;

/**
 * Module class that manages Monolog Logger object
 * @see Monolog\Logger
 * @see Monolog\Registry
 *
 * @author Raphael Antonmattei <rantonmattei@theorchard.com>
 */
class Cascade
{
    /**
     * Config class that holds options for all registered loggers
     * This is optional, you can set up your loggers programmatically
     * @var Config
     */
    protected static $config = null;
    
    /**
     * Inherit on undefined logger option
     *
     * @var bool
     */
    protected static $inheritOnUndefinedLogger = false;

    /**
     * Getter for run-time option `inheritOnUndefinedLogger`
     */
    public static function shouldInheritOnUndefined()
    {
        return static::$inheritOnUndefinedLogger;
    }

    /**
     * Setter for run-time option `inheritOnUndefinedLogger`
     *
     * @param bool $value
     *
     * @return bool
     */
    public static function setInheritOnUndefined($value)
    {
        return static::$inheritOnUndefinedLogger = $value;
    }

    /**
     * Create a new Logger object and push it to the registry
     * @see Monolog\Logger::__construct
     *
     * @throws \InvalidArgumentException if no name is given
     *
     * @param string $name The logging channel
     * @param Monolog\Handler\HandlerInterface[] $handlers Optional stack of handlers, the first
     * one in the array is called first, etc.
     * @param callable[] $processors Optional array of processors
     * @param Logger $parent Optional logger parent
     *
     * @return Logger Newly created Logger
     */
    public static function createLogger(
        $name,
        array $handlers = array(),
        array $processors = array(),
        Logger $parent = null
    ) {

        if (empty($name)) {
            throw new \InvalidArgumentException('Logger name is required.');
        }

        $logger = new Logger($name, $handlers, $processors, $parent);
        Registry::addLogger($logger);

        return $logger;
    }

    /**
     * Get a Logger instance by name. Creates a new one if a Logger with the
     * provided name does not exist
     *
     * @param  string $name Name of the requested Logger instance
     *
     * @return Logger Requested instance of Logger or new instance
     */
    public static function getLogger($name)
    {
        if (Registry::hasLogger($name)) {
            return Registry::getInstance($name);
        }

        if (static::shouldInheritOnUndefined()) {
            $parent = null;
            $current_parent = $name;

            // Store the pos so we only have to search through the string once
            $last_delimit_pos = strrpos($name, '.');
            while ($last_delimit_pos !== false) {
                $current_parent = substr($name, 0, $last_delimit_pos);
                if (Registry::hasLogger($current_parent)) {
                    $parent = Registry::getInstance($current_parent);
                }
                $last_delimit_pos = strrpos($current_parent, '.');
            }

            if ($parent == null && Registry::hasLogger('default')) {
                $parent = Registry::getInstance('default');
            }
            return self::createLogger($name, array(), array(), $parent);
        }
        return self::createLogger($name);
    }

    /**
     * Alias of getLogger
     * @see getLogger
     *
     * @param  string $name Name of the requested Logger instance
     *
     * @return Logger Requested instance of Logger or new instance
     */
    public static function logger($name)
    {
        return self::getLogger($name);
    }

    /**
     * Return the config options
     *
     * @return array Array with configuration options
     */
    public static function getConfig()
    {
        return self::$config;
    }

    /**
     * Load configuration options from a file or a string
     *
     * @param string $resource Path to config file or string or array
     */
    public static function fileConfig($resource)
    {
        self::$config = new Config($resource, new ConfigLoader());
        self::$config->load();
        self::$config->configure();
    }
}
