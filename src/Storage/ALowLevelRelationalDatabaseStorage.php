<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModel\Storage;

use Dibi\Connection;
use Dibi\Expression;
use Dibi\Fluent;
use Dibi\Result;
use MondayFactory\DatabaseModel\Colection\IDatabaseDataCollection;
use MondayFactory\DatabaseModel\Data\IDatabaseData;
use MondayFactory\DatabaseModel\Exception\InvalidResultType;

abstract class ALowLevelRelationalDatabaseStorage implements ILowLevelRelationalDatabaseStorage
{

	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * @var string
	 */
	protected string $tableName;

	/**
	 * @var string
	 */
	protected string $idField;

	/**
	 * @var string
	 */
	protected string $rowFactoryClass;

	/**
	 * @var string
	 */
	protected string $collectionFactory;

	/**
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * @param iterable $data
	 *
	 * @return int|null
	 * @throws \Dibi\Exception
	 */
	public function create(iterable $data): ?int
	{
		$result = $this->connection->insert($this->tableName, $data)
			->execute(\dibi::AFFECTED_ROWS);

		return $result instanceof Result
			? $result->getRowCount()
			: (int) $result;
	}

	public function createFromDataObject(IDatabaseData $data): ?int
	{
		return $this->create($data->toDatabaseArray());
	}

	/**
	 * @param string|int $id
	 *
	 * @return IDatabaseData|null
	 */
	public function findOne($id): ?IDatabaseData
	{
		if (! is_string($id) && ! is_int($id)) {
			throw new \InvalidArgumentException('Argument [id] must be scalar.' . gettype($id) . 'given.');
		}

		$result = $this->connection->select('*')
			->from($this->tableName)
			->where("[$this->idField]" . ' = ?', $id)
			->setupResult('setRowFactory', [$this->rowFactoryClass, 'fromRow'])
			->fetch();

		if ($result === null || $result instanceof IDatabaseData) {
			return $result;
		}

		throw new InvalidResultType('Unexpected result type ' . gettype($result) . '. Expected result is null|'.IDatabaseData::class.'.');
	}

	/**
	 * @param iterable $criteria
	 *
	 * @return IDatabaseData|null
	 */
	public function findOneByCriteria(iterable $criteria): ?IDatabaseData
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

		throw new InvalidResultType('Unexpected result type ' . gettype($result) . '. Expected result is null|'.IDatabaseData::class.'.');
	}

	/**
	 * @param iterable $ids
	 *
	 * @return IDatabaseDataCollection
	 */
	public function find(iterable $ids, ?int $limit = null, ?int $offset = null): IDatabaseDataCollection
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
	 * @return IDatabaseDataCollection
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
	 * @param iterable $criteria
	 *
	 * @return IDatabaseDataCollection
	 */
	public function findByCriteria(iterable $criteria, ?int $limit = null, ?int $offset = null): IDatabaseDataCollection
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
	 * @param iterable $data
	 *
	 * @return int
	 * @throws \Dibi\Exception
	 */
	public function update($id, iterable $data): int
	{
		$result = $this->connection->update($this->tableName, $data)
			->where("[$this->idField]" . ' = ?', $id)
			->execute(\dibi::AFFECTED_ROWS);

		return $result instanceof Result
			? $result->getRowCount()
			: (int) $result;
	}

	/**
	 * @param iterable $criteria
	 * @param iterable $data
	 *
	 * @return int
	 * @throws \Dibi\Exception
	 */
	public function updateBy(iterable $criteria, iterable $data): int
	{
		$result = $this->connection->update($this->tableName, $data)
			->where('%ex', $criteria)
			->execute(\dibi::AFFECTED_ROWS);

		return $result instanceof Result
			? $result->getRowCount()
			: (int) $result;
	}

	/**
	 * @param string|int $id
	 *
	 * @return int
	 * @throws \Dibi\Exception
	 */
	public function delete($id): int
	{
		$result =  $this->connection->delete($this->tableName)
			->where("[$this->idField]" . ' = ?', $id)
			->execute(\dibi::AFFECTED_ROWS);

		return $result instanceof Result
			? $result->getRowCount()
			: (int) $result;
	}

	/**
	 * @param iterable $criteria
	 *
	 * @return int
	 * @throws \Dibi\Exception
	 */
	public function deleteBy(iterable $criteria, ?int $limit = null): int
	{
		$result =  $this->connection->delete($this->tableName)
			->where('%ex', $criteria);

		if (is_int($limit)) {
			$result = $result->limit($limit);
		}

		$result = $result->execute(\dibi::AFFECTED_ROWS);

		return $result instanceof Result
			? $result->getRowCount()
			: (int) $result;
	}

	/* ********************************** Low level helpers ********************************** */

	/**
	 * @param Fluent $query
	 * @param int|null $limit
	 * @param int|null $offset
	 *
	 * @return void
	 */
	public function applyLimitAndOffset(Fluent &$query, ?int $limit = null, ?int $offset = null): void
	{
		!is_int($limit) ?: $query = $query->limit($limit);
		!is_int($offset) ?: $query = $query->offset($offset);
	}

	/**
	 * @param string $selectSet
	 *
	 * @return \Dibi\Fluent
	 */
	public function getTableFluent(string $selectSet = '*', string $tableAlias = ''): Fluent
	{
		return $this->connection
			->select($selectSet)
			->from('[' . $this->tableName . '] ' . $tableAlias)
			->setupResult('setRowFactory', [$this->rowFactoryClass, 'fromRow']);
	}

	/**
	 * @return \Dibi\Expression
	 */
	public function getExpression(): Expression
	{
		return $this->connection::expression(func_get_args());
	}

	/**
	 * @return Connection
	 */
	public function getConnection(): Connection
	{
		return $this->connection;
	}

	/**
	 * @return string
	 */
	public function getTableName(): string
	{
		return $this->tableName;
	}

	/**
	 * @return string
	 */
	public function getIdField(): string
	{
		return $this->idField;
	}

	/**
	 * @param IDatabaseData[] $data
	 *
	 * @return IDatabaseDataCollection
	 */
	public function createCollection(array $data): IDatabaseDataCollection
	{
		return $this->collectionFactory::create($data, $this->idField);
	}

}
