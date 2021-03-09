<?php
/**
 * PhpStorm specific doc.
 * @noinspection SqlNoDataSourceInspection
 */
namespace lib\woocommerce_reports\models;

/**
 * An abstract class used to define several key properties and methods for implementing classes.
 * Makes it easier to create a new database model and map it to an object.
 *
 * Class Model
 * @package lib\woocommerce_reports\models
 * @author Chris van Zanten <chris@vazquez.nl>
 */
abstract class Model {
    /**
     * Table name used for the model.
     *
     * @var string
     */
    protected static $DB_TABLE;

    /**
     * Array structure that defines the layout of the database table.
     *
     * @var array
     */
    protected static $DB_STRUCTURE;

    /**
     * Data store for the values of each property.
     *
     * @var array
     */
    protected $values = [];

    /**
     * Primary key in the table which is unique and autoincrement.
     *
     * @var int
     */
    public $Id;

    /**
     * Converts the name to snake_case and then checks if it exists in the $DB_STRUCTURE property.
     * Should it exist, then the value is taken from the $values property.
     *
     * @param $name - Name of the property.
     * @return mixed
     */
    public function __get($name) {
        $snakeName = self::name_to_snake_case($name);
        if (key_exists($snakeName, static::$DB_STRUCTURE) && key_exists($snakeName, $this->values)) {
            return $this->values[$snakeName];
        } else return null;
    }

    /**
     * Converts the name to snake_case and then checks if it exists in the $DB_STRUCTURE property.
     *
     * @param $name - Name of the property.
     * @return boolean - true when the property exists, false if otherwise.
     */
    public function __isset($name) {
        $snakeName = self::name_to_snake_case($name);
        if (key_exists($snakeName, static::$DB_STRUCTURE) && key_exists($snakeName, $this->values)) {
            return true;
        } else return false;
    }

    /**
     * Converts the name to snake_case and then checks if it exists in the $DB_STRUCTURE property.
     * Should it exist, then the value is written to the $values property with the key equal to the snake_case name.
     *
     * @param $name - Name of the property.
     * @param $value - Value of the property.
     * @throws \Exception - When the name is not in the database structure.
     */
    public function __set($name, $value) {
        $snakeName = self::name_to_snake_case($name);
        if (key_exists($snakeName, static::$DB_STRUCTURE)) {
            $this->values[$snakeName] = $value;
        } else {
            throw new \Exception("Could not assign a value to property with name: {$name} because it has no equivalent in the database structure.");
        }
    }

    /**
     * Saves this Model to the database.
     * Updates the existing Model should it exist or creates a new one should it not exist.
     *
     * @return null || mixed - The Id or null should the save fail.
     */
    public function save() {
        global $wpdb;
        if (isset($this->Id)) {
            // Update the Model.
            $result = $wpdb->update(static::$DB_TABLE, $this->values, ['id' => $this->Id]);
            return $result === false ? null : $this->Id;
        } else {
            // Insert the Model.
            $result = $wpdb->insert(static::$DB_TABLE, $this->values);
            return $result === false ? null : $wpdb->insert_id;
        }
    }

    /**
     * Parses a wpdb associative array row into the properties on this model.
     *
     * @param $row - An associative array row from a wpdb query.
     * @throws \Exception - When something goes wrong while parsing the row.
     */
    public function parse_row($row) {
        if (empty($row) || !is_array($row)) {
            throw new \Exception("The provided row is empty or not in a valid format.");
        };
        foreach ($row as $field => $value) {
            if ($field === 'id') $this->Id = $value;
            else $this->__set($field, $value);
        }
    }

