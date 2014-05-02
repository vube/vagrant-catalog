<?php
/**
 * @copyright 2014 Vubeology, LLC.
 * @author Ross Perkins <ross@vubeology.com>
 */

namespace Vube\VagrantCatalog;

use Vube\VagrantCatalog\Exception\ConfigException;
use Vube\VagrantCatalog\Exception\HttpException;


/**
 * Catalog class
 *
 * @author Ross Perkins <ross@vubeology.com>
 */
class Catalog {

	const CONFIG_PHP = 'config.php';

	private $baseDir;
	private $scriptRelativeDir = '';
	private $pathInfo = '';
	private $configFilename;

	private $config;
	private $requiredConfigItems;
	private $triggerErrors = true;

	public function __construct($baseDir)
	{
		$this->baseDir = $baseDir;
		$this->scriptRelativeDir = dirname($_SERVER['SCRIPT_NAME']);

		// In the docroot we don't want a leading slash.
		// In sub-dirs there won't be a trailing slash, but the docroot
		// is a bit special in that it DOES have a trailing slash.
		if($this->scriptRelativeDir === '/')
			$this->scriptRelativeDir = '';

		$this->requiredConfigItems = array(
			'metadata-root',
			'download-url-prefix',
		);

		$this->pathInfo = $this->computePathInfo();
	}

	public function ignoreErrors()
	{
		$this->triggerErrors = false;
	}

	public function triggerError($msg, $type=E_USER_NOTICE)
	{
		if($this->triggerErrors)
			trigger_error($msg, $type);
	}

	public function getConfigFilename()
	{
		return $this->configFilename;
	}

	public function computePathInfo()
	{
		// Find out the path-only part of the requestUri (e.g. remove ?args, if any)
		$path = preg_replace("/\?.*/", "", $_SERVER['REQUEST_URI']);

		// Trim off the leading relative script dir, if any
		$len = strlen($this->scriptRelativeDir);
		if($len)
		{
			// There is a relative script dir, trim it

			if(substr($path, 0, $len) === $this->scriptRelativeDir)
			{
				if($len < strlen($path))
					$path = substr($path, $len);
				else // there is no additional path info
					$path = "";
			}
			else throw new InvalidInputException("Conflicting values for SCRIPT_NAME and REQUEST_URI");
		}
		else
		{
			// There is no relative script dir, we're in the docroot
			// The entire path (if any) is path info

			// If this is a request for the docroot itself, there is NO path info
			if($path === '/')
				$path = "";
		}

		return $path;
	}

	public function computeConfigFilename()
	{
		$path = $this->baseDir . DIRECTORY_SEPARATOR . self::CONFIG_PHP;
		return $path;
	}

	public function loadConfigFile($file)
	{
		if(file_exists($file) && ! is_dir($file))
		{
			$this->config = require $file;

			$this->configFilename = $file;
			return true;
		}
		return false;
	}

	public function loadConfig()
	{
		$file = $this->computeConfigFilename();

		if(! $this->loadConfigFile($file))
		{
			$fileDist = "$file.dist";

			if(! $this->loadConfigFile($fileDist))
				throw new ConfigException("Missing configuration file ($file) and no default exists (tried $fileDist)");

			$this->triggerError("Warning: Missing $file, using default $fileDist instead", E_USER_NOTICE);
		}
	}

	public function checkConfig()
	{
		// Check to make sure all the required items exist
		foreach($this->requiredConfigItems as $name)
		{
			if(! isset($this->config[$name]))
				throw new ConfigException("Undefined config[$name]");
		}

		// metadata-root may be a relative directory, make it absolute if needed
		if(substr($this->config['metadata-root'],0,1) !== '/')
			$this->config['metadata-root'] = $this->baseDir . DIRECTORY_SEPARATOR . $this->config['metadata-root'];

		// If metadata-root directory doesn't exist, throw exception
		if(! is_dir($this->config['metadata-root']))
			throw new ConfigException("No such config[metadata-root] directory: ".$this->config['metadata-root']);
	}

	public function getConfig()
	{
		return $this->config;
	}

	public function init()
	{
		$this->loadConfig();
		$this->checkConfig();
	}

	public function parseMetadataTemplate($template)
	{
		$parse = preg_replace("/\{\{download_url_prefix\}\}/", $this->config['download-url-prefix'], $template);

		return $parse;
	}

	public function exec()
	{
		$metadataPath = $this->pathInfo . DIRECTORY_SEPARATOR . 'metadata.json';

		$metadataFile = $this->config['metadata-root'] . $metadataPath;

		if(! file_exists($metadataFile))
			throw new HttpException("File not found: $metadataPath", 404);

		$template = @file_get_contents($metadataFile);
		$metadata = $this->parseMetadataTemplate($template);

		$result = array(
			'headers' => array(
				'Content-Type: application/json'
			),
			'content' => $metadata,
		);
		return $result;
	}
}