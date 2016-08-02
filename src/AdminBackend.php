<?php

namespace Luba;

use Luba\Framework\View;
use SQL;

class AdminBackend
{
	protected $config;

	public function __construct(AdminConfig $config)
	{
		$this->config = $config;
	}

	public function index()
	{
		return new View('index', $this->config->getItems()->toArray(), $this->config->template());
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

		if (isset($tables[$tablename]))
		{
			$table = $tables[$tablename];
			$items = $this->getItems($table, $tablename);

			return new View('index', ['items' => $items, 'tableconf' => $table, 'nav' => $this->config->getNav(), 'tablename' => $tablename], $this->config->templateDir());
		}
		else
			throw new AdminException("Action \"$func\" has not been configured!");
	}

	private function getItems(array $tableconf, $tablename)
	{
		$select = [];

		$displayed = isset($tableconf['displayed']) ? $tableconf['displayed'] : '*';

		$primarykey = isset($tableconf['primarykey']) ? $tableconf['primarykey'] : 'id';

		if ((!isset($displayed[$primarykey]) or array_search($primarykey, $displayed) === false) && array_search('*', $displayed) === false)
			$displayed = [$primarykey => mb_strtoupper($primarykey)] + $displayed;

		foreach ($displayed as $col => $title)
		{
			if (is_int($col))
				$select[] = "$title";
			else
				$select[] = "$col as $title";
		}

		$items = SQL::table($tablename)->select(implode(', ', $select));

		if (isset($tableconf['filter']))
		{
			$filter = $tableconf['filter'];
			$filter($items);
		}

		$items = $items->get();

		return $items;
	}
}