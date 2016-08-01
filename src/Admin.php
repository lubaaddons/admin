<?php

namespace Luba;

class Admin
{
	protected $table;

	protected $displayed = ['*'];

	protected $hidden = [];

	protected $config;

	public function __construct($config = [])
	{
		$this->config = $config;
	}
}