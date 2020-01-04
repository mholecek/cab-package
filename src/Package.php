<?php

declare(strict_types=1);

namespace Holda\CAB;

/**
 * @see https://docs.microsoft.com/en-us/previous-versions/bb417343(v=msdn.10)?redirectedfrom=MSDN#microsoft-cabinet-file-format
 */
class Package
{
	
	/**
	 * Path to a new cab file
	 *
	 * @var string
	 */
	private $cabFile;
	
	/**
	 * Data for CAB folder
	 *
	 * @var Package\Folder
	 */
	private $folder;
	
	/**
	 * Data for CAB Header
	 *
	 * @var Package\Header
	 */
	private $header;
	
	/**
	 * @param string $cabFile          - cabinet file, that will be written to disk
	 * @param int    $compressionLevel - level of compression (0 - no compression, 9 - maximum compression)
	 */
	public function __construct(string $cabFile, int $compressionLevel = 1)
	{
		$this->header = new Package\Header();
		$this->folder = new Package\Folder();
		$this->cabFile = $cabFile;
		$this->folder->compressionType = ($compressionLevel > 0 && extension_loaded('zlib')) ? $compressionLevel : 0;
	}
	
	/**
	 * Add a local file-system file to CAB package file
	 *
	 * @param string $filePath - path preferably absolute path
	 * @param int    $attribs  - e.g hidden, read-only - see sdk
	 */
	public function addFile(string $filePath, int $attribs = 32): void
	{
		$fileName = basename($filePath);
		
		// validate given path
		if (!is_file($filePath)) {
			throw new \RuntimeException('Invalid path specified: ' . $filePath);
		}
		
		// validate given file for Unicode name
		if (!mb_detect_encoding($fileName, 'ASCII', TRUE)) {
			throw new \RuntimeException('Unicode is not supported: ' . $filePath);
		}
		
		$file = new Package\File($filePath, $fileName, $attribs);
		
		$this->header->cbCabinet += 16 + strlen($fileName) + 1 + $file->size; // increase CAB file size
		$this->header->filesCount++; // increase file count in folder
		
		$this->folder->addFile($file);
		clearstatcache();
	}
	
	public function write(): void
	{
		$witer = new \Holda\CAB\Package\Writer($this->header, $this->folder);
		$witer->write($this->cabFile);
	}
}

