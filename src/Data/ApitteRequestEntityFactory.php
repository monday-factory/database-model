<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModel\Data;

use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Mapping\Request\BasicEntity;
use InvalidArgumentException;
use LogicException;
use ReflectionClass;
use ReflectionParameter;
use ReflectionProperty;

use function assert;

abstract class ApitteRequestEntityFactory extends BasicEntity
{

	protected string $entityClass;

	/** @var array<int|string, mixed> */
	protected array $forceOptionalProperties = [];

	/** @param array<int|string, mixed> $data */
	public function factory(array $data): self
	{
		if (is_null($this->entityClass)) {
			throw new InvalidArgumentException('Property $entityClass must been set.');
		}

		$inst = new static;
		$ref = new ReflectionClass($this->entityClass);
		$parameters = $ref->getConstructor()->getParameters();

		foreach ($parameters as $parameter) {
			assert($parameter instanceof ReflectionParameter);

			if (!$this->isForceOptional($parameter) && !$parameter->isOptional() && !isset($data[$parameter->name])) {
				throw ClientErrorException::create()
					->withCode(400)
					->withMessage("Parameter {$parameter->name} is required.");
			}

			if (
				($parameter->allowsNull() && !$parameter->isOptional())
				&& isset($data[$parameter->name])
				&& ($data[$parameter->name] === null || $data[$parameter->name] === 'null')
			) {
				$inst->{$parameter->name} = null;
			} elseif ($parameter->isOptional() && !isset($data[$parameter->name])) {
				$inst->{$parameter->name} = $parameter->getDefaultValue();
			} elseif ($this->isForceOptional($parameter) && !isset($data[$parameter->name])) {
				continue;
			}

			if (!$parameter->getType()->isBuiltin()) {
				$paramFactory = "createParam" . ucfirst($parameter->name);

				if (method_exists($this, $paramFactory)) {
					$inst->{$parameter->name} = $inst->$paramFactory($data[$parameter->name]);
				} else {
					throw new LogicException('Missing public method ' . self::class . '::' . $paramFactory);
				}
			} else {
				$value = $data[$parameter->name];
				settype($value, $parameter->getType()->getName());
				$inst->{$parameter->name} = $value;
			}
		}

		$entityReflection = new ReflectionClass(static::class);

		foreach ($entityReflection->getProperties(ReflectionProperty::IS_PUBLIC) as $additionalProperty) {
			if (isset($data[$additionalProperty->name])) {
				$inst->{$additionalProperty->name} = $data[$additionalProperty->name];
			} elseif (!isset($data[$additionalProperty->name]) && !$this->isForceOptional($additionalProperty)) {
				throw ClientErrorException::create()
					->withCode(400)
					->withMessage("Parameter {$additionalProperty->name} is required.");
			}
		}

		return $inst;
	}

	/** @param object|mixed $parameter */
	private function isForceOptional($parameter): bool
	{
		return in_array($parameter->name, $this->forceOptionalProperties, true);
	}

}
