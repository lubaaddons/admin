<?php

namespace Luba;

class TypeToInput
{
	protected static $mapping = [
		'/int/' => 'text',
		'/varchar/' => 'text',
		'/text/' => 'textarea',
		'/date/' => 'text'
	];

	public static function make($coltype)
	{
		foreach (static::$mapping as $type => $input)
		{
			preg_match($type, $coltype, $matches);
			
			if (!empty($matches))
				return $input;
		}

		return NULL;
	}
}