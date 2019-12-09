<?php

declare(strict_types=1);

namespace Holda\CAB\Package;

/**
 * Class represents CFHEADER stucture
 * Size: 24 + 2 + 10 = 36 bytes
 */
final class Header
{
	/**
	 * File signature
	 *
	 * @see u4 signature
	 * @var string
	 */
	private $signature = 'MSCF';
	
	/**
	 * Reserved space, must be 0
	 *
	 * @see  u4 reserved1
	 * @var int
	 */
	private $reserved1 = 0;
	
	/**
	 * Total size of CAB file in bytes
	 *
	 * @see u4 cbCabinet size of this cabinet file in bytes
	 * @var int
	 */
	public $cbCabinet = 44;
	
	/**
	 * Default value is 0
	 *
	 * @see  u4 reserved2
	 * @var int
	 */
	private $reserved2 = 0;
	
	/**
	 * Offset of first data block CFFILE
	 *
	 * @see u4 coffFiles offset of the first CFFILE entry
	 * @var int
	 */
	public $coffFiles = 36 + 8;
	
	/**
	 * Default value is 0
	 *
	 * @see  u4 reserved3
	 * @var int
	 */
	private $reserved3 = 0;
	
	/**
	 * CAB file format major version number 1 (u1 versionMinor)
	 * @var int
	 */
	private $versionMajor = 1;
	
	/**
	 * CAB file format minor version number 3 (u1 versionMajor)
	 * @var int
	 */
	private $versionMinor = 3;
	
	/**
	 * Must have at least one folder
	 *
	 * @see  u2 cFolders number of CFFOLDER entries in this cabinet
	 * @var int
	 */
	public $foldersCount = 1;
	
	/**
	 * Number of files in cab file (u2 cFiles number of CFFILE entries in this cabinet)
	 * @var int
	 */
	public $filesCount = 0;
	
	/**
	 * not supported (u2 flags cabinet file option indicators)
	 *
	 * @var int
	 */
	private $flags = 0;
	
	/**
	 * set ID (not supported) (u2 setID)
	 *
	 * @var int
	 */
	private $setid = 1570;
	
	/**
	 * CAB id (not supported) (u2 iCabinet)
	 *
	 * @var int
	 */
	private $cabid = 0;
	
	public function getCabId(): int
	{
		return $this->cabid;
	}
	
	public function getFlags(): int
	{
		return $this->flags;
	}
	
	public function getReserved1(): int
	{
		return $this->reserved1;
	}
	
	public function getReserved2(): int
	{
		return $this->reserved2;
	}
	
	public function getReserved3(): int
	{
		return $this->reserved3;
	}
	
	public function getSignature(): string
	{
		return $this->signature;
	}
	
	public function getSetId(): int
	{
		return $this->setid;
	}
	
	public function getVersionMajor(): int
	{
		return $this->versionMajor;
	}
	
	public function getVersionMinor(): int
	{
		return $this->versionMinor;
	}
}
