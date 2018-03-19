<?php //-->
/**
 * This file is part of a Custom Package.
 */

use Cradle\Storm\SqlFactory;

use Cradle\Package\System\Schema;
use Cradle\Package\System\Exception;

/**
 * $ cradle package install cradlephp/cradle-auth
 * $ cradle package install cradlephp/cradle-auth 1.0.0
 * $ cradle cradlephp/cradle-auth install
 * $ cradle cradlephp/cradle-auth install 1.0.0
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('cradlephp-cradle-auth-install', function ($request, $response) {
    //custom name of this package
    $name = 'cradlephp/cradle-auth';

    //if it's already installed
    if ($this->package('global')->config('version', $name)) {
        $message = sprintf('%s is already installed', $name);
        return $response->setError(true, $message);
    }

    // install package
    $version = $this->package('cradlephp/cradle-auth')->install('0.0.0');

    // update the config
    $this->package('global')->config('version', $name, $version);
    $response->setResults('version', $version);
});

/**
 * $ cradle package update cradlephp/cradle-auth
 * $ cradle package update cradlephp/cradle-auth 1.0.0
 * $ cradle cradlephp/cradle-auth update
 * $ cradle cradlephp/cradle-auth update 1.0.0
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('cradlephp-cradle-auth-update', function ($request, $response) {
    //custom name of this package
    $name = 'cradlephp/cradle-auth';

    //get the current version
    $current = $this->package('global')->config('version', $name);

    //if it's not installed
    if (!$current) {
        $message = sprintf('%s is not installed', $name);
        return $response->setError(true, $message);
    }

    // get available version
    $version = $this->package($name)->version();

    //if available <= current
    if (version_compare($version, $current, '<=')) {
        $message = sprintf('%s %s <= %s', $name, $version, $current);
        return $response->setError(true, $message);
    }

    // update package
    // install package
    $version = $this->package('cradlephp/cradle-auth')->install($current);

    // update the config
    $this->package('global')->config('versions', $name, $version);
    $response->setResults('version', $version);
});

/**
 * $ cradle package remove cradlephp/cradle-auth
 * $ cradle cradlephp/cradle-auth remove
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('cradlephp-cradle-auth-remove', function ($request, $response) {
    //setup result counters
    $errors = [];

    //scan through each file
    foreach (scandir(__DIR__ . '/schema') as $file) {
        //if it's not a php file
        if(substr($file, -4) !== '.php') {
            //skip
            continue;
        }

        //get the schema data
        $data = include sprintf('%s/schema/%s', __DIR__, $file);

        //if no name
        if (!isset($data['name'])) {
            //skip
            continue;
        }

        //----------------------------//
        // 1. Prepare Data
        $request->setStage('schema', $data['name']);

        //----------------------------//
        // 2. Process Request
        $this->trigger('system-schema-remove', $request, $response);

        //----------------------------//
        // 3. Interpret Results
        if ($response->isError()) {
            //collect all the errors
            $errors[$data['name']] = $response->getMessage();
            continue;
        }

        $processed[] = $data['name'];
    }

    if (!empty($errors)) {
        $response->set('json', 'validation', $errors);
    }

    $response->setResults('schemas', $processed);
});

/**
 * $ cradle elastic flush cradlephp/cradle-auth
 * $ cradle cradlephp/cradle-auth elastic-flush
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('cradlephp-cradle-auth-elastic-flush', function ($request, $response) {});

/**
 * $ cradle elastic map cradlephp/cradle-auth
 * $ cradle cradlephp/cradle-auth elastic-map
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('cradlephp-cradle-auth-elastic-map', function ($request, $response) {});

/**
 * $ cradle elastic populate cradlephp/cradle-auth
 * $ cradle cradlephp/cradle-auth elastic-populate
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('cradlephp-cradle-auth-elastic-populate', function ($request, $response) {});

/**
 * $ cradle redis flush cradlephp/cradle-auth
 * $ cradle cradlephp/cradle-auth redis-flush
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('cradlephp-cradle-auth-redis-flush', function ($request, $response) {});

/**
 * $ cradle redis populate cradlephp/cradle-auth
 * $ cradle cradlephp/cradle-auth redis-populate
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('cradlephp-cradle-auth-redis-populate', function ($request, $response) {});

/**
 * $ cradle sql build cradlephp/cradle-auth
 * $ cradle cradlephp/cradle-auth sql-build
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('cradlephp-cradle-auth-sql-build', function ($request, $response) {
    //load up the database
    $pdo = $this->package('global')->service('sql-main');
    $database = SqlFactory::load($pdo);

    //setup result counters
    $errors = [];
    $processed = [];

    //scan through each file
    foreach (scandir(__DIR__ . '/schema') as $file) {
        //if it's not a php file
        if(substr($file, -4) !== '.php') {
            //skip
            continue;
        }

        //get the schema data
        $data = include sprintf('%s/schema/%s', __DIR__, $file);

        //if no name
        if (!isset($data['name'])) {
            //skip
            continue;
        }

        try {
            $schema = Schema::i($data['name']);
        } catch(Exception $e) {
            continue;
        }

        //remove primary table
        $database->query(sprintf('DROP TABLE IF EXISTS `%s`', $schema->getName()));

        //loop through relations
        foreach ($schema->getRelations() as $table => $relation) {
            //remove relation table
            $database->query(sprintf('DROP TABLE IF EXISTS `%s`', $table));
        }

        //now build it back up
        //set the data
        $request->setStage($data);

        //----------------------------//
        // 1. Prepare Data
        //if detail has no value make it null
        if ($request->hasStage('detail')
            && !$request->getStage('detail')
        ) {
            $request->setStage('detail', null);
        }

        //if fields has no value make it an array
        if ($request->hasStage('fields')
            && !$request->getStage('fields')
        ) {
            $request->setStage('fields', []);
        }

        //if validation has no value make it an array
        if ($request->hasStage('validation')
            && !$request->getStage('validation')
        ) {
            $request->setStage('validation', []);
        }

        //----------------------------//
        // 2. Process Request
        //now trigger
        $this->trigger('system-schema-update', $request, $response);

        //----------------------------//
        // 3. Interpret Results
        //if the event returned an error
        if ($response->isError()) {
            //collect all the errors
            $errors[$data['name']] = $response->getValidation();
            continue;
        }

        $processed[] = $data['name'];
    }

    if (!empty($errors)) {
        $response->set('json', 'validation', $errors);
    }

    $response->setResults('schemas', $processed);
});

/**
 * $ cradle sql flush cradlephp/cradle-auth
 * $ cradle cradlephp/cradle-auth sql-flush
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('cradlephp-cradle-auth-sql-flush', function ($request, $response) {
    //load up the database
    $pdo = $this->package('global')->service('sql-main');
    $database = SqlFactory::load($pdo);

    //setup result counters
    $errors = [];
    $processed = [];

    //scan through each file
    foreach (scandir(__DIR__ . '/schema') as $file) {
        //if it's not a php file
        if(substr($file, -4) !== '.php') {
            //skip
            continue;
        }

        //get the schema data
        $data = include sprintf('%s/schema/%s', __DIR__, $file);

        //if no name
        if (!isset($data['name'])) {
            //skip
            continue;
        }

        try {
            $schema = Schema::i($data['name']);
        } catch(Exception $e) {
            continue;
        }

        //remove primary table
        $database->query(sprintf('TRUNCATE `%s`', $schema->getName()));

        //loop through relations
        foreach ($schema->getRelations() as $table => $relation) {
            //remove relation table
            $database->query(sprintf('TRUNCATE `%s`', $table));
        }

        $processed[] = $data['name'];
    }

    $response->setResults('schemas', $processed);
});

/**
 * $ cradle sql populate cradlephp/cradle-auth
 * $ cradle cradlephp/cradle-auth sql-populate
 *
 * @param Request $request
 * @param Response $response
 */
$this->on('cradlephp-cradle-auth-sql-populate', function ($request, $response) {
    //load up the database
    $pdo = $this->package('global')->service('sql-main');
    $database = SqlFactory::load($pdo);
    //load up the script
    $script = file_get_contents(__DIR__ . '/install/populate.sql');
    //split into queries
    $queries = explode(';', $script);
    //loop through queries
    foreach($queries as $query) {
        //trim it
        $query = trim($query);

        if(!$query) {
            continue;
        }

        //execute the query
        $database->query($query);
    }
});
