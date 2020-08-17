#!/usr/bin/php
<?php

function print_usage() {
	echo "\n";
	echo "\033[1mUSAGE\033[0m\n\n";
	echo "sqlUpdateCharsetCollate.php \033[1m--database\033[0m \e[0;31m<database name>\e[0m \033[1m--charset\033[0m \e[0;31m[utf8mb4|...]\e[0m \033[1m--collate\033[0m \e[0;31m[utf8mb4_unicode_ci|...]\e[0m \033[1m--appdir\033[0m \e[0;31m/path/to/kyte/app\e[0m\n\n";
	exit(-1);
}

$longopts  = array(
    "database:",
    "charset:",
	"collate:",
	"appdir:",
);
$options = getopt(null, $longopts);

if (!array_key_exists('database', $options)) print_usage();
if (!array_key_exists('charset', $options)) print_usage();
if (!array_key_exists('collate', $options)) print_usage();
if (!array_key_exists('appdir', $options)) print_usage();

$builtin_models = $options['appdir']."/builtin/models";
$user_models = $options['appdir']."/app/models";

// check if dirs exists for each of the models
if ( !file_exists( $builtin_models ) && !is_dir( $builtin_models ) ) {
	echo "\n";
	echo "\e[0;31m\033[1mUnable to find $builtin_models.\033[0m\n";
	print_usage();
}

$models = [];

// include models being used by app
foreach (glob("$builtin_models/*.php") as $filename) {
    require_once($filename);
    $model_name = substr($filename, 0, strrpos($filename, "."));
	$model_name = explode('/', $model_name);
	$model_name = end($model_name);
    if (!in_array($model_name, $models)) {
        $models[] = $model_name;
    }
}

/* Load user-defined files */
if ( file_exists( $user_models ) && is_dir( $user_models ) ) {
    // load user defined models and controllers (allow override of builtin)
    foreach (glob("$user_models/*.php") as $filename) {
        require_once($filename);
        $model_name = substr($filename, 0, strrpos($filename, "."));
		$model_name = explode('/', $model_name);
		$model_name = end($model_name);
        if (!in_array($model_name, $models)) {
			$models[] = $model_name;
		}
    }
}

// key-value describing model
// 
//	[
// 		'name'		=> 'name of table (also name of object)',
// 		'struct'	=> [
//			'column name' => [
//				'type'		=>	'i/s/d',		(*required*)
// 				'requred'	=>	true/false,		(*required*)
// 				'pk'		=>	true/false,
// 				'unsigned'	=>	true/false,
// 				'size'		=>	integer,
//				'default'	=>	value,
// 				'precision'	=>	integer,		(* for decimal type *)
// 				'scale'		=>	integer,		(* for decimal type *)
// 				'date'		=>	true/false,		(*required*)
// 				'kms'		=>	true/false,
//		 	],
//			...
//			'column name' => [ 'type' => 'i/s/d', 'requred' => true/false ],
//		]
//	]

$database = $options['database'];
$charset = $options['charset'];
$collate = $options['collate'];

$output = "ALTER DATABASE `$database` CHARACTER SET = $charset COLLATE = $collate;\n\n";

// iterate through each model and create sql table
foreach ($models as $model) {
	$tbl_name = $$model['name'];
	$cols = $$model['struct'];
	
	$output .= "ALTER TABLE `$tbl_name` CONVERT TO CHARACTER SET $charset COLLATE $collate;\n";
	
	// table columns
	foreach ($cols as $name => $attrs) {
		// skip primary keys
		if (array_key_exists('pk', $attrs)) {
			continue;
		}

		if ($attrs['type'] != 's') {
			continue;
		}

		// check if required attrs are set
		if (!isset($attrs['date'])) {
			echo "\n";
			echo "\e[0;31m\033[1mdate attribute must be declared for column $name of table $tbl_name.\033[0m\n";
			print_usage();
		}

		if (!isset($attrs['required'])) {
			echo "\n";
			echo "\e[0;31m\033[1mrequired attribute must be declared for column $name of table $tbl_name.\033[0m\n";
			print_usage();
		}

		if (!isset($attrs['type'])) {
			echo "\n";
			echo "\e[0;31m\033[1mtype attribute must be declared for column $name of table $tbl_name.\033[0m\n";
			print_usage();
		}

		$type_text = (array_key_exists('text', $attrs) ? ($attrs['text'] ? true : false) : false);

		$output .= "ALTER TABLE `$tbl_name` CHANGE `$name` `$name`";	// column name column_name

		// type, size and if signed or not
		if ($attrs['date']) {
			$output .= ' bigint unsigned';
		} else {
			if ($type_text) {
				$output .= ' text';
			} else {
				$output .= ' varchar';
				if (array_key_exists('size', $attrs)) {
					$output .= '('.$attrs['size'].')';
				} else {
					echo "\n";
					echo "\e[0;31m\033[1mvarchar requires size to be declared for column $name of table $tbl_name.\033[0m\n";
					print_usage();
				}
			}
		}
		
		$output .= " CHARACTER SET $charset COLLATE $collate";

		if (array_key_exists('default', $attrs)) {
			// default value?
			$output .= ' DEFAULT ';
			$output .= (is_string($attrs['default']) ? "'".$attrs['default']."'" : $attrs['default']);
		}
		$output .= ($attrs['required'] ? ' NOT NULL' : '');		// required?

		$output .= ";\n";
	}
}

file_put_contents('update_charset_collate.sql', $output);

?>