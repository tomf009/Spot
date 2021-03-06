<?php

/**
 * Adapter Interface
 *
 * @package Spot
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Db;

use Spot\QueryInterface;

interface AdapterInterface
{
    /**
     * Get internal PDO handle
     * @return \PDO
     */
    public function getInternalHandler();

    /**
     * Set the internal PDO handle
     * @param \PDO $pdo
     */
    public function setInternalHandler(\PDO $pdo);

    /**
     * Escape/quote direct user input
     * @param string $string
     * @return string
     */
    public function quote($string);

    /**
     * Escapes a column/table/schema name
     *
     *<code>
     *  $escapedTable = $connection->escapeIdentifier('blog_post');
     *  $escapedTable = $connection->escapeIdentifier(['blog_post', 'id']);
     *</code>
     *
     * @param string|array $identifier
     * @return string
     */
    public function escapeIdentifier($identifier);

    /**
     * Prepare an SQL statement
     * @param string $sqlStatement
     * @return \PDOStatement
     * @throws \Spot\Exception\Adapter
     */
    public function prepare($sqlStatement);

    /**
     * Find records with custom SQL query
     * @param string $sqlStatement SQL query to execute
     * @param array $binds Array of bound parameters to use as values for query
     * @return \PDOStatement|bool
     * @throws \Spot\Exception\Adapter
     */
    public function query($sqlStatement, array $binds = []);

    /**
     * Begin transaction
     * @return bool
     */
    public function beginTransaction();

    /**
     * Commit transaction
     * @return bool
     */
    public function commit();

    /**
     * Rollback transaction
     * @return bool
     */
    public function rollback();

    /**
     * Fetch the last insert id
     * @param string $sequence
     * @return mixed
     */
    public function lastInsertId($sequence = null);

    /**
     * Return insert statement
     * @param string $tableName
     * @param array $columns
     * @param array $binds
     * @return string
     */
    public function insert($tableName, array $columns, array $binds, array $options);

    /**
     * Return update statement
     * @param string $tableName
     * @param array $columns
     * @param array $binds
     * @param array $conditions
     * @param array $options
     * @return string
     */
    public function update($tableName, array $columns, array $binds, array $conditions, array $options);

    /**
     * Return delete statement
     * @param string $tableName
     * @param array $conditions
     * @return string
     */
    public function delete($tableName, array $conditions);

    /**
     * Return a sql statement built by dialect
     *
     * @param \Spot\QueryInterface $query
     * @return string
     */
    public function getQuerySql(QueryInterface $query);

    /**
     * Build SELECT statement from fields
     *
     * @param string $sqlQuery
     * @param array $fields
     * @return string
     */
    public function select($sqlQuery, array $fields = []);

    /**
     * Build FROM statement from table names
     *
     * @param string $sqlQuery
     * @param string $tableName
     * @return string
     */
    public function from($sqlQuery, $tableName);

    /**
     * Builds an SQL string given conditions
     *
     * @param string $sqlQuery
     * @param array $conditions
     * @param int $ci
     * @return string
     * @throws \Spot\Exception\Adapter
     */
    public function where($sqlQuery, array $conditions = []);

    /**
     * Append a table join (INNER, LEFT OUTER, RIGHT OUTER, FULL OUTER, CROSS) to $sqlQuery argument
     *
     * <code>
     *  echo $connection->join("SELECT * FROM blog_post", ['post_comment', 'blog_post.id = post_comment.post_id']);
     * </code>
     *
     * @param string $sqlQuery
     * @param array $joins
     * @return string
     */
    public function join($sqlQuery, array $joins = []);

    /**
     * Appends GROUP BY clause to $sqlQuery argument
     *
     * <code>
     *  echo $connection->group("SELECT * FROM blog_post", ["title"]);
     * </code>
     *
     * @param string $sqlQuery
     * @param array $group
     * @return string
     */
    public function group($sqlQuery, array $group);

    /**
     * Appends ORDER BY clause to $sqlQuery argument
     *
     * <code>
     *  echo $connection->order("SELECT * FROM blog_post", ["created" => "asc"]);
     * </code>
     *
     * @param string $sqlQuery
     * @param array $order
     * @return string
     */
    public function order($sqlQuery, array $order);

    /**
     * Appends a LIMIT clause to $sqlQuery argument
     *
     * <code>
     *  echo $connection->limit("SELECT * FROM blog_post", 5);
     * </code>
     *
     * @param string $sqlQuery
     * @param int $number
     * @return string
     */
    public function limit($sqlQuery, $number);

    /**
     * Appends a OFFSET clause to $sqlQuery argument
     *
     * <code>
     *  echo $connection->offset("SELECT * FROM blog_post LIMIT 5", 10);
     * </code>
     *
     * @param string $sqlQuery
     * @param int $number
     * @return string
     */
    public function offset($sqlQuery, $number);

    /**
     * Create new row object with set properties
     * @param string $tableName
     * @param array $data
     * @param array $options
     * @return mixed
     * @throws \Spot\Exception\Datasource\Missing|\Spot\Exception\Adapter
     */
    public function createEntity($tableName, array $data, array $options = []);

    /**
     * Build a select statement in SQL
     * Can be overridden by adapters for custom syntax
     *
     * @param \Sbux\QueryInterface $query
     * @param array $options
     * @return bool|array
     * @throws \Spot\Exception\Adapter
     */
    public function readEntity(QueryInterface $query, array $options = []);

    /**
     * Update entity
     * @param string $tableName
     * @param array $data
     * @param data $where
     * @param array $options
     * @throws \Spot\Exception\Adapter
     */
    public function updateEntity($tableName, array $data, array $where = [], array $options = []);

    /**
     * Delete entities matching given conditions
     * @param string $tableName The name of the table
     * @param array $conditions
     * @param array $options
     * @throws \Spot\Exception\Adapter
     */
    public function deleteEntity($tableName, array $conditions = [], array $options = []);

    /**
     * Count number of rows in source based on conditions
     * @param \Spot\QueryInterface $query
     * @param array $options
     * @throws \Spot\Exception\Adapter
     */
    public function countEntity(QueryInterface $query, array $options = []);

    /**
     * Return result set for current query
     * @param \Spot\QueryInterface $query
     * @param \PDOStatement $stmt
     * @return \Spot\Entity\ResultsetInterface
     */
    public function getResultset(QueryInterface $query, \PDOStatement $stmt);

    /**
     * Returns array of binds to pass to query function
     * @param \Spot\QueryInterface $query
     * @param bool $ci Use column incrementing
     */
    public function getQueryBinds(QueryInterface $query, $ci = false);
}
