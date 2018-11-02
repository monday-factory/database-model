<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModel\Storage;

use MondayFactory\DatabaseModel\Colection\IDatabaseDataCollection;
use MondayFactory\DatabaseModel\Data\IDatabaseData;

interface ILowLevelRelationalDatabaseStorage
{

	public function create(iterable $data): ?int;

	/**
	 * @param string|int $id
	 *
	 * @return IDatabaseData|null
	 */
	public function findOne($id): ?IDatabaseData;

	public function findOneByCriteria(iterable $criteria): ?IDatabaseData;

	public function find(iterable $ids): IDatabaseDataCollection;

	public function findByCriteria(iterable $criteria): IDatabaseDataCollection;

	/**
	 * @param string|int $id
	 * @param iterable $data
	 *
	 * @return int
	 */
	public function update($id, iterable $data): int;

	public function updateBy(iterable $criteria, iterable $data): int;

	/**
	 * @param string|int $id
	 *
	 * @return int|null
	 */
	public function delete($id): ?int;

}
