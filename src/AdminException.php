<?php

namespace Luba;

class AdminException extends \Exception
{
	public function __construct($m)
	{
		http_response_code(404);
		parent::__construct($m);
	}
}