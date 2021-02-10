<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModel\Data;

interface IDatabaseData
{

	/** @param array<int|string, mixed> $data */
	public static function fromData(array $data): IDatabaseData;

	/** @param array<int|string, mixed> $row */
	public static function fromRow(array $row): IDatabaseData;

	/** @return array<int|string, mixed> */
	public function toArray(): array;

	/** @return array<int|string, mixed> */
	public function toDatabaseArray(): array;

}
