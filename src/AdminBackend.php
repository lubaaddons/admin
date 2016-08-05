<?php

namespace Luba;

use Luba\Framework\View;
use SQL;

class AdminBackend
{
	protected $config;

	public function __construct($config)
	{
		if (is_array($config))
			$config = AdminConfig::make($config);

		$this->config = $config;
	}

	public function index()
	{
		if ($this->config->dashboard() === false)
			throw new AdminException('Dashboard has been disabled!');

		$tables = [];

		foreach ($this->config->tables() as $name => $config)
		{
			$items = $this->getItems($config, $name, function($query){
				$query->orderBy('created_at', 'desc');
				$query->limit(7);
			});
			$tables[] = ['name' => $name, 'config' => $config, 'items' => $items];
		}

		return new View('dashboard', ['nav' => $this->config->getNav(), 'tables' => $tables], __DIR__.'/views/');
	}

	public function edit($id)
	{
		
	}

	public function create()
	{
		
	}

	public function delete($id)
	{
		
	}

	public function update($id)
	{
		
	}

	public function store()
	{
		
	}

	public function actionIsAllowed()
	{
		return true;
	}

	public function __call($tablename, $args)
	{
		$tables = $this->config->tables();
		
		if (isset($args[0]) && method_exists($this, $args[0]))
		{
			$method = array_shift($args);
			return call_user_func_array([$this, $method], $args);
		}

		if (isset($tables[$tablename]))
		{
			$table = $tables[$tablename];
			$items = $this->getItems($table, $tablename); 

			return new View('index', ['items' => $items, 'tableconf' => $table, 'nav' => $this->config->getNav(), 'tablename' => $tablename], $this->config->templateDir());
		}
		else
			throw new AdminException("Action \"$func\" has not been configured!");
	}

	private function getItems(array $tableconf, $tablename, callable $otherfilter = NULL)
	{
		$select = [];

		$displayed = isset($tableconf['displayed']) ? $tableconf['displayed'] : '*';

		$primarykey = isset($tableconf['primarykey']) ? $tableconf['primarykey'] : 'id';

		if ((!isset($displayed[$primarykey]) or array_search($primarykey, $displayed) === false) && array_search('*', $displayed) === false)
			$displayed = [$primarykey => mb_strtoupper($primarykey)] + $displayed;

		foreach ($displayed as $col => $title)
		{
			if (is_int($col))
				$select[] = $title;
			else
				$select[] = "$col";
		}

		$items = SQL::table($tablename)->select(implode(', ', $select));

		if (isset($tableconf['filter']))
		{
			$filter = $tableconf['filter'];
			$filter($items);
		}

		if ($otherfilter)
			$otherfilter($items);

		$items = $items->get();

		return $items;
	}
}