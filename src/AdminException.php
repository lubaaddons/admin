<?php

namespace Luba;

class AdminException extends \Exception
{
	public function __construct($m, $code = 404)
	{
		http_response_code($code);
		parent::__construct($m);
	}
}