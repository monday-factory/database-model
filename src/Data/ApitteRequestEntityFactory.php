<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModel\Data;

use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Mapping\Request\BasicEntity;

abstract class ApitteRequestEntityFactory extends BasicEntity
{
	protected $entityClass;

	/**
	 * @param mixed[] $data
	 * @return static
	 */
	public function factory(array $data): self
	{
		if (is_null($this->entityClass)) {
			throw new \InvalidArgumentException('Property $entityClass must been set.');
		}

		$inst = new static();

		$ref = new \ReflectionClass($this->entityClass);
		$parameters = $ref->getConstructor()->getParameters();

		/**
		 * @var \ReflectionParameter $parameter
		 */
		foreach ($parameters as $parameter) {

			if (! $parameter->isOptional() && !isset($data[$parameter->name])) {
				throw ClientErrorException::create()
					->withCode(400)
					->withMessage("Parameter {$parameter->name} is required.");
			}

			if (
				($parameter->allowsNull() && !$parameter->isOptional())
				&& ($data[$parameter->name] === null || $data[$parameter->name] === 'null')
			) {
				$inst->{$parameter->name} = null;
			} elseif (
				$parameter->isOptional() && !isset($data[$parameter->name])
			) {
				$inst->{$parameter->name} = $parameter->getDefaultValue();
			} else {
				if (! $parameter->getType()->isBuiltin()) {
					$paramFactory = "createParam" . ucfirst($parameter->name);

					if (method_exists($this, $paramFactory)) {
						$inst->{$parameter->name} = $inst->$paramFactory($data[$parameter->name]);
					} else {
						throw new \LogicException('Missing public method ' . __CLASS__ . '::' . $paramFactory);
					}
				} else {
					$value = $data[$parameter->name];
					settype($value, $parameter->getType()->getName());
					$inst->{$parameter->name} = $value;
				}
			}
		}

		return $inst;
	}
}