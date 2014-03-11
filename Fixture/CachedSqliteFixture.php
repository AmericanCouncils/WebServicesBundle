<?php

namespace AC\WebServicesBundle\Fixture;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOSqlite\Driver as SqliteDriver;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\ORM\EntityManager;

use \Mockery as m;

abstract class CachedSqliteFixture extends CachedFixture
{
    private $baseSchemaPath = null;
    private $dbTemplatePath = null;
    private $migCodeFiles = null;
    private $objMan = null;

    final public function loadInto($container)
    {
        if (!is_dir(".tmp")) {
            mkdir(".tmp");
        }
        if (is_null($this->baseSchemaPath)) {
            $this->setupSchemaTemplate();
        }
        if (is_null($this->dbTemplatePath)) {
            $this->setupFixtureTemplate($container->get('doctrine')->getManager());
        }

        $dbPath = ".tmp/testing.sqlite3";
        copy($this->dbTemplatePath, $dbPath);
    }

    final protected function getFixtureObjectManager()
    {
        return $this->objMan;
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
                $template = $this->connectDb($this->baseSchemaPath);

                $sql = [];
                foreach ($this->migrationCodeFiles() as $codePath) {
                    require_once($codePath);
                    $migName = pathinfo($codePath, PATHINFO_FILENAME);
                    $migClass = 'Application\Migrations\\' . $migName;

                    // We want to get at the up() method of the migration without
                    // having to pass its constructor our migration configuration.
                    $migMock = m::mock($migClass)->shouldDeferMissing();

                    $versionMock = m::mock('\Doctrine\DBAL\Migrations\Version');
                    $versionMock->shouldReceive('addSql')->andReturnUsing(
                        function ($statements, $params = [], $types = []) use (&$sql) {
                            if (count($params) > 0 || count($types) > 0) {
                                throw new \RuntimeException(
                                    "AliceSqliteFixtureCache can't do addSql calls with params"
                                );
                            }
                            if (!is_array($statements)) {
                                $statements = [$statements];
                            }
                            foreach ($statements as $s) {
                                $sql[] = $s;
                            }
                        }
                    );
                    $versionInserter = function ($mig, $ver) { $mig->version = $ver; };
                    $versionInserter = \Closure::bind($versionInserter, null, $migMock);
                    $versionInserter($migMock, $versionMock);

                    // up() can set up tables via Version->addSql and/or passed $schema
                    $dummySchema = new Schema;
                    $migMock->up($dummySchema);
                    $sql = array_merge($sql, $dummySchema->toSql(new SqlitePlatform));
                }
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
        $this->dbTemplatePath = ".tmp/$clsName.sqlite3";

        try {
            copy($this->baseSchemaPath, $this->dbTemplatePath);
            $this->objMan = EntityManager::create(
                $this->connectDb(
                    $this->dbTemplatePath,
                    $mainEM->getConfiguration(),
                    $mainEM->getEventManager()
                ),
                $mainEM->getConfiguration(),
                $mainEM->getEventManager()
            );
            $this->execFixture();
            $this->objMan = null;
        } catch (\Exception $e) {
            if (file_exists($this->dbTemplatePath)) {
                unlink($this->dbTemplatePath);
            }
            throw $e;
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
