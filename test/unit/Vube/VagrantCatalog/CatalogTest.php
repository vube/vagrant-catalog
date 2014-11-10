<?php
/**
 * @author Ross Perkins <ross@vubeology.com>
 */

namespace unit\Vube\VagrantCatalog;


use org\bovigo\vfs\vfsStream;
use Vube\VagrantCatalog\Catalog;


class CatalogTest extends \PHPUnit_Framework_TestCase {

	private static $configPhpSource;
	private static $defaultServerSettings;

	private $root;

	public static function parseUrlIntoServerArgs($url, $basePath='')
	{
        if($basePath == '/')
            $basePath = '';

		$u = parse_url($url);

		$defaultPort = ($u['scheme'] == 'https' ? 443 : 80);
		if(empty($u['path'])) $u['path'] = '/';
		if(empty($u['port'])) $u['port'] = $defaultPort;

		$http_host = $u['host'].($u['port'] == $defaultPort ? '' : ':'.$u['port']);
		$query = empty($u['query']) ? '' : '?'.$u['query'];

		$server = array(
			'HTTP_HOST' => $http_host,
			'REQUEST_URI' => $u['path'] . $query,
			'SCRIPT_NAME' => $basePath . '/index.php',
		);
		return $server;
	}

	public static function setUpBeforeClass()
	{
		self::$defaultServerSettings = self::parseUrlIntoServerArgs('http://localhost');

		self::$configPhpSource = file_get_contents($GLOBALS['PHPUNIT_FIXTURES_DIR']."/config.php");
	}

	public function setUp()
	{
		$this->root = vfsStream::setup('root', null, array(
			'docroot' => array(
				'config.php' => self::$configPhpSource,
				'metadata' => array(
					'foo' => array(
						'metadata.json' => '{"name":"foo"}',
					),
					'metadata.json' => '{"name":""}',
				),
				'base' => array(
					'config.php' => self::$configPhpSource,
					'metadata' => array(
						'foo' => array(
							'metadata.json' => '{"name":"base/foo"}',
						),
						'metadata.json' => '{"name":"base"}',
					),
				),
			),
		));
	}

	public function constructCatalog($url='http://localhost/', $basePath='/')
	{
		$server = self::parseUrlIntoServerArgs($url, $basePath);

		$_SERVER = array_merge(self::$defaultServerSettings, $server);

		// Remove trailing slashes, if any, from basePath
		$basePath = preg_replace("%/+$%", "", $basePath);

		$catalog = new Catalog(vfsStream::url("root/docroot$basePath"));
		return $catalog;
	}

	public function testComputeDocrootEmptyPathInfo()
	{
		$expected = '';

		$catalog = $this->constructCatalog();
		$pathinfo = $catalog->computePathInfo();

		$this->assertSame($expected, $pathinfo);
	}

	public function testComputeDocrootNonEmptyPathInfo()
	{
		$expected = '/foo';

		$catalog = $this->constructCatalog("http://localhost/foo");
		$pathinfo = $catalog->computePathInfo();

		$this->assertSame($expected, $pathinfo);
	}

	public function testComputeSubdirEmptyPathInfo()
	{
		$expected = '';

		$catalog = $this->constructCatalog("http://localhost/base/", "/base");
		$pathinfo = $catalog->computePathInfo();

		$this->assertSame($expected, $pathinfo);
	}

	public function testComputeSubdirNonEmptyPathInfo()
	{
		$expected = '/foo';

		$catalog = $this->constructCatalog("http://localhost/base$expected", "/base");
		$pathinfo = $catalog->computePathInfo();

		$this->assertSame($expected, $pathinfo);
	}

	public function testLoadConfigWithConfigPhp()
	{
		$expectedFile = vfsStream::url("root/docroot/config.php");

		$catalog = $this->constructCatalog();
		$config = $catalog->loadConfig();
		$catalog->checkConfig();
		$configFilename = $catalog->getConfigFilename();

		$this->assertSame($expectedFile, $configFilename);
	}

