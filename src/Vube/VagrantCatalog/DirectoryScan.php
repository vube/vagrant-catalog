<?php
/**
 * @author Ross Perkins <ross@vubeology.com>
 */

namespace Vube\VagrantCatalog;
use Vube\VagrantCatalog\Exception\HttpException;


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

	public function countSubDirectories($dir)
	{
		$dh = opendir($dir);
		if(! $dh)
			throw new Exception("Cannot read directory: ".$this->dir);

		$n = 0;
		while($file = readdir($dh))
		{
			// If we should ignore this file, do so
			if(in_array($file, $this->ignoreList))
				continue;

			// If it's a directory, count it
			$path = $dir.DIRECTORY_SEPARATOR.$file;
			if(is_dir($path))
				$n++;
		}

		closedir($dh);
		return $n;
	}

	public function scan()
	{
        if(! file_exists($this->dir))
            throw new HttpException("No such file or directory: ".$this->dir, 404);

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

			$path = $this->dir . DIRECTORY_SEPARATOR . $file;

			if(is_dir($path))
			{
				// If there is a metadata.json in this dir, it is a box
				if(file_exists($path . DIRECTORY_SEPARATOR . 'metadata.json'))
					$result['boxes'][] = $file;

				// Only list this as a directory IFF there are more
				// directories under it.
				if($this->countSubDirectories($path) > 0)
					$result['dirs'][] = $file;
			}
		}

		closedir($dh);
		return $result;
	}
}
