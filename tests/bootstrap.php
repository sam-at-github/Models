<?php
error_reporting((E_ALL)&~(E_STRICT)); # Static abstract, Covariance.
require_once('vendor/autoload.php');
putenv("DATADIR=".dirname(__FILE__) . "/test-data/");
putenv("MONGO_DB_DSN=mongodb://test:pass@localhost/test");
