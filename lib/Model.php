<?php

namespace Gust;

class Model {
	static public function create($name) {
		$php = <<<EOT
<?php

\$$name = [
	'name' => '$name',
	'struct' => [
		// define your attributes below
		// 'attributeName'		=> [
		//	'type'		=> 's/t/i/d',
		//	'required'	=> true/false,
		//	'date'		=> true/false,
		//	'size'	=> 512,
		//	'unsigned'	=> true/false,
		//	'precision'	=> 5,
		//  'scale' => 2,
		// ],
	],
];

?>
EOT;
	}
}


?>