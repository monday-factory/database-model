<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModel\Colection;

use MondayFactory\DatabaseModel\Data\IDatabaseData;

interface IDatabaseDataCollection extends \Iterator, \Countable
{

	/**
	 * @param iterable $data
	 *
	 * @return IDatabaseDataCollection
	 */
	public static function create(iterable $data): self;

	/**
	 * @param string|int $key
	 *
	 * @return IDatabaseData|null
	 */
	public function getByKey($key): ?IDatabaseData;

	/**
	 * @return array
	 */
	public function toArray(): array;

	/**
	 * @return string
	 */
	public function toString(): string;

}