    /**
     * Creates or updates the table structure in the database for the Model.
     * Uses the structure defined in the DB_STRUCTURE property.
     * NOTE: Should be called in the actions.php file when migration is needed.
     *
     * @return bool - true when the migrate succeeded, false if otherwise.
     */
    public static function migrate() {
        // Check if the table already exists.
        if (empty(static::$DB_TABLE) || empty(static::$DB_STRUCTURE)) {
            error_log("Either the DB_TABLE or DB_STRUCTURE property is empty.");
            return false;
        }
        global $wpdb;
        if ($wpdb->query(sprintf("SELECT 1 FROM %s LIMIT 1;", static::$DB_TABLE)) !== false) {
            // Table exists, check if an update is needed.
            $tableStructure = get_site_option(sprintf("migration_structure_%s", static::$DB_TABLE));
            if ($tableStructure === false) {
                error_log(sprintf("There is no valid migration structure for table: %s in the database. Adding all columns as currently defined.", static::$DB_TABLE));
                $tableStructure = [];
            }
            // For each column in DB_STRUCTURE, check if it is the same as currently in the database, if not, modify it.
            $statements = [];
            if (!empty(static::$DB_STRUCTURE)) {
                foreach (static::$DB_STRUCTURE as $column => $parameters) {
                    $newDefinition = self::create_column_definition($column, $parameters);
                    if ($newDefinition === false) return false;
                    $operation = "ADD";
                    if (key_exists($column, $tableStructure)) {
                        // Compare definitions.
                        $oldDefinition = self::create_column_definition($column, $tableStructure[$column]);
                        if ($oldDefinition === false) {
                            error_log(sprintf("The old definition for table: %s and column: %s is no longer valid. Will update to the new definition.", static::$DB_TABLE, $column));
                        } else {
                            if ($oldDefinition['definition'] === $newDefinition['definition'] && $oldDefinition['index'] === $newDefinition['index']) continue;
                            $operation = ($oldDefinition['definition'] !== $newDefinition['definition']) ? "MODIFY" : null;
                            if ($oldDefinition['index'] !== $newDefinition['index']) {
                                if ($oldDefinition['index'] !== '') {
                                    $statements[] = sprintf("DROP INDEX ind_%s", $column);
                                }
                                if ($newDefinition['index'] !== '') {
                                    $statements[] = sprintf('ADD %1$s ind_%2$s (%2$s)', $newDefinition['index'], $column);
                                }
                            }
                        }
                    } else {
                        if ($newDefinition['index'] !== '') {
                            $statements[] = sprintf('ADD %1$s ind_%2$s (%2$s)', $newDefinition['index'], $column);
                        }
                    }
                    if ($operation === null) continue;
                    // Construct and add the statement.
                    $statements[] = sprintf("%s %s", $operation, $newDefinition['definition']);
                }
            }
            // Create DROP COLUMN statements for each column that is in the old table structure and not in the new one.
            $removedColumns = array_diff_key($tableStructure, empty(static::$DB_STRUCTURE) ? [] : static::$DB_STRUCTURE);
            if (!empty($removedColumns)) {
                foreach ($removedColumns as $column => $parameters) {
                    $statements[] = sprintf("DROP COLUMN %s", $column);
                }
            }
            // Construct and run the query.
            $query = sprintf("ALTER TABLE %s %s;", static::$DB_TABLE, implode(', ', $statements));
            if ($wpdb->query($query) === false) {
                error_log(sprintf("An error occurred while trying to alter the table: %s.", static::$DB_TABLE));
                error_log($wpdb->last_error);
                return false;
            }
        } else {
            // Table doesn't exist, so create a new one.
            $query = sprintf("CREATE TABLE IF NOT EXISTS %s (", static::$DB_TABLE);
            $statements = [];
            $indices = [];
            // Add 'Id' column.
            $statements[] = "id INT(11) unsigned NOT NULL AUTO_INCREMENT";
            if (!empty(static::$DB_STRUCTURE)) {
                foreach (static::$DB_STRUCTURE as $column => $parameters) {
                    $newDefinition = self::create_column_definition($column, $parameters);
                    if ($newDefinition === false) return false;
                    $statements[] = $newDefinition['definition'];
                    if ($newDefinition['index'] !== "") $indices[$column] = $newDefinition['index'];
                }
            }
            // Add the primary key to the end.
            $statements[] = "PRIMARY KEY (id)";
            // Add the query elements to the query string.
            $query .= implode(', ', $statements) . ");";
            // Run the query and check if the table got created successfully.
            if ($wpdb->query($query) === false) {
                error_log(sprintf("An error occurred while trying to create the table: %s.", static::$DB_TABLE));
                error_log($wpdb->last_error);
                return false;
            }
            // Set the indices if there are any.
            if (!empty($indices)) {
                foreach ($indices as $column => $index) {
                    $query = sprintf('CREATE %1$s ind_%3$s ON %2$s (%3$s);', $index, static::$DB_TABLE, $column);
                    if ($wpdb->query($query) === false) {
                        error_log(sprintf("An error occurred while trying to create an index for column: %s on table: %s.", $column, static::$DB_TABLE));
                        error_log($wpdb->last_error);
                        // Drop the previously created table.
                        $query = sprintf("DROP TABLE %s;", static::$DB_TABLE);
                        if ($wpdb->query($query) === false) {
                            error_log(sprintf("An error occurred while trying to drop table: %s.", static::$DB_TABLE));
                            error_log($wpdb->last_error);
                        }
                        return false;
                    }
                }
            }
        }
        // Update/Create the migration_hash and migration_structure meta keys for this table.
        $hash = md5(serialize(static::$DB_STRUCTURE));
        update_site_option(sprintf("migration_hash_%s", static::$DB_TABLE), $hash);
        update_site_option(sprintf("migration_structure_%s", static::$DB_TABLE), static::$DB_STRUCTURE);
        error_log(sprintf("Migrated table: %s with hash: %s.", static::$DB_TABLE, $hash));
        return true;
    }

