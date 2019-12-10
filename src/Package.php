<?php

declare(strict_types=1);

namespace Holda\CAB;

/**
 * @see https://docs.microsoft.com/en-us/previous-versions/bb417343(v=msdn.10)?redirectedfrom=MSDN#microsoft-cabinet-file-format
 */
class Package
{
	/**
	 * Max data block size
	 *
	 * @var int
	 */
	static $MAX_BLOCKSIZE_BYTES = 32768;
	
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
	 * @param string $fileName         - file that will be written to
	 * @param int    $compressionLevel - level of compression (0 - no compression, 9 - maximum compression)
	 */
	public function __construct(string $fileName, int $compressionLevel = 1)
	{
		$this->header = new Package\Header();
		$this->folder = new Package\Folder();
		$this->cabFile = $fileName;
		$this->folder->compressionType = ($compressionLevel > 0 && extension_loaded('zlib')) ? $compressionLevel : 0;
	}
	
	/**
	 * Write the data to a CAB file
	 */
	public function write(): void
	{
		$this->folder->blocks = (int)ceil($this->folder->dataOffset / static::$MAX_BLOCKSIZE_BYTES);
		$this->header->cbCabinet += ($this->folder->blocks * 8);
		if (($res = fopen($this->cabFile, "w+b")) !== FALSE) {
			$this->writeHeader($res);
			$this->writeFolder($res);
			$this->writeFiles($res);
			$this->writeData($res);
			$this->updateCABsize($res);
			fclose($res);
		} else {
			throw new \RuntimeException("Failed to open " . $this->cabFile);
		}
	}
	
	/**
	 * Add a local file system file to your CAB file
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
	
	/**
	 * Write the header
	 *
	 * @param $res
	 */
	private function writeHeader($res)
	{
		assert(is_resource($res));
		
		$this->writeByteBuffer($res, $this->header->getSignature());
		$this->writeDWord($res, $this->header->getReserved1());
		$this->writeDWord($res, (int)$this->header->cbCabinet);
		$this->writeDWord($res, $this->header->getReserved2());
		$this->writeDWord($res, $this->header->coffFiles);
		$this->writeDWord($res, $this->header->getReserved3());
		$this->writeByte($res, $this->header->getVersionMinor());
		$this->writeByte($res, $this->header->getVersionMajor());
		$this->writeWord($res, $this->header->foldersCount);
		$this->writeWord($res, $this->header->filesCount);
		$this->writeWord($res, $this->header->getFlags());
		$this->writeWord($res, $this->header->getSetId());
		$this->writeWord($res, $this->header->getCabId());
	}
	
	private function writeByteBuffer($res, string $scalar): void
	{
		fwrite($res, $scalar);
	}
	
	/**
	 * Write (unsigned char) single byte
	 *
	 * @param $res
	 * @param $scalar
	 */
	private function writeByte($res, $scalar): void
	{
		assert(is_resource($res));
		
		fwrite($res, pack("C", $scalar));
	}
	
	/**
	 * Write (unsigned short int) word
	 *
	 * @param $res
	 * @param $scalar
	 */
	private function writeWord($res, $scalar)
	{
		assert(is_resource($res));
		
		if (is_string($scalar) && preg_match("/[ \/a-zA-Z]/", $scalar)) {
			$f = [];
			for ($i = 0; $i < strlen($scalar); ++$i) {
				$f[$i] = ord(substr($scalar, $i, 1));
			}
			$fmt = "S" . strlen($scalar);
			fwrite($res, pack("$fmt", $f));
			return;
		}
		fwrite($res, pack("S", $scalar));
	}
	
	/**
	 * Write (unsigned long int) dword
	 *
	 * @param $res
	 * @param $scalar
	 */
	private function writeDWord($res, $scalar)
	{
		assert(is_resource($res));
		
		if (is_string($scalar) && preg_match("/[ \/a-zA-Z]/", $scalar)) {
			$f = [];
			for ($i = 0; $i < strlen($scalar); ++$i) {
				$f[$i] = ord(substr($scalar, $i, 1));
			}
			$fmt = "L" . strlen($scalar);
			fwrite($res, pack("$fmt", $f));
			return;
		}
		fwrite($res, pack("L", $scalar));
	}
	
