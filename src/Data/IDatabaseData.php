<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModel\Data;

interface IDatabaseData
{

	public static function fromData(array $data): IDatabaseData;

	public static function fromRow(array $row): IDatabaseData;

	public function toArray(): array;

	public function toDatabaseArray(): array ;

}