    /**
     * Returns whether or not this Model needs to be migrated.
     * NOTE: Should be called in the actions.php file.
     *
     * @return bool - true when migration is needed, false if otherwise.
     */
    public static function needs_migration() {
        $currentHash = md5(serialize(static::$DB_STRUCTURE));
        $dbHash = get_site_option(sprintf("migration_hash_%s", static::$DB_TABLE));
        if ($currentHash === $dbHash) return false;
        return true;
    }

    /**
     * Retrieves all Model instances from the database for which there are id's.
     *
     * @param $ids - An array of positive integer id's.
     * @return static[] || null - An array of Model instances or null if something went wrong.
     */
    public static function get_by_ids($ids) {
        if (empty($ids)) return null;
        $modelInstances = [];
        $query = sprintf("SELECT * FROM %s WHERE id IN (%s)", static::$DB_TABLE, implode(',', $ids));
        global $wpdb;
        $rows = $wpdb->get_results($query, ARRAY_A);
        if (!isset($rows)) return null;
        if (!empty($rows)) {
            foreach ($rows as $row) {
                // Create a new Model object for each of the rows returned.
                $modelInstance = new static();
                try {
                    $modelInstance->parse_row($row);
                } catch (\Exception $exc) {
                    error_log($exc->getMessage());
                    error_log($exc->getTraceAsString());
                    return null;
                }
                $modelInstances[] = $modelInstance;
            }
        }
        return $modelInstances;
    }

    /**
     * Converts a property name that is in PascalCase to snake_case.
     *
     * @param string $name - Property name that is in PascalCase.
     * @return string - A snake_case version of the property name
     */
    private static function name_to_snake_case($name) {
        return ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $name)), '_');
    }

    /**
     * Converts a database field name that is in snake_case to PascalCase.
     *
     * @param string $name - Name of a database field in snake_case.
     * @return string - A PascalCase version of the field name.
     */
    private static function field_to_pascal_case($name) {
        return str_replace('_', '', ucwords($name, '_'));
    }

    /**
     * Creates a mysql valid column definition string that can be used while creating/altering the table.
     *
     * @param $column - The column name.
     * @param $parameters - The parameters of the column. Can be the following:
     *  <type> The type of the column with its size. BIGINT(20). DATE.
     *  [unsigned] - true or false, whether or not to make the column unsigned. Default: false.
     *  [allow_null] - true or false, whether or not to make the column allow null. Default: false.
     *  [default] - The default value for the column. Default: none.
     *  [auto_increment] - true or false, whether or not the make the column auto increment. Default: false.
     *  [index] - 'unique' or 'index', whether or not to add a (unique) index to the column. Default: none.
     * @return array | false - The column definition with index or false if something went wrong.
     */
    private static function create_column_definition($column, $parameters) {
        $columnElements = [ $column ];
        $index = "";
        if (!isset($parameters['type'])) {
            error_log("The type parameter has not been set for column {$column}.");
            return false;
        }
        $columnElements[] = $parameters['type'];
        if (isset($parameters['unsigned']) && $parameters['unsigned'] === true) $columnElements[] = 'unsigned';
        if (isset($parameters['allow_null']) && $parameters['allow_null'] === false) $columnElements[] = 'NOT NULL';
        if (isset($parameters['default'])) $columnElements[] = sprintf("DEFAULT '%s'", $parameters['default']);
        if (isset($parameters['auto_increment']) && $parameters['auto_increment'] === true) $columnElements[] = 'AUTO_INCREMENT';
        if (isset($parameters['index'])) {
            if ($parameters['index'] === 'unique') $index = 'UNIQUE INDEX';
            else if ($parameters['index'] === 'index') $index = 'INDEX';
        }
        return ['definition' => implode(" ", $columnElements), 'index' => $index];
    }
}