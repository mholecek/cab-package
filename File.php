<?php

declare(strict_types=1);

namespace Holda\CAB\Package;

/**
 * Class represents CFFILE stucture
 * Size: 16 bytes + fileName bytes + 1 byte (nul)
 */
final class File
{
	public function __construct(string $file, string $fileName, int $attributes)
	{
		$this->attribs = $attributes;
		$this->date = $this->getCABFileDate(filemtime($file));
		$this->name = $fileName . "\0";
		$this->path = $file;
		$this->size = filesize($file);
		$this->time = $this->getCABFileTime(filemtime($file));
	}
	
	/** @var int */
	public $offset = 0;
	
	/** @var int */
	public $date;
	
	/** @var int */
	public $time;
	
	/** @var string */
	public $attribs;
	
	/** @var string */
	public $name;
	
	/** @var string */
	public $path;
	
	/**
	 * Size in bytes
	 *
	 * @var int
	 */
	public $size = 0;
	
	/**
	 * Make cabinet file date from certain file
	 *
	 * @param int $timestamp
	 *
	 * @return int
	 */
	private function getCABFileDate(int $timestamp): int
	{
		$s = localtime($timestamp, TRUE);
		$res = (($s['tm_year'] - 80) << 9) + (($s['tm_mon'] + 1) << 5) + $s['tm_mday'];
		return $res;
	}
	
	/**
	 * Make cabinet file date
	 *
	 * @param int $timestamp
	 *
	 * @return int
	 */
	private function getCABFileTime(int $timestamp): int
	{
		$s = localtime($timestamp, TRUE);
		$res = ($s['tm_hour'] << 11) + ($s['tm_min'] << 5) + ($s['tm_sec'] / 2);
		return (int)$res;
	}
}