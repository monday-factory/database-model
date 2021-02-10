<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModel\Collection;

use Apitte\Core\Mapping\Request\BasicEntity;
use ArrayIterator;
use LogicException;
use Traversable;

class ApitteRequestCollectionFactory extends BasicEntity
{

	protected string $entityFactory;

	/** @var array<int|string, mixed> $data */
	private array $data;

	/** @param iterable<int|string, mixed> $data */
	public function factory(array $data): self
	{
		if (!class_exists($this->entityFactory)) {
			throw new LogicException("Class [{$this->entityFactory}] is not exists.");
		}

		foreach ($data as $row) {
			$inst = new $this->entityFactory;
			$this->data[] = $inst->factory($row);
		}

		return $this;
	}

	/** @return ArrayIterator|Traversable|array<mixed> */
	public function getIterator(): iterable
	{
		return new ArrayIterator($this->data);
	}

	/** @return array<int, mixed> */
	public function toArray(): array
	{
		$result = [];

		foreach ($this->data as $item) {
			$result[] = $item->toArray();
		}

		return $result;
	}

	public function count(): int
	{
		return count($this->data);
	}

	public function rewind(): void
	{
		reset($this->data);
	}

	/** @return int|mixed|null|string */
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

	/** @return mixed */
	public function current()
	{
		return current($this->data);
	}

}
