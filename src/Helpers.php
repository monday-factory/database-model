<?php

declare(strict_types=1);

namespace MondayFactory\DatabaseModel;

class Helpers
{

	public static function prepareJson(array $data): array
	{
		return ['\''. json_encode($data) .'\''];
	}
}
