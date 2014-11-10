<?php
/**
 * @author Ross Perkins <ross@vubeology.com>
 */

namespace unit\Vube\VagrantCatalog;


use org\bovigo\vfs\vfsStream;
use Vube\VagrantCatalog\DirectoryScan;


class DirectoryScanTest extends \PHPUnit_Framework_TestCase
{

    private $root;

    public function setUp()
    {
        $this->root = vfsStream::setup('root', null, array(
            'docroot' => array(
                'non-empty' => array(
                    'non-empty' => array(
                        'metadata.json' => '{"name":"foo"}',
                        'empty' => array(),
                    ),
                    'metadata.json' => '{"name":""}',
                ),
                'empty' => array(
                    'empty' => array(
                        'empty' => array(),
                        'README' => 'no metadata.json in this path'
                    ),
                ),
            ),
        ));
    }

    public function testCountMetadataChildren()
    {
        $tests = array(
            'docroot' => 2,
            'docroot/non-empty' => 2,
            'docroot/non-empty/non-empty' => 1,
            'docroot/non-empty/non-empty/empty' => 0,
            'docroot/empty' => 0,
            'docroot/empty/empty' => 0,
            'docroot/empty/empty/empty' => 0,
        );

        foreach ($tests as $scanDir => $expectedMetadataNodes)
        {
            $dir = vfsStream::url("root/$scanDir");
            $scanner = new DirectoryScan($dir);
            $actualMetadataNodes = $scanner->countMetadataChildren($dir);

            $this->assertSame($expectedMetadataNodes, $actualMetadataNodes,
                "Failed to count metadata.json nodes in $dir");
        }
    }

    public function testDirectoryScan()
    {
        $dir = vfsStream::url("root/docroot");
        $scanner = new DirectoryScan($dir);
        $result = $scanner->scan();

        $this->assertArrayHasKey('dirs', $result);
        $this->assertArrayHasKey('boxes', $result);
        $this->assertArrayHasKey('metadata', $result);

        $this->assertSame(array('non-empty'), $result['dirs']);
        $this->assertSame(array('non-empty'), $result['boxes']);
        $this->assertNull($result['metadata']);
    }

    public function testDirectoryScanNonEmpty()
    {
        $dir = vfsStream::url("root/docroot/non-empty");
        $scanner = new DirectoryScan($dir);
        $result = $scanner->scan();

        $this->assertArrayHasKey('dirs', $result);
        $this->assertArrayHasKey('boxes', $result);
        $this->assertArrayHasKey('metadata', $result);

        $this->assertSame(array('non-empty'), $result['dirs']);
        $this->assertSame(array('non-empty'), $result['boxes']);
        $this->assertNotNull($result['metadata']);
    }

    public function testDirectoryScanNonEmptyNonEmpty()
    {
        $dir = vfsStream::url("root/docroot/non-empty/non-empty");
        $scanner = new DirectoryScan($dir);
        $result = $scanner->scan();

        $this->assertArrayHasKey('dirs', $result);
        $this->assertArrayHasKey('boxes', $result);
        $this->assertArrayHasKey('metadata', $result);

        $this->assertSame(array(), $result['dirs']);
        $this->assertSame(array(), $result['boxes']);
        $this->assertNotNull($result['metadata']);
    }

    public function testDirectoryScanEmpty()
    {
        $dir = vfsStream::url("root/docroot/empty");
        $scanner = new DirectoryScan($dir);
        $result = $scanner->scan();

        $this->assertArrayHasKey('dirs', $result);
        $this->assertArrayHasKey('boxes', $result);
        $this->assertArrayHasKey('metadata', $result);

        $this->assertSame(array(), $result['dirs']);
        $this->assertSame(array(), $result['boxes']);
        $this->assertNull($result['metadata']);
    }
}
