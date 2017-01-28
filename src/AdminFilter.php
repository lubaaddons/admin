<?php

namespace Luba;

use Input;
use Flo\MySQL\MySQL;

class AdminFilter
{
	protected $filters = [];

	public function select($name, $dbcol, $values = [], $cond = '=')
	{
		if (!isset($values[0]) && !isset($values['0']))
			$values = ['0' => 'All'] + $values;

		$this->filters[] = ['type' => 'select', 'name' => $name, 'col' => $dbcol, 'values' => $values, 'condition' => $cond];
	}

	public function text($name, $dbcol, $cond = 'LIKE')
	{
		$this->filters[] = ['type' => 'text', 'name' => $name, 'col' => $dbcol, 'condition' => $cond];
	}

	public function custom($name, $dbcol, $query)
	{
		
	}

	public function render()
	{
		$str = [];

		foreach ($this->filters as $filter)
		{
			$name = $filter['name'];
			$col = $filter['col'];

			$str[] = "<div class=\"fieldrow\">";

			$input = Input::get($col) ?: NULL;

			if ($filter['type'] == 'select')
			{
				$values = $filter['values'];
				$str[] = "<label for=\"$col\">$name</label>";
				$str[] = "<select id=\"$col\" name=\"$col\">";

				foreach ($values as $key => $value)
				{
					$selected = $input == $key ? 'selected' : '';
					$str[] = "<option value=\"$key\" $selected>$value</option>";
				}

				$str[] = "</select>";
			}
			elseif ($filter['type'] == 'text')
			{
				$str[] = "<label for\"$col\">$name</label>";
				$str[] = "<input type=\"text\" placeholder=\"Search...\" name=\"$col\" id=\"$col\" value=\"$input\">";
			}

			$str[] = "</div>";
		}

		return implode("\r\n", $str);
	}

	public function __tostring()
	{
		return $this->render();
	}

	public function filter(MySQL $items)
	{
		foreach ($this->filters as $filter)
		{
			$input = Input::get($filter['col']);

			if ($filter['condition'] == 'like' or $filter['condition'] == 'LIKE')
				$input = "%$input%";

			if ($input && $input != '' && !is_null($input) && $input != '0' && $input !== 0)
				$items = $items->where($filter['col'], $filter['condition'], $input);
		}

		return $items;
	}
}