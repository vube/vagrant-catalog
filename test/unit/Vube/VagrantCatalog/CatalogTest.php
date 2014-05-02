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

	public static function setUpBeforeClass()
	{
		self::$defaultServerSettings = array(
			'HTTP_HOST' => 'http://localhost',
			'REQUEST_URI' => '/',
			'SCRIPT_NAME' => '/index.php',
		);

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

	public function constructCatalog($subdir='/', $SERVER=array())
	{
		if($subdir === '/')
			$subdir = '';

		$_SERVER = array_merge(self::$defaultServerSettings, $SERVER);

		$catalog = new Catalog(vfsStream::url("root/docroot$subdir"));
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

		$catalog = $this->constructCatalog("/", array(
			'REQUEST_URI' => $expected,
		));
		$pathinfo = $catalog->computePathInfo();

		$this->assertSame($expected, $pathinfo);
	}

	public function testComputeSubdirEmptyPathInfo()
	{
		$expected = '';

		$catalog = $this->constructCatalog("/base", array(
			'REQUEST_URI' => "/base",
			'SCRIPT_NAME' => "/base/index.php",
		));
		$pathinfo = $catalog->computePathInfo();

		$this->assertSame($expected, $pathinfo);
	}

	public function testComputeSubdirNonEmptyPathInfo()
	{
		$expected = '/foo';

		$catalog = $this->constructCatalog("/base", array(
			'REQUEST_URI' => "/base$expected",
			'SCRIPT_NAME' => "/base/index.php",
		));
		$pathinfo = $catalog->computePathInfo();

		$this->assertSame($expected, $pathinfo);
	}

	public function testLoadConfigWithConfigPhp()
	{
		$expectedFile = vfsStream::url("root/docroot/config.php");

		$catalog = $this->constructCatalog();
		$config = $catalog->loadConfig();
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

	public function testTemplateParse()
	{
		$template = '{"url":"{{download_url_prefix}}/filename.box"}';
		$expected = '{"url":"http://download.dev/files/filename.box"}';

		$catalog = $this->constructCatalog();
		$catalog->loadConfig();

		$result = $catalog->parseMetadataTemplate($template);

		$this->assertSame($expected, $result);
	}

	public function testExecDocroot()
	{
		$expected = @file_get_contents(vfsStream::url('root/docroot/metadata/metadata.json'));

		$catalog = $this->constructCatalog();
		$catalog->init();
		$result = $catalog->exec();

		$this->assertSame($expected, $result['content']);
	}

	public function testExecDocrootPathInfo()
	{
		$pathinfo = "/foo";
		$expected = @file_get_contents(vfsStream::url("root/docroot/metadata$pathinfo/metadata.json"));

		$catalog = $this->constructCatalog("/", array(
			'REQUEST_URI' => $pathinfo,
		));
		$catalog->init();
		$result = $catalog->exec();

		$this->assertSame($expected, $result['content']);
	}

	public function testExecSubdir()
	{
		$expected = @file_get_contents(vfsStream::url('root/docroot/base/metadata/metadata.json'));

		$catalog = $this->constructCatalog("/base", array(
			'REQUEST_URI' => "/base",
			'SCRIPT_NAME' => "/base/index.php",
		));
		$catalog->init();
		$result = $catalog->exec();

		$this->assertSame($expected, $result['content']);
	}

	public function testExecSubdirPathInfo()
	{
		$expected = @file_get_contents(vfsStream::url('root/docroot/base/metadata/foo/metadata.json'));

		$catalog = $this->constructCatalog("/base", array(
			'REQUEST_URI' => "/base/foo",
			'SCRIPT_NAME' => "/base/index.php",
		));
		$catalog->init();
		$result = $catalog->exec();

		$this->assertSame($expected, $result['content']);
	}
}
 