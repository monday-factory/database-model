<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModel\Colection;

use MondayFactory\DatabaseModel\Data\IDatabaseData;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;

abstract class BaseDatabaseDataCollection implements IDatabaseDataCollection
{

	/**
	 * @var array
	 */
	private $data = [];

	/**
	 * @var string|null
	 */
	protected $idFieldSerializer = null;

	/**
	 * @var string|null
	 */
	private $rowFieldGetter = null;

	/**
	 * @param array $data
	 */
	protected function __construct(iterable $data, string $colectionItemClass, ?string $idField = null)
	{
		$this->prepareRowFieldGetter($idField);
		$rowFieldGetter = $this->rowFieldGetter;

		/**
		 * @var IDatabaseData $row
		 */
		foreach ($data as $row) {
			if (! $row instanceof $colectionItemClass) {
				throw new \LogicException(
					'You try create collection from item of type ['
					. gettype($row) .
					'], the type of items must be '.
					$colectionItemClass);
			}

			if (is_string($this->rowFieldGetter)) {
				if (! method_exists($row, $this->rowFieldGetter)) {
					throw new \InvalidArgumentException("{$this->$rowFieldGetter} is not a valid getter of {$colectionItemClass}.");
				} elseif (key_exists($this->getIdFieldValue($row), $this->data)) {
					throw new \UnexpectedValueException("Collection must have only one piece {$idField} with value {$row->$rowFieldGetter()}. " .
						'You must provide unique items if is set $idField.');
				}

				$this->data[$this->getIdFieldValue($row)] = $row;
			} else {
				$this->data[] = $row;
			}
		}
	}

	/**
	 * @param string|null $idField
	 */
	private function prepareRowFieldGetter(?string $idField): void
	{
		if (is_string($idField)) {
			$this->rowFieldGetter = 'get' . preg_replace('/[-\_]/', '', Strings::capitalize(ucfirst($idField)));
		}
	}

	/**
	 * @param IDatabaseData $row
	 *
	 * @return string
	 */
	private function getIdFieldValue(IDatabaseData $row): string
	{
		$rowFieldGetter = $this->rowFieldGetter;
		$idFieldSerializer = $this->idFieldSerializer;

		if (is_string($this->idFieldSerializer)) {
			if (!method_exists($row->$rowFieldGetter(), (string) $idFieldSerializer)) {
				throw new \InvalidArgumentException("Method does not exists " . get_class($row->$rowFieldGetter()) . "::{$idFieldSerializer}().");
			}

			return $row->$rowFieldGetter()->$idFieldSerializer();
		}

		return $row->$rowFieldGetter();
	}

	/**
	 * @param array $data
	 *
	 * @return IDatabaseDataCollection
	 */
	abstract public static function create(iterable $data): IDatabaseDataCollection;

	/**
	 * @param int|string $key
	 *
	 * @return IDatabaseData
	 */
	public function getByKey($key): ?IDatabaseData
	{
		if (! is_string($key) && ! is_int($key)) {
			throw new \InvalidArgumentException('Key must be scalar.');
		}

		if (array_key_exists($key, $this->data)) {
			return $this->data[$key];
		}

		return null;
	}

	/**
	 * @return array
	 */
	public function toArray(): array
	{
		$result = [];

		foreach ($this->data as $item) {
			$result[] = $item->toArray();
		}

		return $result;
	}

	/**
	 * @return string
	 */
	public function toString(): string
	{
		return (string) $this;
	}

	/**
	 * @return int
	 */
	public function count(): int
	{
		return count($this->data);
	}


	public function rewind(): void
	{
		reset($this->data);
	}

	/**
	 * @return int|mixed|null|string
	 */
	public function key()
	{
		return key($this->data);
	}

	public function next(): void
	{
		next($this->data);
	}

	/**
	 * @return bool
	 */
	public function valid(): bool
	{
		return key($this->data) !== null;
	}

	/**
	 * @return mixed
	 */
	public function current()
	{
		return current($this->data);
	}

	/**
	 * @return string
	 */
	public function __toString(): string
	{
		try {
			return Json::encode($this->toArray());
		} catch (JsonException $e) {
			return Json::encode(['status' => 'error', 'message' => $e->getMessage()]);
		}
	}

}
