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

	public function countMetadataChildren($dir)
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
			$path = $dir.'/'.$file;
			if(is_dir($path))
				$n += $this->countMetadataChildren($path);

            else if($file == 'metadata.json')
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
            'metadata' => null,
		);

		while($file = readdir($dh))
		{
			// If we should ignore this file, do so
			if(in_array($file, $this->ignoreList))
				continue;

			$path = $this->dir . '/' . $file;

			if(is_dir($path))
			{
				// If there is a metadata.json in this dir, it is a box
				if(file_exists($path . '/' . 'metadata.json'))
					$result['boxes'][] = $file;

				// Only list this as a directory IFF there are more
				// directories under it.
				if($this->countMetadataChildren($path) > 0)
					$result['dirs'][] = $file;
			}
            else if($file == 'metadata.json')
            {
                $result['metadata'] = $path;
            }
		}

		closedir($dh);

        sort($result['dirs']);
        sort($result['boxes']);

		return $result;
	}
}
