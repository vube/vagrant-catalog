<?php
/**
 * @author Ross Perkins <ross@vubeology.com>
 */

namespace Vube\VagrantCatalog;


/**
 * Smarty class
 *
 * @author Ross Perkins <ross@vubeology.com>
 */
class Smarty extends \Smarty {

	public function __construct($baseDir)
	{
		parent::__construct();

		$this->setTemplateDir("$baseDir/templates");
		$this->setCompileDir(sys_get_temp_dir().DIRECTORY_SEPARATOR."vagrant-catalog".DIRECTORY_SEPARATOR.$baseDir);
	}
}