<?php
/**
 * vagrant catalog
 *
 * @author Ross Perkins <ross@vubeology.com>
 * @copyright 2014 Vubeology LLC
 */

$composerAutoLoader = implode(DIRECTORY_SEPARATOR, array(__DIR__,'vendor','autoload.php'));
require_once $composerAutoLoader;

use Vube\VagrantCatalog\Catalog;


try
{
	$catalog = new Catalog(__DIR__);

	$catalog->init();
	$result = $catalog->exec();

	foreach($result['headers'] as $header)
		header($header);

	if($_SERVER['REQUEST_METHOD'] !== 'HEAD')
		echo $result['content'];
}
catch(\Vube\VagrantCatalog\Exception\HttpException $e)
{
	header('Content-Type: text/plain', true, $e->getCode());
	echo $e->getMessage();
}
catch(\Exception $e)
{
	// Return 500 Internal Server Error
	header('Content-Type: text/plain', true, 500);
	echo "Fatal Error: ".$e."\n";
}
