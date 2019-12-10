CAB package
===========
PHP library for creating CAB files (packages) directly

Usage
-----
Download or clone library.
Then, you can use it:
```
$package = new \Holda\CAB\Package('yourNewCABFile.cab',0);
$package->addFile(__DIR__ . '/data/testfile1.xlsx');
$package->addFile(__DIR__ . '/data/testfile2.txt');
$package->addFile(__DIR__ . '/data/testfile3.xlsx');
$package->write();

```
First parameter is file, which you want to create, second parameter is compression, where: 0 - means no compression (store) and 9 - means maximum compression.

```
$package = new \Holda\CAB\Package('yourNewCABFile.cab',0); // no compression

```

```
$package = new \Holda\CAB\Package('yourNewCABFile.cab',9); // maximum compression

```
Limitations
-----------
Currently, is not supported:
- Unicode in file names
- Multi CAB packages
- Maximum size of CAB file is 2GB
- Maximum file count inside CAB is about 64k
- LZX data compression

Others
------
For more informations about CAB packages, please see Microsoft specification page:
https://docs.microsoft.com/en-us/previous-versions/bb417343(v=msdn.10)?redirectedfrom=MSDN#microsoft-cabinet-file-format

