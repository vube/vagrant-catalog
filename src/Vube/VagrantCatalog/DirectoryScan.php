<?php
/**
 * @author Ross Perkins <ross@vubeology.com>
 */

namespace Vube\VagrantCatalog;


/**
 * DirectoryScan class
 *
 * @author Ross Perkins <ross@vubeology.com>
 */
class DirectoryScan {

	private $dir;
	private $ignoreList;

	public function __construct($dir)
	{
		$this->dir = $dir;
		$this->ignoreList = array('.', '..');
	}

	public function ignoreFile($file)
	{
		$this->ignoreList[] = $file;
	}

	public function scan()
	{
		$dh = opendir($this->dir);
		if(! $dh)
			throw new Exception("Cannot read directory: ".$this->dir);

		$result = array(
			'dirs' => array(),
			'boxes' => array(),
		);

		while($file = readdir($dh))
		{
			// If we should ignore this file, do so
			if(in_array($file, $this->ignoreList))
				continue;

			if(is_dir($this->dir . DIRECTORY_SEPARATOR . $file))
				$result['dirs'][] = $file;
			else if($file === 'metadata.json')
				$result['boxes'][] = $file;
		}

		return $result;
	}
}
