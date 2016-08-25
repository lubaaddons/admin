<?php

namespace Luba;

use Luba\Framework\View;
use Flo\MySQL\MySQLResult;
use SQL;
use Redirect;
use Form;
use Input;
use Luba\Framework\Paginator;

class AdminBackend
{
	protected $config;

	protected $table;

	public function __construct($config)
	{
		if (is_array($config))
			$config = AdminConfig::make($config);
		
		$this->config = $config;
		$this->config->authenticate();

		if (!is_dir(public_path('tempimages')))
			mkdir(public_path('tempimages'));

		$this->cleanThumbs();
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
		$item = SQL::table($this->table)->find($id);
		$tableconf = $this->getTableConfig();

		if (!isset($tableconf['editable']))
			throw new AdminException('No editable fields are defined!');

		$editfields = $tableconf['editable'];
		$types = [];

		foreach ($editfields as $column)
		{
			$types[] = TypeToInput::make(SQL::table($this->table)->getColumnType($column));
		}

		$merged = array_combine($editfields, $types);

		$form = $this->itemform($merged, (array) $item);
		$form->action(url("admin/{$this->table}/update/$id"));

		return new View('edit', ['editfields' => $merged, 'item' => $item, 'form' => $form], __DIR__.'/views/');
	}

	public function update($id)
	{
		$tableconf = $this->getTableConfig();

		if (!isset($tableconf['editable']))
			throw new AdminException('No editable fields are defined!');

		$editfields = $tableconf['editable'];
		$types = [];

		foreach ($editfields as $column)
		{
			$types[] = TypeToInput::make(SQL::table($this->table)->getColumnType($column));
		}

		$merged = array_combine($editfields, $types);

		$form = $this->itemform($merged);

		SQL::table($this->table)->update($id, Input::except('_token', 'save'));

		Redirect::to(url("admin/{$this->table}"));
	}

	public function itemform($fields, $bindings = false)
	{
		$form = new Form;
		
		if ($bindings)
			$form->bind($bindings);
		
		foreach ($fields as $name => $field)
		{
			$form->$field($name)->label(ucfirst($name));
		}

		$form->submit('save', 'Save', ['value' => 'Save']);

		return $form;
	}

	public function create()
	{
		$tableconf = $this->getTableConfig();

		if (!isset($tableconf['editable']))
			throw new AdminException('No editable fields are defined!');

		$editfields = $tableconf['editable'];
		$types = [];

		foreach ($editfields as $column)
		{
			$types[] = TypeToInput::make(SQL::table($this->table)->getColumnType($column));
		}

		$merged = array_combine($editfields, $types);

		$form = $this->itemform($merged);
		$form->action(url("admin/{$this->table}/store"));

		return new View('edit', ['editfields' => $merged, 'form' => $form], __DIR__.'/views/');
	}

	public function store()
	{
		$tableconf = $this->getTableConfig();

		if (!isset($tableconf['editable']))
			throw new AdminException('No editable fields are defined!');

		$editfields = $tableconf['editable'];
		$types = [];

		foreach ($editfields as $column)
		{
			$types[] = TypeToInput::make(SQL::table($this->table)->getColumnType($column));
		}

		$merged = array_combine($editfields, $types);

		$form = $this->itemform($merged);

		SQL::table($this->table)->insert(Input::except('_token', 'save'));

		Redirect::to(url("admin/{$this->table}"));
	}

	public function delete($id)
	{
		$item = SQL::table($this->table)->where('id', $id)->first();
		$action = url("admin/{$this->table}/postdelete/$id");

		return new View('delete', ['item' => $item, 'action' => $action, 'displayed' => $this->config->tables()[$this->table]['displayed']], __DIR__.'/views/');
	}

	public function postdelete($id)
	{
		SQL::table($this->table)->where('id', $id)->delete();

		Redirect::to(url("admin/{$this->table}"));
	}

	public function actionIsAllowed()
	{
		return true;
	}

	public function __call($tablename, $args)
	{
		$tables = $this->config->tables();
		
		$this->table = $tablename;

		if (isset($args[0]) && method_exists($this, $args[0]))
		{
			$method = array_shift($args);
			return call_user_func_array([$this, $method], $args);
		}

		if (isset($tables[$tablename]))
		{
			$table = $tables[$tablename];

			if (isset($table['pagination']) && $table['pagination'] == true)
			{
				$perpage = isset($table['perpage']) ? $table['perpage'] : 10;
				$pagination = $this->makePagination($perpage, $table, $tablename);
				$items = $pagination->getItems();
			}
			else
			{
				$items = $this->getItems($table, $tablename); 
				$pagination = '';
			}

			$logoutlink = $this->config->logoutlink();

			return new View('index', ['logoutlink' => $logoutlink, 'items' => $items, 'pagination' => $pagination, 'tableconf' => $table, 'nav' => $this->config->getNav(), 'tablename' => $tablename], $this->config->templateDir());
		}
		else
			throw new AdminException("Action \"$func\" has not been configured!");
	}

	private function getItems(array $tableconf, $tablename, callable $otherfilter = NULL, $returnquery = false)
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

		if ($returnquery)
			return $items;

		$items = $items->get();

		return $items;
	}

	public function getTableConfig()
	{
		return $this->config->tables()[$this->table];
	}

	public function cleanThumbs()
	{
		$files = glob(public_path('tempimages/*'));

		foreach($files as $file)
		{
			if (is_file($file))
				unlink($file);
		}
	}

	public function makePagination($perpage, $tableconf, $tablename)
	{
		$items = $this->getItems($tableconf, $tablename, NULL, true);
		$paginator = new Paginator($items, $perpage);
		return $paginator;
	}
}