<?php
/**
 * Test Bootloader
 *
 * Create the /tmp/vube-vagrant-catalog directory and chdir to it so any
 * relative files we test will be encapsulated in there.
 *
 * @author Ross Perkins <ross@vubeology.com>
 */

$composerAutoloadPhp = implode(DIRECTORY_SEPARATOR, array(__DIR__,'..','vendor','autoload.php'));
$loader = require_once $composerAutoloadPhp;


$GLOBALS['PHPUNIT_FIXTURES_DIR'] = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures';


$dir = sys_get_temp_dir() .DIRECTORY_SEPARATOR. 'vube-vagrancatalog';

if(! is_dir($dir))
{
	if(! mkdir($dir, 0775, true))
		throw new \Exception("Cannot create temp dir: $dir");
}

if(! chdir($dir))
	throw new \Exception("Cannot chdir to system temp: $dir");

// Remove any/all files in this directory that may persist since last run

$dh = opendir($dir);
while($file = readdir($dh))
{
	if($file === '.' || $file === '..')
		continue;

	// Remove this temp file
	unlink($dir . DIRECTORY_SEPARATOR . $file);
}
