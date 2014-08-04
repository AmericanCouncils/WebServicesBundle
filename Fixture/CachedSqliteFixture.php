<?php

namespace AC\WebServicesBundle\Fixture;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOConnection;
use Doctrine\DBAL\Driver\PDOSqlite\Driver as SqliteDriver;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

use \Mockery as m;

abstract class CachedSqliteFixture extends CachedFixture
{
    private $baseSchemaPath = null;
    private $dbTemplatePath = null;
    private $migCodeFiles = null;
    private $templateEM = null;

    final protected function loadImpl($container)
    {
        $mainEM = $container->get('doctrine')->getManager();
        $conn = $mainEM->getConnection();
        if (!$conn->getDatabasePlatform() instanceof SqlitePlatform) {
            throw new \RuntimeException("Cannot load sqlite fixture into non-sqlite database");
        }

        if (!is_dir(".tmp")) {
            mkdir(".tmp");
        }
        if (is_null($this->baseSchemaPath)) {
            $this->setupSchemaTemplate();
        }
        if (is_null($this->dbTemplatePath)) {
            $this->dbTemplatePath = $this->setupFixtureTemplate($mainEM);
        }

        $dbPath = $conn->getDatabase();
        if (!is_dir(dirname($dbPath))) {
            mkdir(dirname($dbPath), 0777, true);
        }
        copy($this->dbTemplatePath, $dbPath);
    }

    final protected function getFixtureObjectManager()
    {
        return $this->templateEM;
    }

    private function setupSchemaTemplate()
    {
        $schemaSource = "";
        foreach ($this->migrationCodeFiles() as $codePath) {
            $schemaSource .= file_get_contents($codePath);
        }
        $schemaHash = md5($schemaSource);
        $this->baseSchemaPath = ".tmp/BaseSchema_" . $schemaHash . ".sqlite3";
        if (file_exists($this->baseSchemaPath)) {
            return;
        }

        $this->withLoadingMessage("building schema template", function () {
            try {
                $schema = new Schema;
                $addedSql = [];
                foreach ($this->migrationCodeFiles() as $codePath) {
                    require_once($codePath);
                    $migName = pathinfo($codePath, PATHINFO_FILENAME);
                    $migClass = 'Application\Migrations\\' . $migName;

                    // We want to get at the up() method of the migration without
                    // having to pass its constructor our migration configuration.
                    $migMock = m::mock($migClass)->shouldDeferMissing();

                    // Force set the platform, even though it wasn't loaded from a configuration
                    $migReflector = new \ReflectionProperty($migClass, "platform");
                    $migReflector->setAccessible(true);
                    $migReflector->setValue($migMock, new SqlitePlatform);

                    $versionMock = m::mock('\Doctrine\DBAL\Migrations\Version');
                    $versionMock->shouldReceive('addSql')->andReturnUsing(
                        function ($statements, $params = [], $types = []) use (&$addedSql) {
                            if (count($params) > 0 || count($types) > 0) {
                                throw new \RuntimeException(
                                    "AliceSqliteFixtureCache can't do addSql calls with params"
                                );
                            }
                            if (!is_array($statements)) {
                                $statements = [$statements];
                            }
                            foreach ($statements as $s) {
                                $addedSql[] = $s;
                            }
                        }
                    );
                    $versionInserter = function ($mig, $ver) { $mig->version = $ver; };
                    $versionInserter = \Closure::bind($versionInserter, null, $migMock);
                    $versionInserter($migMock, $versionMock);

                    $migMock->up($schema);
                }

                $sql = array_merge($schema->toSql(new SqlitePlatform), $addedSql);
                $template = $this->connectDb($this->baseSchemaPath);
                $template->exec(implode(";\n", $sql));
            } catch (\Exception $e) {
                if (file_exists($this->baseSchemaPath)) {
                    unlink($this->baseSchemaPath);
                }
                throw $e;
            }
        });
    }

    private function setupFixtureTemplate($mainEM)
    {
        $clsName = str_replace("\\", "__", get_called_class());
        $templatePath = ".tmp/$clsName.sqlite3";

        try {
            copy($this->baseSchemaPath, $templatePath);
            $this->templateEM = EntityManager::create(
                $this->connectDb(
                    $templatePath,
                    $mainEM->getConfiguration(),
                    $mainEM->getEventManager()
                ),
                $mainEM->getConfiguration(),
                $mainEM->getEventManager()
            );
            $this->execFixture();
            $this->templateEM = null;
        } catch (\Exception $e) {
            if (file_exists($templatePath)) {
                unlink($templatePath);
            }
            throw $e;
        }

        return $templatePath;
    }

    final protected function getNamespaceAliases($objMan)
    {
        return $objMan->getConfiguration()->getEntityNamespaces();
    }

    final protected function prePersist($obj)
    {
        # Update reverse associations as needed
        $meta = $this->templateEM->getClassMetadata(get_class($obj));
        foreach ($meta->associationMappings as $field => $assoc) {
            $mappedBy = $assoc['mappedBy'];
            if (is_null($mappedBy)) { continue; }
            $data = call_user_func([$obj, "get" . ucfirst($field)]);
            if (is_null($data)) { continue; }
            foreach ($data as $subObj) {
                $mapping = call_user_func([$subObj, "get" . ucfirst($mappedBy)]);
                if (is_null($mapping)) {
                    call_user_func([$subObj, "set" . ucfirst($mappedBy)], $obj);
                    $this->templateEM->persist($subObj);
                }
            }
        }
    }

    private function migrationCodeFiles()
    {
        if (is_null($this->migCodeFiles)) {
            $this->migCodeFiles = glob("app/DoctrineMigrations/Version*.php");
            sort($this->migCodeFiles);
        }

        return $this->migCodeFiles;
    }

    private function connectDb($path, $config = null, $eventManager = null)
    {
        $c = new Connection(["path" => $path], new SqliteDriver, $config, $eventManager);
        $c->exec("PRAGMA journal_mode=MEMORY");
        $c->exec("PRAGMA synchronous=OFF");

        return $c;
    }
}
