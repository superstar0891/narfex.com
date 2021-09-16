<?php

namespace Core\Command;

use Db\Db;
use Db\Exception as DbException;
use Db\Model\Exception as ModelException;
use Db\Model\Field;
use Db\Model\Model;
use Db\Transaction;
use Db\Where;
use Exception;
use Migrations;

class Migrate implements CommandInterface {
    private $prefix;

    private $db;

    /**
     * Migrate constructor.
     *
     * @param string|null $prefix
     * @param string $db
     */
    function __construct(string $prefix = null, string $db = 'mysql') {
        $this->prefix = $prefix ?: '';
        $this->db = $db;
    }

    public function exec() {
        if (!$this->isInitialized()) {
            $this->initialize();
        }

        $migrations = $this->getPendingMigrations();

        foreach ($migrations as $migration) {
            $this->migrate($migration);
        }
    }

    /**
     * @return bool
     * @throws DbException\DbAdapterException
     * @throws ModelException\TableNameUndefinedException
     */
    private function isInitialized(): bool {
        return Db::hasTable(MigrationModel::getTableName());
    }

    /**
     * @throws ModelException\ModelUndefinedFieldsException
     */
    private function initialize() {
        $fields = MigrationModel::getFields();

        $mig = new MigrationModel();
        $mig->prefix = '';
        $mig->name = 'Initial';

        Transaction::wrap(function () use ($fields, $mig) {
            Db::createTable(MigrationModel::getTableName(), $fields, MigrationModel::hasDefaultFields());
            $mig->save();
        });

        echo "Migration `Initial` done! \n";
    }

    /**
     * @return array
     * @throws DbException\DbAdapterException
     * @throws DbException\InvalidSelectQueryException
     * @throws DbException\InvalidWhereOperatorException
     * @throws ModelException\TableNameUndefinedException
     */
    private function getPendingMigrations(): array {
        $all_migrations = [];

        $migration_folder = $this->db === Migration::DB_CLICK_HOUSE ? 'ClickHouseMigrations' : 'Migrations';
        $migrations_dir_path = KERNEL_CONFIG['root'];
        $migrations_dir_path .= '/' . KERNEL_CONFIG['autoloader']['prefixes'][$migration_folder];
        $migrations_dir_path .= "/{$this->prefix}/";

        if (is_dir($migrations_dir_path)) {
            if ($dh = opendir($migrations_dir_path)) {
                while (($file_path = readdir($dh)) !== false) {
                    $migration_file_path = $migrations_dir_path . $file_path;
                    if (is_file($migration_file_path) && substr($file_path, 0, 10) == 'Migration_') {
                        list($migration_name, $ext) = explode('.', $file_path);

                        $all_migrations[] = $migration_name;
                    }
                }
                closedir($dh);
            }
        }

        $applied_migrations = MigrationModel::select(Where::and()
            ->set('prefix', Where::OperatorEq, $this->prefix)
            ->set('db', Where::OperatorEq, $this->db)
        );
        $applied_migrations_names = $applied_migrations->column('name');

        $pending_migrations = array_diff($all_migrations, $applied_migrations_names);

        sort($pending_migrations);

        return $pending_migrations;
    }

    /**
     * @param string $name
     *
     * @throws Exception
     */
    private function migrate(string $name) {
        $migration_folder = $this->db === Migration::DB_CLICK_HOUSE ? 'ClickHouseMigrations' : 'Migrations';
        $migration_name = $migration_folder . '\\';
        if ($this->prefix) {
            $migration_name .= "{$this->prefix}\\";
        }
        $migration_name .= "{$name}";

        echo "Making migration `{$migration_name}`... \n";

        $up_callable = $migration_name . '::up';

        $mig = new MigrationModel();
        $mig->prefix = $this->prefix;
        $mig->name = $name;
        $mig->db = $this->db;

        Transaction::wrap(function () use ($up_callable, $mig) {
            $up_callable();
            $mig->save();
        });

        echo "Migration `{$migration_name}` done! \n";
    }
}

class MigrationModel extends Model {
    protected static $table_name = 'core_migrations';

    protected static $has_default_fields = false;

    protected static $fields = [];

    protected static function fields(): array {
        return [
            'prefix' => Field\CharField::init()->setLength(256),
            'name' => Field\CharField::init()->setLength(256),
            'db' => Field\CharField::init()->setLength(256),
            'applied_at' => Field\CreatedAtField::init(),
        ];
    }
}
