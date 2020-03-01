<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModel\Colection;

use Apitte\Core\Mapping\Request\BasicEntity;
use ArrayIterator;
use Traversable;

class ApitteRequestCollectionFactory extends BasicEntity
{
	/**
	 * @var array
	 */
	private $data;

	/**
	 * @var string
	 */
	protected $entityFactory;

	public function factory(array $data)
	{
		if (! class_exists($this->entityFactory)) {
			throw new \LogicException("Class [{$this->entityFactory}] is not exists.");
		}

		foreach ($data as $row) {
			$inst = new $this->entityFactory;
			$this->data[] = $inst->factory($row);
		}

		return $this;
	}

	/**
	 * @return ArrayIterator|Traversable|mixed[]
	 */
	public function getIterator(): iterable
	{
		return new ArrayIterator($this->data);
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
}
