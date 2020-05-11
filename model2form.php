#!/usr/bin/php
<?php

function print_usage() {
	echo "\n";
	echo "\033[1mUSAGE\033[0m\n\n";
	echo "model2dt.php \033[1m--format\033[0m \e[0;31m/path/to/json/format\e[0m \033[1m--model\033[0m \e[0;31m/path/to/kyte/model\e[0m \033[1m--table\033[0m \e[0;31mID of table\e[0m\n\n";
	echo "Will create modal forms and their ajax functions - customization may be required\n\n";
	exit(-1);
}

$longopts  = array(
    "format:",
    "model:",
    "table:",
);
$options = getopt(null, $longopts);

if (!array_key_exists('format', $options)) print_usage();
if (!array_key_exists('model', $options)) print_usage();
if (!array_key_exists('table', $options)) print_usage();

$path['format'] = $options['format'];
$path['model'] = $options['model'];
$tableid = $options['table'];


// check if file exists
if ( !file_exists( $path['format'] ) ) {
	echo "\n";
	echo "\e[0;31m\033[1mUnable to find ".$path['format'].".\033[0m\n";
	print_usage();
}

// check if file exists
if ( !file_exists( $path['model'] ) ) {
	echo "\n";
	echo "\e[0;31m\033[1mUnable to find ".$path['model'].".\033[0m\n";
	print_usage();
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
// load model
require_once($path['model']);
$model = substr($path['model'], 0, strrpos($path['model'], "."));
$model = explode('/', $model);
$model = end($model);

// load form format
$format = json_decode(file_get_contents($path['format']), true);
// {
//     "model":"ModelName",                             <---- Name of model (future todo is to allow abstract models)
//     "fields":[                                       <---- form fields are created using bootstrap grid
//         {
//             "row": [
//                 {
//                     "name":"column_name_1",          <---- *required*
//                     "label":"label_for_column",      <---- optional, can be defined in model file
//                     "fa-prefix":"fa-user",           <---- optional, font awesome label prefix
//                     "select":[]                      <---- empty select signified it will be populated dynamically (optional)
//                 },
//                 {
//                     "name":"column_name_2",
//                     "size": 4                        <---- col size appended after col-sm- (optional)
//                 },
//                 {
//                     "name":"column_name_3",
//                     "limit": 3                       <---- field char size (optional)
//                 }
//             ]
//         },
//         {                                            <---- new form field row
//             "row": [
//                 {
//                     "name":"column_name_4",
//                 },
//                 {
//                     "name":"column_name_5",
//                     "select":["option1","option2"],  <---- define option fields as array when value and label are the same
//                     "size":2
//                 },
//                 {
//                     "name":"column_name_6",
//                     "select":[                       <---- alternatively, define the label and value separately for select fields
//                         { "label":"option1", "value":1, "disabled":false },
//                         { "label":"option2", "value":2, "disabled":false },
//                         { "label":"option2", "value":3, "disabled":true}
//                     ],
//                     "size":2
//                 },
//             ]
//         },

// generate form id
$formid = $format["model"].'_'.uniqid();

// to be able to demo the html file directly
$output = '<!DOCTYPE html><html lang="en" class="mdb-color"><head> <meta charset="utf-8"> <meta name="google" content="notranslate"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <meta http-equiv="x-ua-compatible" content="ie=edge"> <title>Kyte Form Output</title> <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.11.2/css/all.css"> <link href="https://cdn.keyq.cloud/css/bootstrap.min.css" rel="stylesheet"> <link href="https://cdn.keyq.cloud/css/mdb.min.css" rel="stylesheet"> <link href="https://cdn.keyq.cloud/css/addons/datatables.min.css" rel="stylesheet"> <link href="https://cdn.keyq.cloud/css/addons/datatables-select.min.css" rel="stylesheet"><script type="text/javascript" src="https://cdn.keyq.cloud/js/jquery.min.js"></script> <script type="text/javascript" src="https://cdn.keyq.cloud/js/popper.min.js"></script> <script type="text/javascript" src="https://cdn.keyq.cloud/js/bootstrap.min.js"></script> <script type="text/javascript" src="https://cdn.keyq.cloud/js/addons/datatables.min.js"></script> <script type="text/javascript" src="https://cdn.keyq.cloud/js/addons/datatables-select.min.js"></script> <script type="text/javascript" src="https://cdn.keyq.cloud/js/mdb.min.js"></script> <script type="text/javascript" src="https://cdn.keyq.cloud/js/ajaxzip3.js"></script> <script type="text/javascript" src="https://cdn.keyq.cloud/js/formvalidation.js"></script> <script type="text/javascript" src="'.$format["model"].'.js"></script></head><body>';
$output .= "\n\n\n";
$output .= '<!-- IGNORE THE ABOVE CODE WHEN IMPLEMENTING INTO YOR WEB APP -->';
$output .= "\n\n\n\n\n\n";

// modal button
$output .= '<a class="btn btn-lg btn-primary" id="openModal'.$formid.'"><i class="fas fa-plus prefix"></i> New</a>';
$output .= "\n\n";
// edit button
$output .= '<a class="btn btn-lg btn-secondary" id="edit'.$formid.'"><i class="fas fa-pencil-alt prefix"></i> Edit</a>';
$output .= "\n\n";

// modal dialog - begin
$output .= '<div class="modal fade" id="modal'.$formid.'" tabindex="-1" role="dialog" aria-labelledby="modal'.$formid.'" aria-hidden="true"><div class="modal-dialog modal-lg" role="document"><div class="modal-content"><div class="modal-header text-center"><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><div class="modal-body mx-3">';
$output .= "\n\n";

// open form tag
$output .= '<form novalidate="novalidate" class="needs-validation" id="form'.$formid.'">';
$output .= "\n";

// iterate through each model and create sql table
foreach ($format['fields'] as $fields) {

    // open grid row
    $output .= "\t";
    $output .= '<div class="row">';
    $output .= "\n";

    foreach ($fields['row'] as $row) {

        // check if row definition exists
        if (!isset($$model['struct'][$row['name']])) {
            echo "\n";
            echo "\e[0;31m\033[1mUnable to find definition for ".$row['name'].".\033[0m\n";
            print_usage();
        }

        // open grid col
        $output .= "\t\t";
        $output .= '<div class="col-sm'.(isset($row['size']) ? '-'.$row['size'] : '').'">';
        $output .= "\n";

        // open md form tag (not bootstrap supported)
        $output .= "\t\t\t";
        $output .= '<div class="md-form">';
        $output .= "\n";

        // check if fa-prefix is set
        if (isset($row['fa-prefix'])) {
            $output .= "\t\t\t\t";
            $output .= '<i class="fas '.$row['fa-prefix'].' prefix"></i>';
            $output .= "\n";
        }

        // determine what type of input field...
        // 
        // first check model to see if it is a unique requirement like textarea
        // ..., then check format
        if (isset($$model['struct'][$row['name']]['text'])) {
            // textarea
            $output .= "\t\t\t\t";
            $output .= '<textarea id="'.$formid.'_'.$row['name'].'" class="form-control md-textarea" name="'.$row['name'].'"'.($$model['struct'][$row['name']]['required'] ? ' required="required"' : '').'></textarea>';
            $output .= "\n";
        } else {
            if (isset($row['select'])) {

                // open select tag
                $output .= "\t\t\t\t";
                $output .= '<select id="'.$formid.'_'.$row['name'].'" class="mdb-select">';
                $output .= "\n";

                foreach ($row['select'] as $option) {
                    if (is_array($option)) {
                        $output .= "\t\t\t\t\t";
                        $output .= '<option value="'.$option['value'].'"'.($option['disabled'] ? ' disabled="disabled"' : '').'>'.$option['label'].'</option>';
                        $output .= "\n";
                    } else {
                        $output .= "\t\t\t\t\t";
                        $output .= '<option value="'.$option.'">'.$option.'</option>';
                        $output .= "\n";
                    }
                }

                // close select tag
                $output .= "\t\t\t\t";
                $output .= '</select>';
                $output .= "\n";

            } elseif (isset($row['password'])) {
                $output .= "\t\t\t\t";
                $output .= '<input type="password" id="'.$formid.'_'.$row['name'].'" class="form-control" name="'.$row['name'].'"'.($$model['struct'][$row['name']]['required'] ? ' required="required"' : '').'>';
                $output .= "\n";
            } else {
                $output .= "\t\t\t\t";
                $output .= '<input type="text" id="'.$formid.'_'.$row['name'].'" class="form-control" name="'.$row['name'].'"'.($$model['struct'][$row['name']]['required'] ? ' required="required"' : '').'>';
                $output .= "\n";
            }
        }

        // label
        if (isset($row['label'])) {
            $output .= "\t\t\t\t";
            $output .= '<label'.(isset($row['select']) ? ' class="mdb-main-label"' : '').' for="'.$formid.'_'.$row['name'].'">'.$row['label'].'</label>';
            $output .= "\n";
        } elseif (isset($$model['struct'][$row['name']]['label'])) {
            $output .= "\t\t\t\t";
            $output .= '<label'.(isset($row['select']) ? ' class="mdb-main-label"' : '').' for="'.$formid.'_'.$row['name'].'">'.$$model['struct'][$row['name']]['label'].'</label>';
            $output .= "\n";
        } else {
            $output .= "\t\t\t\t";
            $output .= '<label'.(isset($row['select']) ? ' class="mdb-main-label"' : '').' for="'.$formid.'_'.$row['name'].'">'.$row['name'].'</label>';
            $output .= "\n";
        }

        // close md form tag
        $output .= "\t\t\t";
        $output .= '</div>';
        $output .= "\n";

        // close grid col
        $output .= "\t\t";
        $output .= '</div>';
        $output .= "\n";

    }

    // close grid row
    $output .= "\t";
    $output .= '</div>';
    $output .= "\n";
    
}

// submit button
$output .= '<div class="row"><div class="col-sm text-center"><button class="btn btn-default" id="submit'.$formid.'">Ok</button></div></div>';
$output .= "\n\n";

// open form tag
$output .= '</form>';
$output .= "\n";

// modal dialog - end
$output .= '</div></div></div></div>';
$output .= "\n\n";

$output .= "\n\n\n\n\n\n";
$output .= "</body></html>";

// output to file
file_put_contents($format["model"].'.html', $output);


/********************** JAVASCRIPT */

$output = "var row".$formid.";";
$output .= "\n\n";
$output .= "$(document).ready(function() { ";
$output .= "\n\n";

// use material select for the options - is ignored if mdb is not supported
$output .= "\t";
$output .= "$('.mdb-select').materialSelect();";
$output .= "\n\n\n";

// function to open modal
$output .= "\t";
$output .= "/* OPEN MODAL FOR CREATE NEW */";
$output .= "\n";
$output .= "\t";
$output .= "$('#openModal".$formid."').click(function() {";
$output .= "\n";
// clear form
foreach ($format['fields'] as $fields) {
    foreach ($fields['row'] as $row) {
        $output .= "\t\t";
        $output .= "$('#".$formid.'_'.$row['name']."').val('');";
        $output .= "\n";
    }
}
$output .= "\t\t";
$output .= "$('#form".$formid."').data('objidx', '');";
$output .= "\n";
$output .= "\t\t";
$output .= "$('#modal".$formid."').modal('show');";
$output .= "\n";
$output .= "\t";
$output .= "});";
$output .= "\n";

$output .= "\n\n\n";

// function to create new or update existing data
$output .= "\t";
$output .= "/* CREATE NEW or UPDATE */";
$output .= "\n";
$output .= "\t";
$output .= "$('#form".$formid."').submit(function(event) {";
$output .= "\n";
$output .= "\t\t";
$output .= "var form = $(this);";
$output .= "\n";
$output .= "\t\t";
$output .= "event.preventDefault();";
$output .= "\n";
$output .= "\t\t";
$output .= "var valid = true;";
$output .= "\n";
$output .= "\t\t";
$output .= "form.find('input').each(function() { if($(this).prop('required') && !$(this).val()) { valid = false; } });";
$output .= "\n";
$output .= "\t\t";
$output .= "if (valid) {";
$output .= "\n";
$output .= "\t\t\t";
$output .= "if ($('#form".$formid."').data('objidx')) {";
$output .= "\n";
$output .= "\t\t\t\t";
$output .= "k.put('".$format["model"]."', 'id', $('#form".$formid."').data('objidx'), null, form.serialize(), function(response) {";
$output .= "\n";
$output .= "\t\t\t\t\t";
$output .= "/* callback function */";
$output .= "\n";
$output .= "\t\t\t\t\t";
$output .= "row".$formid.".data(response.data).draw();";
$output .= "\n";
$output .= "\t\t\t\t\t";
$output .= "$('#modal".$formid."').modal('hide');";
$output .= "\n";
$output .= "\t\t\t\t\t";
$output .= "$('#form".$formid."')[0].reset();";
$output .= "\n";
$output .= "\t\t\t\t";
$output .= "}, function(response) {";
$output .= "\n";
$output .= "\t\t\t\t\t";
$output .= "/* error callback function */";
$output .= "\n";
$output .= "\t\t\t\t\t";
$output .= "k.alert('Error', 'Error message：'+response, 'error', 0);";
$output .= "\n";
$output .= "\t\t\t\t";
$output .= "});";
$output .= "\n";
$output .= "\t\t\t";
$output .= "} else {";
$output .= "\n";
$output .= "\t\t\t\t";
$output .= "k.post('".$format["model"]."', null, form.serialize(), function(response) {";
$output .= "\n";
$output .= "\t\t\t\t\t";
$output .= "/* callback function */";
$output .= "\n";
$output .= "\t\t\t\t\t";
$output .= $tableid.".row.add(response.data).draw();";
$output .= "\n";
$output .= "\t\t\t\t\t";
$output .= "$('#modal".$formid."').modal('hide');";
$output .= "\n";
$output .= "\t\t\t\t\t";
$output .= "$('#form".$formid."')[0].reset();";
$output .= "\n";
$output .= "\t\t\t\t";
$output .= "}, function(response) {";
$output .= "\n";
$output .= "\t\t\t\t\t";
$output .= "/* error callback function */";
$output .= "\n";
$output .= "\t\t\t\t\t";
$output .= "k.alert('Error', 'Error message：'+response, 'error', 0);";
$output .= "\n";
$output .= "\t\t\t\t";
$output .= "});";
$output .= "\n";
$output .= "\t\t\t";
$output .= "}";
$output .= "\n";
$output .= "\t\t";
$output .= "}";
$output .= "\n";
$output .= "\t";
$output .= "});";
$output .= "\n\n\n";

// function to edit
$output .= "\t";
$output .= "/* EDIT */";
$output .= "\n";
$output .= "\t";
$output .= "$('#edit".$format["model"]."').click( function () {";  
$output .= "\n";
$output .= "\t\t";
$output .= "k.get('".$format["model"]."', 'id', $('#form".$formid."').data('objidx'), function(data) {";
$output .= "\n";
// populate form with data
foreach ($format['fields'] as $fields) {
    foreach ($fields['row'] as $row) {
        $output .= "\t\t\t";
        $output .= "$('#".$formid.'_'.$row['name']."').val(response.data[0].".$row['name'].").change();";
        $output .= "\n";
    }
}
// open modal
$output .= "\t\t\t";
$output .= "$('#modal".$formid."').modal('show');";
$output .= "\n";
$output .= "\t\t";
$output .= "}, function(response) { /* error callback function */ });";
$output .= "\n";
$output .= "\t";
$output .= "});";
$output .= "\n\n\n";

// function to delete
$output .= "\t";
$output .= "/* DELETE */";
$output .= "\n";
$output .= "\t";
$output .= "$('#delete".$format["model"]."').click( function () {";    
$output .= "\n";
$output .= "\t\t";
$output .= "k.confirm('Delete', 'Are you sure you wish to perform the delete operation', 'warning', function() {";
$output .= "\n";
$output .= "\t\t\t";
$output .= "k.delete('".$format["model"]."', 'id', $('#form".$formid."').data('objidx'), function() { /* callback function */ }, function(response) { /* error callback function */ } );";
$output .= "\n";
$output .= "\t\t";
$output .= "});";
$output .= "\n";
$output .= "\t";
$output .= "});";
$output .= "\n\n\n";

// function to edit (with table)
$output .= "\t";
$output .= "/* EDIT FROM TABLE */";
$output .= "\n";
$output .= "\t";
$output .= "$('#dt".$tableid." tbody').on('click', '.edit', function () {";
$output .= "\n";
$output .= "\t\t";
$output .= "row".$formid." = ".$tableid.".row($(this).parents('tr'))";
$output .= "\n";
$output .= "\t\t";
$output .= "var data = row".$formid.".data();";
$output .= "\n";
$output .= "\t\t";
$output .= "k.get('".$format["model"]."', 'id', data['id'], function(response) {";
$output .= "\n";
// populate form with data
foreach ($format['fields'] as $fields) {
    foreach ($fields['row'] as $row) {
        $output .= "\t\t\t";
        $output .= "$('#".$formid.'_'.$row['name']."').val(response.data[0].".$row['name'].").change();";
        $output .= "\n";
    }
}
// open modal
$output .= "\t\t\t";
$output .= "$('#form".$formid."').data('objidx', data['id']);";
$output .= "\n";
$output .= "\t\t\t";
$output .= "$('#modal".$formid."').modal('show');";
$output .= "\n";
$output .= "\t\t";
$output .= "}, function(response) { k.alert('Error', 'Error message：'+response, 'error', 0); /* error callback function */ });";
$output .= "\n";
$output .= "\t";
$output .= "});";
$output .= "\n\n\n";

// function to delete (with table)
$output .= "\t";
$output .= "/* DELETE FROM TABLE */";
$output .= "\n";
$output .= "\t";
$output .= "$('#dt".$tableid." tbody').on('click', '.delete', function () {";
$output .= "\n";
$output .= "\t\t";
$output .= "row".$formid." = ".$tableid.".row($(this).parents('tr'))";
$output .= "\n";
$output .= "\t\t";
$output .= "var data = row".$formid.".data();";
$output .= "\n";
$output .= "\t\t";
$output .= "k.confirm('Delete', 'Are you sure you wish to perform the delete operation', 'warning', function() {";
$output .= "\n";
$output .= "\t\t\t";
$output .= "k.delete('".$format["model"]."', 'id', data['id'], function() { row".$formid.".remove().draw(); /* callback function */ }, function(response) { k.alert('Error', 'Error message：'+response, 'error', 0); /* error callback function */ } );";
$output .= "\n";
$output .= "\t\t";
$output .= "});";
$output .= "\n";
$output .= "\t";
$output .= "});";
$output .= "\n\n\n";

$output .= "});";
$output .= "\n";

// output to file
file_put_contents($format["model"].'.js', $output);

?>