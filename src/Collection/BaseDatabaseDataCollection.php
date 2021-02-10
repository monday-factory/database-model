<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModel\Collection;

use InvalidArgumentException;
use LogicException;
use MondayFactory\DatabaseModel\Data\IDatabaseData;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;
use UnexpectedValueException;

use function assert;

abstract class BaseDatabaseDataCollection implements IDatabaseDataCollection
{

	protected ?string $idFieldSerializer = null;

	/** @var array<int|string, IDatabaseData> */
	private array $data = [];

	private ?string $rowFieldGetter = null;

	/** @param iterable<int|string, mixed> $data */
	abstract public static function create(iterable $data): IDatabaseDataCollection;

	/** @param iterable<int|string, mixed> $data */
	protected function __construct(iterable $data, string $collectionItemClass, ?string $idField = null)
	{
		$this->prepareRowFieldGetter($idField);
		$rowFieldGetter = $this->rowFieldGetter;

		foreach ($data as $row) {
			assert($row instanceof IDatabaseData);

			if (!$row instanceof $collectionItemClass) {
				throw new LogicException(
					'You try create collection from item of type ['
					. gettype($row)
					. '], the type of items must be '
					. $collectionItemClass);
			}

			if (!is_string($this->rowFieldGetter)) {
				$this->data[] = $row;

				continue;
			}

			if (!method_exists($row, $this->rowFieldGetter)) {
				throw new InvalidArgumentException(
					"{$this->$rowFieldGetter} is not a valid getter of {$collectionItemClass}.",
				);
			}

			if (array_key_exists($this->getIdFieldValue($row), $this->data)) {
				throw new UnexpectedValueException(
					"Collection must have only one piece {$idField} with value {$row->$rowFieldGetter()}. " .
					'You must provide unique items if is set $idField.',
				);
			}

			$this->data[$this->getIdFieldValue($row)] = $row;
		}
	}

	/** @param int|string $key */
	public function getByKey($key): ?IDatabaseData
	{
		if (!is_string($key) && !is_int($key)) {
			throw new InvalidArgumentException('Key must be scalar.');
		}

		return $this->data[$key] ?? null;
	}

	/** @return array<int, array<int|string, mixed>> */
	public function toArray(): array
	{
		$result = [];

		foreach ($this->data as $item) {
			$result[] = $item->toArray();
		}

		return $result;
	}

	public function toString(): string
	{
		return (string) $this;
	}

	public function count(): int
	{
		return count($this->data);
	}

	public function rewind(): void
	{
		reset($this->data);
	}

	/** @return int|null|string */
	public function key()
	{
		return key($this->data);
	}

	public function next(): void
	{
		next($this->data);
	}

	public function valid(): bool
	{
		return key($this->data) !== null;
	}

	/** @return false|IDatabaseData */
	public function current()
	{
		return current($this->data);
	}

	private function prepareRowFieldGetter(?string $idField): void
	{
		if (is_string($idField)) {
			$this->rowFieldGetter = 'get' . preg_replace('/[-\_]/', '', Strings::capitalize(ucfirst($idField)));
		}
	}

	private function getIdFieldValue(IDatabaseData $row): string
	{
		$rowFieldGetter = $this->rowFieldGetter;
		$idFieldSerializer = $this->idFieldSerializer;

		if (!is_string($this->idFieldSerializer)) {
			return $row->$rowFieldGetter();
		}

		if (method_exists($row->$rowFieldGetter(), (string) $idFieldSerializer)) {
			return $row->$rowFieldGetter()->$idFieldSerializer();
		}

		throw new InvalidArgumentException(
			"Method does not exists " . get_class($row->$rowFieldGetter()) . "::{$idFieldSerializer}().",
		);
	}

	public function __toString(): string
	{
		try {
			return Json::encode($this->toArray());
		} catch (JsonException $e) {
			return Json::encode(['status' => 'error', 'message' => $e->getMessage()]);
		}
	}

}
