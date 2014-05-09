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
	const DEFAULT_CATALOG_URI = 'catalog';

	const ROUTE_INDEX = 'index';
	const ROUTE_CATALOG = 'catalog';
	const ROUTE_DEBUG = 'debug';

	private $route = self::ROUTE_INDEX;
	private $routeList = array();

	private $baseDir;
	private $baseUri;

	private $scriptRelativeDir = '';
	private $pathInfo = '';

	private $configFilename;
	private $config;
	private $requiredConfigItems;

	private $triggerErrors = true;

	public function __construct($baseDir)
	{
		$this->baseDir = $baseDir;

		$this->requiredConfigItems = array(
			'metadata-root',
			'download-url-prefix',
		);

		$this->initServerInfo();
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

	public function initServerInfo()
	{
		$this->scriptRelativeDir = dirname($_SERVER['SCRIPT_NAME']);

		// In the docroot we don't want a leading slash.
		// In sub-dirs there won't be a trailing slash, but the docroot
		// is a bit special in that it DOES have a trailing slash.
		if($this->scriptRelativeDir === '/')
			$this->scriptRelativeDir = '';

		// This part can be kind of confusing unless you know exactly what $_SERVER
		// contains.  Here is an example:
		//
		// HTTP_HOST   = "host.com:8080"
		// SCRIPT_NAME = "/base/index.php"
		//
		// scriptRelativeDir: "/base" (calculated above)
		// baseUri: "http://host.com:8080/base"

		$protocol = 'http' . (empty($_SERVER['HTTPS']) ? '' : 's');
		$httpHostPort = $protocol . '://' . $_SERVER['HTTP_HOST'];
		$this->baseUri = $httpHostPort . $this->scriptRelativeDir;
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

		// Remove any trailing slashes from the path info
		$path = preg_replace("%/+$%", "", $path);

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

	/**
	 * Set a config option (used by tests)
	 *
	 * @param string $n Config key
	 * @param string $v Value of the config
	 */
	public function setConfig($n, $v)
	{
		$this->config[$n] = $v;
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

		// Remove any trailing slashes from the metadata root dir
		$this->config['metadata-root'] = preg_replace("%/+$%", "", $this->config['metadata-root']);

		// If metadata-root directory doesn't exist, throw exception
		if(! is_dir($this->config['metadata-root']))
			throw new ConfigException("No such config[metadata-root] directory: ".$this->config['metadata-root']);

		// If they did not specify a proto:// in front of the download-url-prefix, then it
		// must be relative to the current base uri
		if(! preg_match("%^(file|ftp|https?)://%i", $this->config['download-url-prefix']))
		{
			$slash = substr($this->config['download-url-prefix'], 0, 1) === '/' ? '' : '/';
			$this->config['download-url-prefix'] = $this->baseUri . $slash . $this->config['download-url-prefix'];
		}

		// Remove any trailing slashes from the download-url-prefix
		$this->config['download-url-prefix'] = preg_replace("%/+$%", "", $this->config['download-url-prefix']);

		// Set the default catalog-uri if needed
		if(! isset($this->config['catalog-uri']))
			$this->config['catalog-uri'] = self::DEFAULT_CATALOG_URI;
		else
		{
			// They configured the catalog-uri, remove any leading or trailing slashes
			// from the configured value, in case there are any.
			$this->config['catalog-uri'] = preg_replace("%(^/+|/+$)%", "", $this->config['catalog-uri']);
		}
	}

	public function getConfig()
	{
		return $this->config;
	}

	public function buildRoutes()
	{
		return array(
			$this->config['catalog-uri'] => self::ROUTE_CATALOG,
			self::ROUTE_DEBUG => self::ROUTE_DEBUG,
		);
	}

	public function initRoute()
	{
		$this->routeList = $this->buildRoutes();

		// By default pathInfo is everything after the script
		$this->pathInfo = $this->computePathInfo();

		foreach($this->routeList as $uri => $routeType)
		{
			$len = strlen($uri);

			// If pathInfo is like "/$uri/*"
			if(substr($this->pathInfo, 0, $len+2) === '/'.$uri.'/' ||
				//  or if path info is "/$uri"
				(substr($this->pathInfo, 0, $len+1) === '/'.$uri && strlen($this->pathInfo) === $len+1))
			{
				// We matched a route
				// Set the route type
				// Remove the route uri from the pathInfo

				$this->route = $routeType;
				$this->pathInfo = substr($this->pathInfo, $len+1);
				return;
			}
		}
	}

	public function init()
	{
		$this->loadConfig();
		$this->checkConfig();

		$this->initRoute();
	}

	public function parseMetadataTemplate($template)
	{
		$parse = $template;

		$parse = preg_replace("/\{\{download_url_prefix\}\}/", $this->config['download-url-prefix'], $parse);
		$parse = preg_replace("/\{\{path_info\}\}/", $this->pathInfo, $parse);

		return $parse;
	}

	public function execCatalogRoute()
	{
		$metadataPath = $this->pathInfo . DIRECTORY_SEPARATOR . 'metadata.json';

		$metadataFile = $this->config['metadata-root'] . $metadataPath;

		if(! file_exists($metadataFile))
			throw new HttpException("No such file: $metadataFile", 404);

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

	public function execIndexRoute()
	{
		$currentDir = $this->config['metadata-root'] . $this->pathInfo;

		$smarty = new Smarty($this->baseDir);

		$smarty->assign('BASE_URI', $this->scriptRelativeDir);
		$smarty->assign('CATALOG_URI', $this->scriptRelativeDir.'/'.$this->config['catalog-uri']);

		$smarty->assign('pathInfo', $this->pathInfo);
		// pathInfo with no leading slash (possibly empty string)
		$smarty->assign('relativePathInfo', preg_replace('%^/+%', '', $this->pathInfo));

		$scanner = new DirectoryScan($currentDir);
		$dirInfo = $scanner->scan();

		$smarty->assign('directories', $dirInfo['dirs']);
		$smarty->assign('boxes', $dirInfo['boxes']);

		$result = array(
			'headers' => array(
				'Content-Type: text/html',
			),
			'content' => $smarty->fetch('index.tpl'),
		);
		return $result;
	}

	public function exec()
	{
		switch($this->route)
		{
			case self::ROUTE_CATALOG:
				$result = $this->execCatalogRoute();
				break;

			case self::ROUTE_INDEX:
				$result = $this->execIndexRoute();
				break;

			case self::ROUTE_DEBUG:
				$result = array(
					'headers' => array('Content-Type: text/plain'),
					'content' => "DEBUG OUTPUT" .
						"\n\n".
						var_export($this, true) .
						"\n\n" .
						var_export($_SERVER, true),
				);
				break;

			default:
				throw new Exception("No such route: ".$this->route);
		}

		return $result;
	}
}