<?php

namespace Holda\CAB\Package;

final class Writer
{
	/**
	 * Max data block size
	 *
	 * @var int
	 */
	static $MAX_BLOCKSIZE = 32768;
	
	/**
	 * Data for CAB folder
	 *
	 * @var Folder
	 */
	private $folder;
	
	/**
	 * Data for CAB Header
	 *
	 * @var Header
	 */
	private $header;
	
	public function __construct(Header $header, Folder $folder)
	{
		$this->header = $header;
		$this->folder = $folder;
	}
	
	/**
	 * Write the header
	 *
	 * @param $res
	 */
	private function writeHeader($res)
	{
		assert(is_resource($res));
		
		Helpers::writeByteBuffer($res, $this->header->getSignature());
		Helpers::writeDWord($res, $this->header->getReserved1());
		Helpers::writeDWord($res, (int)$this->header->cbCabinet);
		Helpers::writeDWord($res, $this->header->getReserved2());
		Helpers::writeDWord($res, $this->header->coffFiles);
		Helpers::writeDWord($res, $this->header->getReserved3());
		Helpers::writeByte($res, $this->header->getVersionMinor());
		Helpers::writeByte($res, $this->header->getVersionMajor());
		Helpers::writeWord($res, $this->header->foldersCount);
		Helpers::writeWord($res, $this->header->filesCount);
		Helpers::writeWord($res, $this->header->getFlags());
		Helpers::writeWord($res, $this->header->getSetId());
		Helpers::writeWord($res, $this->header->getCabId());
	}
	
	private function writeFolder($res): void
	{
		assert(is_resource($res));
		
		Helpers::writeDWord($res, $this->folder->offset);
		Helpers::writeWord($res, $this->folder->blocks);
		Helpers::writeWord($res, $this->folder->compressionType);
	}
	
	private function writeFiles($res): void
	{
		assert(is_resource($res));
		
		foreach ($this->folder->getFiles() as $file) {
			Helpers::writeDWord($res, $file->size); // cbFile
			Helpers::writeDWord($res, $file->offset); // uoffFolderStart
			Helpers::writeWord($res, 0); //iFolder - folder index
			Helpers::writeWord($res, $file->date); // date
			Helpers::writeWord($res, $file->time); // time
			Helpers::writeWord($res, $file->attribs); // attribs
			Helpers::writeByteBuffer($res, $file->name); // szName
		}
	}
	
	private function writeData($cabinetFileHandle)
	{
		foreach ($this->folder->getFiles() as $file) {
			$blockData = $this->processFile($cabinetFileHandle, $this->getFileHandle($file), $blockData ?? '');
		}
		
		if (!empty($blockData)) {
			$this->writeBlock($cabinetFileHandle, $blockData);
		}
	}
	
	private function processFile($cabinetFileHandle, $processedFileHandle, string $blockData, int $length = NULL)
	{
		if ($length === NULL) {
			$length = !empty($blockData) ? (static::$MAX_BLOCKSIZE - strlen($blockData)) : static::$MAX_BLOCKSIZE;
		}
		
		if (!feof($processedFileHandle)) {
			$blockData .= fread($processedFileHandle, $length);
			
			if (strlen($blockData) < static::$MAX_BLOCKSIZE) {
				$length = static::$MAX_BLOCKSIZE - strlen($blockData);
			} else {
				$this->writeBlock($cabinetFileHandle, $blockData);
				$blockData = "";
			}
			$blockData = $this->processFile($cabinetFileHandle, $processedFileHandle, $blockData, $length);
		}
		
		return $blockData;
	}
	
	private function getFileHandle(File $file)
	{
		if (($resource = @fopen($file->path, "rb")) !== FALSE) {
			return $resource;
		} else {
			throw new \RuntimeException('File not exists!');
		}
	}
	
	private function writeBlock($res, string $blockData): void
	{
		$blockSize = strlen($blockData);
		assert($blockSize <= static::$MAX_BLOCKSIZE);
		
		$data = $this->folder->compressionType === 1 ? $this->compressData($blockData) : [
			'cbytes' => $blockSize,
			'data' => $blockData,
		];
		
		Helpers::writeDWord($res, 0); // cSum, not calculated
		Helpers::writeWord($res, $data['cbytes']); // cbData
		Helpers::writeWord($res, $blockSize); // cbUncomp
		Helpers::writeByteBuffer($res, $data['data']); //
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
		Helpers::writeDWord($res, $t);
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
	
	/**
	 * Write the data to a CAB file
	 *
	 * @param string $cabFile
	 */
	public function write(string $cabFile): void
	{
		$this->folder->blocks = (int)ceil($this->folder->dataSize / static::$MAX_BLOCKSIZE);
		$this->header->cbCabinet += ($this->folder->blocks * 8);
		if (($res = fopen($cabFile, "w+b")) !== FALSE) {
			$this->writeHeader($res);
			$this->writeFolder($res);
			$this->writeFiles($res);
			$this->writeData($res);
			$this->updateCABsize($res);
			fclose($res);
		} else {
			throw new \RuntimeException("Failed to open " . $cabFile);
		}
	}
}