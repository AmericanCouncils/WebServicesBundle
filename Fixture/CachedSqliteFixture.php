<?php

namespace AC\WebServicesBundle\Fixture;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOConnection;
use Doctrine\DBAL\Driver\PDOSqlite\Driver as SqliteDriver;
use Doctrine\DBAL\Driver\PDOMySql\Driver as MySqlDriver;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\SqliteSchemaManager;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Tools\SchemaTool;

use \Mockery as m;
use \Functional as F;

abstract class CachedSqliteFixture extends CachedFixture
{
    private $cacheDir = null;
    private $baseSchemaPath = null;
    private $dbTemplatePath = null;
    private $migCodeFiles = null;
    private $templateEM = null;

    final protected function loadImpl($container)
    {
        $this->cacheDir = $container->getParameter("kernel.cache_dir") . "/cached_sqlite_fixture";

        $mainEM = $container->get('doctrine')->getManager();
        $conn = $mainEM->getConnection();
        $platform = $conn->getDatabasePlatform();
        if ($platform instanceof MySqlPlatform) {
            print "\nAbout to overwrite MySQL database! If you're sure, type \"yes\":\n";
            $line = fgets(STDIN);
            if ($line == "yes\n") {
                return $this->overwriteMysqlData($mainEM);
            } else {
                print "Fixture load cancelled.\n";
                return;
            }
        } elseif (!($platform instanceof SqlitePlatform)) {
            throw new \RuntimeException("Cannot load fixture into this SQL database type");
        }

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
        if (is_null($this->baseSchemaPath)) {
            $this->setupSchemaTemplate($mainEM);
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

    private function overwriteMysqlData($mainEM)
    {
        $fileMigs = F\map(glob("app/DoctrineMigrations/Version*.php"), function($filename) {
            if (preg_match("#Version(\d+).php$#", $filename, $matches)) {
                return $matches[1];
            } else {
                throw new \RuntimeException("Invalid migration file $filename");
            }
        });

        $conn = $mainEM->getConnection();
        $dbMigs = F\pluck($conn->fetchAll("SELECT * FROM migration_versions"), 'version');

        if (count(array_diff($dbMigs, $fileMigs)) > 0) {
            throw new \RuntimeException("MySQL database has unknown migrations");
        } elseif (count(array_diff($fileMigs, $dbMigs)) > 0) {
            throw new \RuntimeException("MySQL database needs to be migrated first");
        }

        $conn->query('SET FOREIGN_KEY_CHECKS=0');
        $tables = F\map($conn->fetchAll("SHOW TABLES"), function ($row) {
            return F\first($row);
        });
        foreach ($tables as $tableName) {
            if ($tableName == 'migration_versions') { continue; }
            $conn->executeUpdate($conn->getDatabasePlatform()->getTruncateTableSql($tableName));
        }
        $conn->query('SET FOREIGN_KEY_CHECKS=1');

        $this->templateEM = $mainEM;
        $this->execFixture();
        $this->templateEM = null;
    }

    private function setupSchemaTemplate($mainEM)
    {
        $tool = new SchemaTool($mainEM);
        $schemaSql = $tool->getCreateSchemaSql($mainEM->getMetadataFactory()->getAllMetadata());
        $schemaHash = md5(var_export($schemaSql, true));
        $this->baseSchemaPath = $this->cacheDir . "/BaseSchema_" . $schemaHash . ".sqlite3";
        if (file_exists($this->baseSchemaPath)) {
            return;
        }

        $this->withLoadingMessage("building schema template", function () use ($schemaSql) {
            try {
                $templateDb = $this->connectDb($this->baseSchemaPath);
                $templateDb->exec(implode(";\n", $schemaSql));
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
        $templatePath = $this->cacheDir . "/$clsName.sqlite3";

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
