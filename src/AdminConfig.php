<?php

namespace Luba;

use SQL;
use Luba\Framework\View;
use Closure;
use Flo\MySQL\MySQL;

class AdminConfig
{
	protected $tables = [];

	protected $templateDir = __DIR__.'/views/';

	protected static $global_config = [];

	protected $auth;

	protected $filters;

	protected $dashboard = true;

    protected $loginlink = 'auth/login';
	protected $logoutlink = 'auth/logout';

	public function __construct($config = [])
	{
		$this->set(static::$global_config);

		if (!empty($config))
		{
			$this->set($config);
		}
	}

	public static function setGlobal(array $config)
	{
		static::$global_config = $config;
	}

	public function auth(callable $auth)
	{
		$this->auth = $auth;
	}

	public function set($config)
	{
		foreach ($config as $key => $setting)
		{
			$this->$key = $setting;
		}
	}

	public function paginate($perpage = 20)
	{
		$this->pagination = true;
		$this->perpage = $perpage;
	}

	public function __set($key, $value)
	{
		if (!property_exists($this, $key))
			throw new AdminException("Error in config: Property $key can not be set!");
	}

	public function filters(Closure $filters)
	{
		$this->filters = $filters;
	}

	public function applyFilters(MySQL $query)
	{
		$filters = $this->filters;

		return $filters($query);
	}

	public function templateDir()
	{
		return $this->templateDir;
	}

	public static function make(array $config)
	{
		return new self($config);
	}

	public function tables()
	{
		return $this->tables;
	}

	public function getNav()
	{
		$nav = [];

		if  ($this->dashboard)
			$nav['Dashboard'] = url('admin');

		foreach ($this->tables as $name => $conf)
		{
			$menuname = isset($conf['menuname']) ? $conf['menuname'] : ucfirst($name);

			$nav[$menuname] = url("admin/$name");
		}

		return $nav;
	}

	public function dashboard()
	{
		return $this->dashboard;
	}

	public function authenticate()
	{
		if (!$this->auth)
			return true;

		$auth = $this->auth;
		if ($auth())
			return true;
		else
			throw new AdminException('Access Denied', 403);
	}

    public function loginlink()
    {
        return $this->loginlink;
    }

	public function logoutlink()
	{
		return $this->logoutlink;
	}
}