	private function writeFolder($res): void
	{
		assert(is_resource($res));
		
		$this->writeDWord($res, $this->folder->offset);
		$this->writeWord($res, $this->folder->blocks);
		$this->writeWord($res, $this->folder->compressionType);
	}
	
	private function writeFiles($res): void
	{
		assert(is_resource($res));
		
		foreach ($this->folder->getFiles() as $file) {
			$this->writeDWord($res, $file->size); // cbFile
			$this->writeDWord($res, $file->offset); // uoffFolderStart
			$this->WriteWord($res, 0); //iFolder - folder index
			$this->writeWord($res, $file->date); // data
			$this->writeWord($res, $file->time); // time
			$this->writeWord($res, $file->attribs); // attribs
			$this->writeByteBuffer($res, $file->name); // szName
		}
	}
	
	private function writeData($res): void
	{
		assert(is_resource($res));
		
		$datasize = $this->folder->dataOffset;
		$blocksize = $datasize > static::$MAX_BLOCKSIZE_BYTES ? static::$MAX_BLOCKSIZE_BYTES : $datasize;
		$block = $blocksize;
		
		$newblock = FALSE;
		$buffer = "";
		foreach ($this->folder->getFiles() as $file) {
			if (($addedFile = @fopen($file->path, "rb")) !== FALSE) {
				
				$thisTimeDataLength = 0;
				$lastTimeDataLength = 0;
				
				if ($newblock === TRUE) {
					$buffer = "";
				}
				
				while (!feof($addedFile)) {
					$buffer .= fread($addedFile, $block);
					//@fixme - when block size === file size then read entire file until eof
					$currentFilePosition = ftell($addedFile);
					$thisTimeDataLength = $currentFilePosition - $lastTimeDataLength; // 11 - 0
					$lastTimeDataLength = $currentFilePosition; //11
					$datasize -= $thisTimeDataLength;
					
					if ($thisTimeDataLength === $blocksize) {
						$block = static::$MAX_BLOCKSIZE_BYTES;
						$newblock = TRUE;
					} else {
						$block -= $thisTimeDataLength;
						$newblock = FALSE;
						if ($block <= 0) {
							$block = static::$MAX_BLOCKSIZE_BYTES;
							$newblock = TRUE;
						}
					}
					
					if ($newblock) {
						$data = $this->folder->compressionType === 1 ? $this->compressData($buffer) : [
							'cbytes' => $blocksize,
							'data' => $buffer,
						];
						if (!empty($buffer)) {
							$this->writeDWord($res, 0); // cSum, not calculated
							$this->writeWord($res, $data['cbytes']); // cbData
							$this->writeWord($res, $blocksize); // cbUncomp
							$this->writeByteBuffer($res, $data['data']); //
						}
						$blocksize = $datasize > static::$MAX_BLOCKSIZE_BYTES ? static::$MAX_BLOCKSIZE_BYTES : $datasize;
						$buffer = "";
					}
				}
				
				// close input file
				fclose($addedFile);
				if (preg_match("'\.cabtemp$'", $file->path)) {
					unlink($file->path);
				}
			} else {
				throw new \RuntimeException("Failed to open " . $file->name);
			}
		}
	}
	
	/**
	 * Update the file size in the header
	 *
	 * @param $res
	 */
	private function updateCABsize($res): void
	{
		assert(is_resource($res));
		
		$t = ftell($res);
		fseek($res, 8);
		$this->writeDWord($res, $t);
	}
	
	private function compressData($buffer): array
	{
		$data['data'] = pack("C", 0x43) . pack("C", 0x4B) . gzdeflate($buffer, $this->folder->compressionType);
		$data['cbytes'] = $this->bytelen($data['data']);
		return $data;
	}
	
	private function bytelen($data): int
	{
		return strlen($data . "A") - 1;
	}
}