	public function testLoadConfigWithConfigPhpDist()
	{
		$configPhp = vfsStream::url("root/docroot/config.php");
		$expectedFile = "$configPhp.dist";

		// Move config.php to config.php.dist
		// E.g. simulate that config.php hasn't been created and only the
		// default config.php.dist file exists.
		rename($configPhp, $expectedFile);

		$catalog = $this->constructCatalog();

		// silence error about config.php not being found
		$catalog->ignoreErrors();

		$catalog->loadConfig();

		$configFilename = $catalog->getConfigFilename();
		$this->assertSame($expectedFile, $configFilename);

		$config = $catalog->getConfig();
		$this->assertTrue(is_array($config), "loaded config is array");
	}

	public function testTemplateParseDocroot()
	{
		$template = '{"url":"{{download_url_prefix}}{{path_info}}/filename.box"}';
		$expected = '{"url":"http://download.dev/files/filename.box"}';

		$catalog = $this->constructCatalog();
		$catalog->loadConfig();
		$catalog->checkConfig();

		$result = $catalog->parseMetadataTemplate($template);

		$this->assertSame($expected, $result);
	}

	public function testTemplateParseDocrootWithRelativeBaseUrlPrefix()
	{
		$template = '{"url":"{{download_url_prefix}}{{path_info}}/filename.box"}';
		$expected = '{"url":"http://localhost/PREFIX/filename.box"}';

		$catalog = $this->constructCatalog();
		$catalog->loadConfig();
		// Before checkConfig() override the download-url-prefix to a relative
		// directory called PREFIX.  checkConfig() should then make it absolute
		// based on the current URL
		$catalog->setConfig('download-url-prefix', 'PREFIX');
		$catalog->checkConfig();

		$result = $catalog->parseMetadataTemplate($template);

		$this->assertSame($expected, $result);
	}

	public function testTemplateParseSubdirPathInfo()
	{
		$template = '{"url":"{{download_url_prefix}}{{path_info}}/filename.box"}';
		$expected = '{"url":"http://download.dev/files/foo/filename.box"}';

		$catalog = $this->constructCatalog("http://localhost/base/foo", "/base");
		$catalog->init();

		$result = $catalog->parseMetadataTemplate($template);

		$this->assertSame($expected, $result);
	}

	public function testExecDocroot()
	{
		$expected = @file_get_contents(vfsStream::url('root/docroot/metadata/metadata.json'));

		$catalog = $this->constructCatalog("http://localhost/catalog/");
		$catalog->init();
		$result = $catalog->exec();

		$this->assertSame($expected, $result['content']);
	}

    public function testExecNoSuchDirectory()
    {
        $catalog = $this->constructCatalog("http://localhost/catalog/no/such/directory");
        $catalog->init();

        // Expect this to give a HTTP 404 exception
        $this->setExpectedException('\\Vube\\VagrantCatalog\\Exception\\HttpException', '', 404);
        $unused = $catalog->exec();
    }

	public function testExecDocrootPathInfo()
	{
		$pathinfo = "/foo";
		$expected = @file_get_contents(vfsStream::url("root/docroot/metadata$pathinfo/metadata.json"));

		$catalog = $this->constructCatalog("http://localhost/catalog/foo");
		$catalog->init();
		$result = $catalog->exec();

		$this->assertSame($expected, $result['content']);
	}

	public function testExecSubdir()
	{
		$expected = @file_get_contents(vfsStream::url('root/docroot/base/metadata/metadata.json'));

		$catalog = $this->constructCatalog("http://localhost/base/catalog", "/base");
		$catalog->init();
		$result = $catalog->exec();

		$this->assertSame($expected, $result['content']);
	}

	public function testExecSubdirPathInfo()
	{
		$expected = @file_get_contents(vfsStream::url('root/docroot/base/metadata/foo/metadata.json'));

		$catalog = $this->constructCatalog("http://localhost/base/catalog/foo", "/base");
		$catalog->init();
		$result = $catalog->exec();

		$this->assertSame($expected, $result['content']);
	}
}
 