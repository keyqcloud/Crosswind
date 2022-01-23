<?php

namespace Gust;

class Controller {
	static public function create($name) {
		$php = <<<EOT
<?php

class {$name}Controller extends \Kyte\Mvc\Controller\ModelController
{
	/*
	 * available controller flags that can be modified in the initialization hook
	 * /
	// protected \$user;
	// protected \$account;
	// protected \$session;
	// protected \$response;
	// public \$dateformat;
	// public \$model;
	// protected \$cascadeDelete;
	// protected \$getFKTables;
	// protected \$getExternalTables;
	// protected \$requireAuth;
	// protected \$requireRoles;
	// protected \$requireAccount;
	// protected \$failOnNull;
	// protected \$allowableActions;
	// protected \$checkExisting;
	// protected \$exceptionMessages;

	/*
	 * available hooks to modify controller behaviour
	 */
	public function hook_init() {}
	public function hook_auth() {}
	public function hook_prequery(\$method, &\$field, &\$value, &\$conditions, &\$all, &\$order) {}
	public function hook_preprocess(\$method, &\$r, &\$o = null) {}
	public function hook_response_data(\$method, \$o, &\$r = null, &\$d = null) {}
	public function hook_process_get_response(&\$r) {}

	/*
	 * alternatively, the following class methods can be overriden instead
	 */
	// public function new(\$data) {}
	// public function update(\$field, \$value, \$data) {}
	// public function get(\$field, \$value) {}
	// public function delete(\$field, \$value) {}
}

?>
EOT;

		return $php;
	}
}


?>