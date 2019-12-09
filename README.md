CAB package
===========

Usage
-----
```php

$a = new \Holda\CAB\Package('yourNewCABFile.cab',0);
$a->addFile(__DIR__ . '/data/testfile1.xlsx');
$a->addFile(__DIR__ . '/data/testfile2.txt');
$a->addFile(__DIR__ . '/data/testfile3.xlsx');
$a->write();

```

