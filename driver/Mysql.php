<?php
declare(strict_types=1);
/*
 * Copyright (c) 2023. Ankio. All Rights Reserved.
 */

/**
 * Package: nova\plugin\orm\driver
 * Class Mysql
 * Created By ankio.
 * Date : 2022/11/14
 * Time : 23:14
 * Description :
 */

namespace nova\plugin\orm\driver;

use nova\plugin\orm\exception\DbConnectError;
use nova\plugin\orm\object\DbFile;
use nova\plugin\orm\object\Model;
use nova\plugin\orm\object\SqlKey;
use PDO;
use PDOException;

class Mysql extends Driver
{

    private DbFile $dbFile;

    /**
     * @throws DbConnectError
     */
    public function __construct(DbFile $dbFile)
    {
        if ($dbFile->charset === "utf8")
            $dbFile->charset = "utf8mb4";
        $this->dbFile = $dbFile;
        //pdo初始化
        try {
            $this->pdo = new PDO("mysql:host={$this->dbFile->host};port={$this->dbFile->port};dbname={$this->dbFile->db}",
                $this->dbFile->username,
                $this->dbFile->password,
                [
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'' . $this->dbFile->charset . '\'',
                ]);
        } catch (PDOException $e) {
            throw new DbConnectError($e->getMessage(), $e->errorInfo, "Mysql");
        }

    }

    /**
     * 渲染创建字段
     * @param Model $model
     * @param string $table
     * @return string
     */
    function renderCreateTable(Model $model, string $table): string
    {
        $primary_keys = $model->getPrimaryKey();
        $sql = 'CREATE TABLE IF NOT EXISTS `' . $table . '`(';
        $name = $primary_keys->name;
        $primary = $name;
        $sql .= $this->renderKey($primary_keys, $model->getUnique()) . ",";


        foreach (get_object_vars($model) as $key => $value) {
            if ($key === $primary) continue;
            $sql .= $this->renderKey(new SqlKey($key, $value), $model->getUnique()) . ",";
        }
        $sql .= "PRIMARY KEY (";
        $sql .= "`$primary`";
        $sql .= ")";

        $full = $model->getFullTextKeys();
        if (!empty($full)) {
            $sql .= ",";
            $sql .= "FULLTEXT ( " . join(',', $full) . " ) WITH PARSER ngram";
        }

        $sql .= ')ENGINE=InnoDB DEFAULT CHARSET=' . $this->dbFile->charset . ';';
//getFullTextKeys
        return $sql;

    }

    public function renderKey(SqlKey $sqlKey, array $unique = []): string
    {
        if ($sqlKey->type === SqlKey::TYPE_TEXT && $sqlKey->value !== null)
            $sqlKey->value = str_replace("'", "\'", $sqlKey->value);
        if (in_array($sqlKey->name, $unique)) {
            if ($sqlKey->type === SqlKey::TYPE_INT) return "`$sqlKey->name` BIGINT DEFAULT $sqlKey->value UNIQUE";
            if ($sqlKey->type === SqlKey::TYPE_TEXT) return "`$sqlKey->name` VARCHAR(191) DEFAULT '$sqlKey->value' UNIQUE";
        }
        if ($sqlKey->type === SqlKey::TYPE_INT && $sqlKey->auto) return "`$sqlKey->name` BIGINT AUTO_INCREMENT";

        elseif ($sqlKey->type === SqlKey::TYPE_INT && !$sqlKey->auto) return "`$sqlKey->name` BIGINT DEFAULT '$sqlKey->value'";

        elseif ($sqlKey->type === SqlKey::TYPE_BOOLEAN) return "`$sqlKey->name` TINYINT(1) DEFAULT " . intval($sqlKey->value) . " ";


        elseif ($sqlKey->type === SqlKey::TYPE_TEXT && $sqlKey->length !== 0) return "`$sqlKey->name` VARCHAR(" . $sqlKey->length . ") DEFAULT '$sqlKey->value'";

        elseif ($sqlKey->type === SqlKey::TYPE_TEXT && $sqlKey->length === 0 && $sqlKey->value !== null || $sqlKey->type === SqlKey::TYPE_ARRAY) return "`$sqlKey->name` LONGTEXT   DEFAULT NULL";

        elseif ($sqlKey->type === SqlKey::TYPE_TEXT && $sqlKey->length === 0 && $sqlKey->value === null) return "`$sqlKey->name` TEXT DEFAULT NULL";

        elseif ($sqlKey->type === SqlKey::TYPE_FLOAT) return "`$sqlKey->name` DECIMAL(10, 2) DEFAULT '$sqlKey->value'";

        else {
            return "`$sqlKey->name` TEXT DEFAULT NULL";
        }
    }

    function getDbConnect(): PDO
    {

        return $this->pdo;
    }

    public function __destruct()
    {
        unset($this->pdo);
    }


    function renderEmpty(string $table): string
    {
        return "TRUNCATE TABLE '$table';";
    }

    function onInsertModel(int $model): int
    {
        return $model;
    }
}