<?php
/**
 * Class MinifyCssTask
 *
 * @author: Jiří Šifalda <sifalda.jiri@gmail.com>
 * @date: 28.08.13
 */
namespace Tasker\Minify;

use Tasker\Setters\IRootPathSetter;
use Tasker\Tasks\ITaskService;
use Tasker\Concat\IConcatFiles;
use Tasker\Concat\ConcatFiles;
use Tasker\InvalidStateException;
use Tasker\Utils\FileSystem;

class MinifyCssTask implements ITaskService, IRootPathSetter
{

	private $root;

	/** @var IConcatFiles  */
	private $concatFiles;

	/**
	 * @param IConcatFiles $concatFiles
	 */
	function __construct(IConcatFiles $concatFiles = null)
	{
		if($concatFiles === null) {
			$concatFiles = new ConcatFiles;
		}

		$this->concatFiles = $concatFiles;
	}

	/**
	 * @param string $root
	 * @return $this
	 */
	public function setRootPath($root)
	{
		$this->root = (string) $root;
		return $this;
	}

	/**
	 * @param array $config
	 * @return array|mixed
	 * @throws \Tasker\InvalidStateException
	 */
	public function run(array $config)
	{
		$results = array();
		if(count($config)) {
			foreach ($config as $dest => $sources) {
				if(!is_string($dest)) {
					throw new InvalidStateException('Destination must be valid path');
				}

				$files = $files = $this->concatFiles->getFiles($sources);
				$result = $this->process($files, $dest);

				if($result === false) {
					$results[] = 'File "' . $dest . '" cannot be concatenated.';
				}else{
					$results[] = 'File "' . $dest . '" was concatenated and minified. ' . count($files) . ' files included.';
				}
			}
		}

		return $results;
	}

	protected function process($files, $dest)
	{
		$content = $this->getMinified($this->concatFiles->getFilesContent($files, $this->root));
		return FileSystem::write($this->root . DIRECTORY_SEPARATOR . $dest, $content);
	}

	/**
	 * @param $content
	 * @return mixed
	 */
	protected function getMinified($content)
	{
		return str_replace('; ', ';',
			str_replace(' }', '}',
				str_replace('{ ', '{',
					str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), "",
						preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content))
				)
			)
		);
	}
}