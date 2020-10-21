#!/usr/bin/env php
<?php

// constants
define('KYTE_STDIN', fopen("php://stdin","rb"));
define('KYTE_CROSSWIND_ENV', $_SERVER['HOME']."/.kytecrosswind");

if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
    echo 'Warning: Crosswind should be invoked via the CLI version of PHP, not the '.PHP_SAPI.' SAPI'.PHP_EOL;
}

setlocale(LC_ALL, 'C');

// check if .kytecrosswind exists
if (!file_exists( KYTE_CROSSWIND_ENV )) {
    echo "Thank you for installing Crosswind to get your Kyte application up in to the sky.\n";
    echo "First, we need some information to configure your Crosswind environment.\n\n";
    echo "Where is your Kyte application located? (/var/www/html/): ";
    $crosswind_env['kyte_dir'] = trim(fgets(KYTE_STDIN));

    echo "\n\nExcellent, next what is the DB engine? (InnoDB): ";
    $crosswind_env['db_engine'] = trim(fgets(KYTE_STDIN));

    echo "\n\nPerfect, and one last, what is the charset? (utf8): ";
    $crosswind_env['db_charset'] = trim(fgets(KYTE_STDIN));

    echo "\n\nAweseome! Your answers have been saved in ".KYTE_CROSSWIND_ENV." so you won't have to keep typing them\n";

    $config_content = <<<EOT
#!/usr/bin/env php
<?php
    \$crosswind_env['kyte_dir'] = '{$crosswind_env['kyte_dir']}';
    \$crosswind_env['db_engine'] = '{$crosswind_env['db_engine']}';
    \$crosswind_env['db_charset'] = '{$crosswind_env['db_charset']}';
EOT;

    // write config file
    file_put_contents(KYTE_CROSSWIND_ENV, $config_content);
} else {
    require_once(KYTE_CROSSWIND_ENV);
}

if (!file_exists($crosswind_env['kyte_dir'].'config.php')) {
    echo "Missing configuration file.  Please create a configuration file with path ".$crosswind_env['kyte_dir'].'config.php'.PHP_EOL;
    exit(-1);
}
require_once($crosswind_env['kyte_dir'].'bootstrap.php');

// init db
// init account
if (isset($argv[1], $argv[2]) ) {

    // init db
    if ($argv[1] == 'init' && $argv[2] == 'db') {
        // load DB lib
        require_once __DIR__.'/lib/Database.php';

        // create db connection sh for convenience
        $content = <<<EOT
#!/usr/bin/bash
mysql -u%s -p%s -h%s %s
EOT;

        file_put_contents($_SERVER['HOME'].'/dbconnect.sh', sprintf($content, KYTE_DB_USERNAME, KYTE_DB_PASSWORD, KYTE_DB_HOST, KYTE_DB_DATABASE));
        echo "Database connection bash script created ({$_SERVER['HOME']}/dbconnect.sh)\n";

        echo "Initializing database...";
        // check if database exists and if not create it
        shell_exec(sprintf("mysql -u%, -p%s -h%s -e 'CREATE DATABASE IF NOT EXISTS %s;'", KYTE_DB_USERNAME, KYTE_DB_PASSWORD, KYTE_DB_HOST, KYTE_DB_DATABASE));
        // TODO: Check return response
        echo sprintf("database %s created\n", KYTE_DB_DATABASE);

        echo "Creating tables...\n";
        $model_sql = \Crosswind\Database::create_tables($crosswind_env['db_charset'], $crosswind_env['db_engine']);
        $sql_stmt = '';

        foreach($model_sql as $stmt) {
            $sql_stmt .= $stmt."\n\n";
        }
        file_put_contents($_SERVER['HOME'].'/schema.sql', $sql_stmt);

        // create tables
        shell_exec(sprintf("mysql -u%, -p%s -h%s %s < schema.sql", KYTE_DB_USERNAME, KYTE_DB_PASSWORD, KYTE_DB_HOST, KYTE_DB_DATABASE));
        // TODO: check return response

        echo "DB initialization complete!\n\n";
        echo "Next, consider running `{$argv[0]} init account` to create API keys, install default roles and permission and setup a Kyte account.\n";
    }

    // init account [Account Name] [User's Name] [email] [password]
    if ($argv[1] == 'init' && $argv[2] == 'account' && isset($argv[3], $argv[4], $argv[5], $argv[6])) {
        echo "Begining account initialization...\n\n";

        // create account
        echo "Creating account...";
        $account = new \Kyte\ModelObject(Account);
        $length=20; //maximum: 32
        do {
            $account_number = substr(md5(uniqid(microtime())),0,$length);
        } while ($account->retrieve('number', $account_number));
        
        if (!$account->create([
            'name'      => $argv[3],
            'number'    => $account_number
        ])) {
            echo "FAILED\n\n";
            exit(-1);
        }
        echo "OK\n\n";
        echo "Account # $account_number created.\n\n";

        echo "Creating API Keys...";
        // create API Keys
        $epoch = time();
        $identifier = uniqid();
        $secret_key = hash_hmac('sha1', $identifier, $epoch);
        $public_key = hash_hmac('sha1', $identifier, $secret_key);

        $apiKey = new \Kyte\ModelObject(APIKey);
        if (!$apiKey->create([
            'identifier' => $identifier,
            'public_key' => $public_key,
            'secret_key' => $secret_key,
            'epoch' => $epoch,
            'kyte_account' => $account_number,
        ])) {
            echo "FAILED\n\n";
            exit(-1);
        }
        echo "OK\n\n";
        echo "Identifier $identifier\n";
        echo "Public Key $public_key\n";
        echo "Secret Key $secret_key\n\n";

        // populate with default Admin role for all models
        echo "Creating new admin role...";
        $role = new \Kyte\ModelObject(Role);
        if (!$role->create([
            'name' => 'Administrator',
            'kyte_account' => $account_number
        ])) {
            echo "FAILED\n\n";
            exit(-1);
        }
        echo "OK\n\n";

        echo "Creating default admin permissions for role.\n";
        foreach (KYTE_MODELS as $model) {
            foreach (['new', 'update', 'get', 'delete'] as $actionType) {
                $permission = new \Kyte\ModelObject(Permission);
                echo "Creating $actionType permission for ".$$model['name']."...";
                if (!$permission->create([
                    'role'  => $role->getParam('id'),
                    'model' => $$model['name'],
                    'action' => $actionType,
                    'kyte_account' => $account_number
                ])) {
                    echo "FAILED\n\n";
                    exit(-1);
                }
                echo "OK\n\n";
            }
        }

        // create user
        echo "Creating new admin user...";
        $role = new \Kyte\ModelObject(User);
        if (!$role->create([
            'name' => $argv[4],
            'email' => $argv[5],
            'password' => password_hash($argv[6], PASSWORD_DEFAULT),
            'kyte_account' => $account_number
        ])) {
            echo "FAILED\n\n";
            exit(-1);
        }
        echo "OK\n\n";

    }
}
