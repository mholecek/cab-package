CAB package
===========
PHP library for creating CAB files (packages) directly

Usage
-----
Download or clone library.
Then, you can use it:
```php
$packagge = new \Holda\CAB\Package('yourNewCABFile.cab',0);
$package->addFile(__DIR__ . '/data/testfile1.xlsx');
$package->addFile(__DIR__ . '/data/testfile2.txt');
$package->addFile(__DIR__ . '/data/testfile3.xlsx');
$a->write();

```

Limitations
-----------
Currently, is not supported:
- Unicode in file names
- Multi CAB packages
- Maximum size of CAB file is 2GB
- Maximum file count inside CAB is about 64k

Others
------
For more informations about CAB packages, please see Microsoft specification page:
https://docs.microsoft.com/en-us/previous-versions/bb417343(v=msdn.10)?redirectedfrom=MSDN#microsoft-cabinet-file-format

