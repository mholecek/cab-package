<?php

declare(strict_types=1);

namespace Holda\CAB\Package;

/**
 * Class represents CFFOLDER stucture
 * Size: 8 bytes
 */
final class Folder
{
	/**
	 * Number of CFDATA blocks in this folder
	 *
	 * @see u2 cCFData
	 * @var int
	 */
	public $blocks = 0;
	
	/** @var int */
	public $dataSize = 0;
	
	/** @var File[] */
	private $files = [];
	
	/**
	 * offset of the first CFDATA block in this folder
	 *
	 * @see u4 coffCabStart
	 * @var int
	 */
	public $offset = 44;
	
	/**
	 * 0 = no compression, 1 = MSZIP; LZX
	 *
	 * @see u2 typeCompress
	 * @var int
	 */
	public $compressionType = 0;
	
	public function addFile(File $file): void
	{
		$file->offset = $this->dataSize;

		$this->dataSize += $file->size;
		// header 36bytes, folder 8bytes, file 17bytes
		$this->offset += 16 + strlen($file->name);  //+ 1; // offset of first fileData
		
		$this->files[] = $file;
	}
	
	/**
	 * @return File[]
	 */
	public function getFiles(): array
	{
		return $this->files;
	}
}
