#!/usr/bin/php
<?php

function print_usage() {
	echo "\n";
	echo "\033[1mUSAGE\033[0m\n\n";
	echo "model2dt.php \033[1m--modeldir\033[0m \e[0;31m/path/to/kyte/model\e[0m\n\n";
	echo "Will create generic data tables that may require further customization\n\n";
	exit(-1);
}

$longopts  = array(
    "modeldir:",
    "lang:",
);
$options = getopt(null, $longopts);

if (!array_key_exists('modeldir', $options)) print_usage();
$lang = '';
if (isset($options['lang'])) {
    switch ($options['lang']) {
        case 'ja':
            $lang = 'language: { "url": "https://cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Japanese.json" }, ';
            break;
        
        default:
            $lang = '';
            break;
    }
}
$path = $options['modeldir'];

// check if dirs exists for each of the models
if ( !file_exists( $path ) && !is_dir( $path ) ) {
	echo "\n";
	echo "\e[0;31m\033[1mUnable to find $path.\033[0m\n";
	print_usage();
}

$models = [];

// load models
foreach (glob("$path/*.php") as $filename) {
    require_once($filename);
    $model_name = substr($filename, 0, strrpos($filename, "."));
    $model_name = explode('/', $model_name);
    $model_name = end($model_name);
    if (!in_array($model_name, $models)) {
        $models[] = $model_name;
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

// iterate through each model and create sql table
foreach ($models as $model) {

    /* ******************** HTML */

    // generate table name
    $tablename = $$model['name'].'_'.uniqid();
    // open data table tag
    $output = '<table id="dt'.$tablename.'" class="display nowrap table table-striped table-bordered" style="width:100%">';
    $output .= "\n";
    
    // table header
    $output .= "\t";
    $output .= '<thead>';
    $output .= "\n";

    // table row
    $output .= "\t\t";
    $output .= '<tr>';
    $output .= "\n";

    // table columns
    $cols = $$model['struct'];
	foreach ($cols as $name => $attrs) {
        // table column header
        $output .= "\t\t\t";
        $output .= '<th>'.$name.'</th>';
        $output .= "\n";
    }

    // last column for crud controls
    $output .= "\t\t\t";
    $output .= '<th></th>';
    $output .= "\n";

    // close table row
    $output .= "\t\t";
    $output .= '</tr>';
    $output .= "\n";

    // close table header
    $output .= "\t";
    $output .= '</thead>';
    $output .= "\n";

    // table body
    $output .= "\t";
    $output .= '<tbody></tbody>';
    $output .= "\n";
    
	// close table tag
    $output .= '</table>';
    $output .= "\n";


    $output .= "\n\n\n";
    /* ******************** JAVASCRIPT */

    // start js tag
    $output .= '<script>';
    $output .= "\n";

    // create js variable for data table
    $output .= 'var '.$tablename.';';
    $output .= "\n";


    $output .= '// ** update to include field-value reqs if needed';
    $output .= "\n";

    $output .= "k.get('".$$model['name']."', null, null, function(response) {";
    $output .= "\n";
    
    $output .= "\t";
    $output .= $tablename.' = $("#'.$tablename.'").DataTable( { responsive: true, '.$lang.'data: response.data,';
    $output .= "\n";
    
    $output .= "\t\t";
    $output .= 'columnDefs: [';
    $output .= "\n";

    $i=0;
    foreach ($cols as $name => $attrs) {
        $output .= "\t\t\t";
        $output .= '{';
        $output .= "\n";

        $output .= "\t\t\t\t";
        $output .= '"targets": ['.$i.'],';
        $output .= "\n";

        $output .= "\t\t\t\t";
        $output .= '"data": "'.$name.'"';
        $output .= "\n";

        $output .= "\t\t\t";
        $output .= '},';
        $output .= "\n";

        $i++;
    }

    // crud controls
    $output .= "\t\t\t";
    $output .= '{';
    $output .= "\n";
    $output .= "\t\t\t\t";
    $output .= '"targets": ['.$i.'],';
    $output .= "\n";
    $output .= "\t\t\t\t";
    $output .= '"sortable": false,';
    $output .= "\n";
    $output .= "\t\t\t\t";
    $output .= '"data": "",';
    $output .= "\n";
    $output .= "\t\t\t\t";
    $output .= "render: function (data, type, row, meta) { return '<a class=\"mr-3 delete\"><i class=\"fas fa-trash-alt text-danger\"></i></a><a class=\"mr-3 edit\"><i class=\"fas fa-edit text-primary\"></i></a><a class=\"mr-3 viewDetails\"><i class=\"fas fa-info text-success\"></i></a>'; }";
    $output .= "\n";
    $output .= "\t\t\t";
    $output .= '}';
    $output .= "\n";
                
    $output .= "\t\t";
    $output .= '], order: [[ 0, "desc" ]]';
    $output .= "\n";
    
    $output .= "\t";
    $output .= '});';
    $output .= "\n";

    $output .= "}, function(response) { /* error handler */ });";
    $output .= "\n";

    // close js tag
    $output .= "</script>";
    $output .= "\n";
    

    // output to file
    file_put_contents($$model['name'].'.html', $output);
}

?>