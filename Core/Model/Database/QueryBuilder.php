<?php
/*
 * COPYRIGHT (c) [SUMAN BANERJEE] - All Rights Reserved
 * SUMAN BANERJEE <contact@isumanbanerjee.com>
 * Project Name: FRAMEWORK
 * Created by: Suman Banerjee <contact@isumanbanerjee.com>.
 */

namespace Core\Model\Database;

use PDO;

class QueryBuilder
{
    private PDO $pdo;
    private string $table;
    private array $fields = [];
    private array $conditions = [];
    private array $params = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function select(array $fields = ['*']): self
    {
        $this->fields = $fields;
        return $this;
    }

    public function where(string $field, string $operator, $value): self
    {
        $this->conditions[] = "$field $operator ?";
        $this->params[] = $value;
        return $this;
    }

    public function get(): array
    {
        $sql = "SELECT " . implode(', ', $this->fields) . " FROM $this->table";
        if (!empty($this->conditions)) {
            $sql .= " WHERE " . implode(' AND ', $this->conditions);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}