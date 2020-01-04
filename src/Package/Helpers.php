<?php

namespace Holda\CAB\Package;

final class Helpers
{
	final static function writeByteBuffer($resource, string $scalar): void
	{
		assert(is_resource($resource));

		fwrite($resource, $scalar);
	}
	
	/**
	 * Write (unsigned char) single byte
	 *
	 * @param $resource
	 * @param $scalar
	 */
	final static function writeByte($resource, $scalar): void
	{
		assert(is_resource($resource));
		
		fwrite($resource, pack("C", $scalar));
	}
	
	/**
	 * Write (unsigned short int) word
	 *
	 * @param $resource
	 * @param $scalar
	 */
	final static function writeWord($resource, $scalar)
	{
		assert(is_resource($resource));
		
		if (is_string($scalar) && preg_match("/[ \/a-zA-Z]/", $scalar)) {
			$f = [];
			for ($i = 0; $i < strlen($scalar); ++$i) {
				$f[$i] = ord(substr($scalar, $i, 1));
			}
			$fmt = "S" . strlen($scalar);
			fwrite($resource, pack("$fmt", $f));
			return;
		}
		fwrite($resource, pack("S", $scalar));
	}
	
	/**
	 * Write (unsigned long int) dword
	 *
	 * @param $resource
	 * @param $scalar
	 */
	final static function writeDWord($resource, $scalar)
	{
		assert(is_resource($resource));
		
		if (is_string($scalar) && preg_match("/[ \/a-zA-Z]/", $scalar)) {
			$f = [];
			for ($i = 0; $i < strlen($scalar); ++$i) {
				$f[$i] = ord(substr($scalar, $i, 1));
			}
			$fmt = "L" . strlen($scalar);
			fwrite($resource, pack("$fmt", $f));
			return;
		}
		fwrite($resource, pack("L", $scalar));
	}
}
