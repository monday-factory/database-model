<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModel\Collection;

use Countable;
use Iterator;
use MondayFactory\DatabaseModel\Data\IDatabaseData;

/** @extends Iterator<int|null|string, IDatabaseData> */
interface IDatabaseDataCollection extends Iterator, Countable
{

	/** @param iterable<int|string, mixed> $data */
	public static function create(iterable $data): self;

	/** @param string|int $key */
	public function getByKey($key): ?IDatabaseData;

	/** @return array<int|string, mixed> */
	public function toArray(): array;

	public function toString(): string;

}
