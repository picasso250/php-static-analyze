<?php

spl_autoload_register(function ($class) {
	$f = __DIR__."/$class.php";
	if (is_file($f)) {
		require $f;
	}
});
