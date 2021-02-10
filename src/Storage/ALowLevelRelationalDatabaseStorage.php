<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModel\Storage;

use dibi;
use Dibi\Connection;
use Dibi\Expression;
use Dibi\Fluent;
use Dibi\Result;
use InvalidArgumentException;
use MondayFactory\DatabaseModel\Collection\IDatabaseDataCollection;
use MondayFactory\DatabaseModel\Data\IDatabaseData;
use MondayFactory\DatabaseModel\Exception\InvalidResultType;

abstract class ALowLevelRelationalDatabaseStorage implements ILowLevelRelationalDatabaseStorage
{

    protected string $tableName;
    protected string $idField;
    protected string $rowFactoryClass;
    protected string $collectionFactory;
	private Connection $connection;

	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * @param iterable<int|string, mixed> $data
	 *
	 * @throws \Dibi\Exception
	 */
	public function create(iterable $data): ?int
	{
		$result = $this->connection->insert($this->tableName, $data)
			->execute(dibi::AFFECTED_ROWS);

		return $result instanceof Result
			? $result->getRowCount()
			: (int) $result;
	}

	/** @param string|int $id */
	public function findOne($id): ?IDatabaseData
	{
		if (!is_string($id) && !is_int($id)) {
			throw new InvalidArgumentException('Argument [id] must be scalar.' . gettype($id) . 'given.');
		}

		$result = $this->connection->select('*')
			->from($this->tableName)
			->where("[$this->idField]" . ' = ?', $id)
			->setupResult('setRowFactory', [$this->rowFactoryClass, 'fromRow'])
			->fetch();

		if ($result === null || $result instanceof IDatabaseData) {
			return $result;
		}

		throw new InvalidResultType(
            'Unexpected result type ' . gettype($result) . '. Expected result is null|' . IDatabaseData::class . '.',
        );
	}

	/** @param array <int|string, mixed> $criteria */
	public function findOneByCriteria(array $criteria): ?IDatabaseData
	{
		$result = $this->connection->select('*')
			->from($this->tableName);

		if (count($criteria) > 0) {
			$result = $result
				->where('%ex', $criteria);
		}

		$result = $result->setupResult('setRowFactory', [$this->rowFactoryClass, 'fromRow'])
			->limit(1)
			->fetch();

		if ($result === null || $result instanceof IDatabaseData) {
			return $result;
		}

		throw new InvalidResultType(
            'Unexpected result type ' . gettype($result) . '. Expected result is null|' . IDatabaseData::class . '.',
        );
	}

	/**
	 * @param array<int|string, mixed> $ids
	 *
	 * @return IDatabaseDataCollection<int|string, IDatabaseData>
	 */
	public function find(array $ids, ?int $limit = null, ?int $offset = null): IDatabaseDataCollection
	{
		$query = $this->connection->select('*')
			->from($this->tableName)
			->where("[$this->idField]" . 'IN(?)', $ids)
			->setupResult('setRowFactory', [$this->rowFactoryClass, 'fromRow']);

		$this->applyLimitAndOffset($query, $limit, $offset);

		return $this->createCollection($query->fetchAll());
	}

	/**
	 * @param int|null $limit default null
	 * @param int|null $offset default null
	 *
	 * @return IDatabaseDataCollection<int|string, IDatabaseData>
	 */
	public function findAll(?int $limit = null, ?int $offset = null): IDatabaseDataCollection
	{
		$query = $this->connection->select('*')
			->from($this->tableName)
			->setupResult('setRowFactory', [$this->rowFactoryClass, 'fromRow']);

		$this->applyLimitAndOffset($query, $limit, $offset);

		return $this->createCollection($query->fetchAll());
	}

	/**
	 * @param array<int|string, mixed> $criteria
	 *
	 * @return IDatabaseDataCollection<int|string, IDatabaseData>
	 */
	public function findByCriteria(array $criteria, ?int $limit = null, ?int $offset = null): IDatabaseDataCollection
	{
		$query = $this->connection->select('*')
			->from($this->tableName)
			->where('%ex', $criteria)
			->setupResult('setRowFactory', [$this->rowFactoryClass, 'fromRow']);

		$this->applyLimitAndOffset($query, $limit, $offset);

		return $this->createCollection($query->fetchAll());
	}

	/**
	 * @param string|int $id
	 * @param iterable<int|string, mixed> $data
	 */
	public function update($id, iterable $data): int
	{
		$result = $this->connection->update($this->tableName, $data)
			->where("[$this->idField]" . ' = ?', $id)
			->execute(dibi::AFFECTED_ROWS);

		return $result instanceof Result
			? $result->getRowCount()
			: (int) $result;
	}

	/**
	 * @param array<int|string, mixed> $criteria
	 * @param iterable<int|string, mixed> $data
	 */
	public function updateBy(array $criteria, iterable $data): int
	{
		$result = $this->connection->update($this->tableName, $data)
			->where('%ex', $criteria)
			->execute(dibi::AFFECTED_ROWS);

		return $result instanceof Result
			? $result->getRowCount()
			: (int) $result;
	}

	/** @param string|int $id */
	public function delete($id): int
	{
		$result = $this->connection->delete($this->tableName)
			->where("[$this->idField]" . ' = ?', $id)
			->execute(dibi::AFFECTED_ROWS);

		return $result instanceof Result
			? $result->getRowCount()
			: (int) $result;
	}

	/** @param array<int|string, mixed> $criteria */
	public function deleteBy(array $criteria, ?int $limit = null): int
	{
		$result = $this->connection->delete($this->tableName)
			->where('%ex', $criteria);

		if (is_int($limit)) {
			$result = $result->limit($limit);
		}

		$result = $result->execute(dibi::AFFECTED_ROWS);

		return $result instanceof Result
			? $result->getRowCount()
			: (int) $result;
	}

	/* ********************************** Low level helpers ********************************** */

	public function applyLimitAndOffset(Fluent &$query, ?int $limit = null, ?int $offset = null): void
	{
		if (is_int($limit)) {
			$query = $query->limit($limit);
		}

		if (is_int($offset)) {
			$query = $query->offset($offset);
		}
	}

	public function getTableFluent(string $selectSet = '*', string $tableAlias = ''): Fluent
	{
		return $this->connection
			->select($selectSet)
			->from($this->tableName . ' ' . $tableAlias)
			->setupResult('setRowFactory', [$this->rowFactoryClass, 'fromRow']);
	}

	public function getExpression(): Expression
	{
		return $this->connection::expression(func_get_args());
	}

	public function getConnection(): Connection
	{
		return $this->connection;
	}

	public function getTableName(): string
	{
		return $this->tableName;
	}

	public function getIdField(): string
	{
		return $this->idField;
	}

	/**
	 * @param array<IDatabaseData> $data
	 *
	 * @return IDatabaseDataCollection<int|string, IDatabaseData>
	 */
	public function createCollection(array $data): IDatabaseDataCollection
	{
		return $this->collectionFactory::create($data, $this->idField);
	}

}
