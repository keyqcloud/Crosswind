#!/usr/bin/php
<?php

function print_usage() {
	echo "\n";
	echo "\033[1mUSAGE\033[0m\n\n";
	echo "kyte-model2sql.php \033[1m-n\033[0m \e[0;31m[number of keys to generate]\e[0m\n\n";
	echo "if no option is specified only one key will be generated.\n\n";
	exit(-1);
}

$options = getopt("n:");

$count = 1;

if (array_key_exists('n', $options)) {
	$count = intval($options['n']);
}

$output = '';

for ($i=0; $i < $count; $i++) { 
	$epoch = time();
	$identifier = uniqid();
	$secret_key = hash_hmac('sha1', $identifier, $epoch);
	$public_key = hash_hmac('sha1', $identifier, $secret_key);

	$output .= "INSERT INTO APIKey(`identifier`, `public_key`, `secret_key`, `epoch`) VALUES('$identifier', '$public_key', '$secret_key', $epoch);\n";
}

echo $output;

?>