#!/usr/bin/php
<?php

namespace Crosswind;

class Database {

	public function create_tables($charset, $engine, $path) {
		$models = [];
		$sqls = [];
	
		$builtin_models = $path."builtin/models";
		$user_models = $path."app/models";
	
		// check if dirs exists for each of the models
		if ( !file_exists( $builtin_models ) && !is_dir( $builtin_models ) ) {
			echo "\n";
			echo "\e[0;31m\033[1mUnable to find $builtin_models.\033[0m\n";
			exit(-1);
		}
	
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

		// iterate through each model and create sql table
		foreach ($models as $model) {
			$tbl_name = $$model['name'];
			$cols = $$model['struct'];
			$pk_name = '';	// store col struct for primary key

			$tbl_sql = <<<EOT
DROP TABLE IF EXISTS `$tbl_name`;
CREATE TABLE `$tbl_name` (
EOT;

			$num_fields = count($cols);
			$i = 1;
			// table columns
			foreach ($cols as $name => $attrs) {

				// check if required attrs are set
				if (!isset($attrs['date'])) {
					echo "\n";
					echo "\e[0;31m\033[1mdate attribute must be declared for column $name of table $tbl_name.\033[0m\n";
					exit(-1);
				}

				if (!isset($attrs['required'])) {
					echo "\n";
					echo "\e[0;31m\033[1mrequired attribute must be declared for column $name of table $tbl_name.\033[0m\n";
					exit(-1);
				}

				if (!isset($attrs['type'])) {
					echo "\n";
					echo "\e[0;31m\033[1mtype attribute must be declared for column $name of table $tbl_name.\033[0m\n";
					exit(-1);
				}

				$field = "`$name`";	// column name
				
				// type, size and if signed or not
				if ($attrs['date']) {
					$field .= ' bigint unsigned';
				} else {

					if ($attrs['type'] == 'i') {
						$field .= ' int';
						if (array_key_exists('size', $attrs)) {
							$field .= '('.$attrs['size'].')';
						}
						if (array_key_exists('unsigned', $attrs)) {
							$field .= ' unsigned';
						}
					} elseif ($attrs['type'] == 's') {
						$field .= ' varchar';
						if (array_key_exists('size', $attrs)) {
							$field .= '('.$attrs['size'].')';
						} else {
							echo "\n";
							echo "\e[0;31m\033[1mvarchar requires size to be declared for column $name of table $tbl_name.\033[0m\n";
							print_usage();
						}
					} elseif ($attrs['type'] == 'd' && array_key_exists('precision', $attrs) && array_key_exists('scale', $attrs)) {
						$field .= ' decimal('.$attrs['precision'].','.$attrs['scale'].')';
					} elseif ($attrs['type'] == 't') {
						$field .= ' text';
					} else {
						echo "\n";
						echo "\e[0;31m\033[1mUnknown type ".$attrs['type']." for column $name of table $tbl_name.\033[0m\n";
						print_usage();
					}
				}
				if (array_key_exists('default', $attrs)) {
					// default value?
					$field .= ' DEFAULT ';
					$field .= (is_string($attrs['default']) ? "'".$attrs['default']."'" : $attrs['default']);
				}
				$field .= ($attrs['required'] ? ' NOT NULL' : '');		// required?

				if (array_key_exists('pk', $attrs)) {
					// primary key?
					if ($attrs['pk']) {
						$field .= ' AUTO_INCREMENT';
						$pk_name = $name;
					}
				}

				$field .= $i < $num_fields ? ",\n" : "\n";

				$tbl_sql .= <<<EOT
	$field
EOT;

				$i++;
			}

			// primary key
			$tbl_sql .= <<<EOT
	PRIMARY KEY (`$pk_name`)
) ENGINE=$engine DEFAULT CHARSET=$charset;
EOT;

			$sqls[$tbl_name] = $tbl_sql;
		}

		return $sqls;
	}
}


?>