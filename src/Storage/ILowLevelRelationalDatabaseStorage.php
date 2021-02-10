<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModel\Storage;

use MondayFactory\DatabaseModel\Collection\IDatabaseDataCollection;
use MondayFactory\DatabaseModel\Data\IDatabaseData;

interface ILowLevelRelationalDatabaseStorage
{

	/** @param iterable<int|string, mixed> $data */
	public function create(iterable $data): ?int;

	/** @param string|int $id */
	public function findOne($id): ?IDatabaseData;

	/** @param array<int|string, mixed> $criteria */
	public function findOneByCriteria(array $criteria): ?IDatabaseData;

	/**
	 * @param array<int|string, mixed> $ids
	 *
	 * @return IDatabaseDataCollection<int|string, IDatabaseData>
	 */
	public function find(array $ids): IDatabaseDataCollection;

	/**
	 * @param array<int|string, mixed> $criteria
	 *
	 * @return IDatabaseDataCollection<int|string, IDatabaseData>
	 */
	public function findByCriteria(array $criteria): IDatabaseDataCollection;

	/**
	 * @param string|int $id
	 * @param iterable<int|string, mixed> $data
	 */
	public function update($id, iterable $data): int;

	/**
	 * @param array<int|string, mixed> $criteria
	 * @param iterable<int|string, mixed> $data
     */
	public function updateBy(array $criteria, iterable $data): int;

	/** @param string|int $id */
	public function delete($id): ?int;

	/** @param array<int|string, mixed> $criteria */
	public function deleteBy(array $criteria, ?int $limit = null): int;

}
