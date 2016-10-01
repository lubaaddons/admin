<?php

namespace Luba;

class AdminCustomLink
{
	public $ajax = false;

	public $url = '';

	public $attributes = [];

	public $name = '';

    public $target = '';

	public function ajax($ajax = true)
	{
		$this->ajax = $ajax;
	}

    public function target($target = '')
    {
        $this->target = $target;
    }

	public function url($url = '')
	{
		$this->url = $url;
	}

	public function attributes($attr = [])
	{
		$this->attributes = $attr;
	}

	public function render()
	{
		$linkattr = [];

		foreach ($this->attributes as $attr => $value)
		{
			$linkattr[] = "$attr=\"$value\"";
		}

		$linkattr = implode(' ', $linkattr);
		$ajax = $this->ajax ? 'data-behaviour="ajax"' : '';
        $target = $this->target ? 'target="'.$this->target.'"' : '';

		return "<a href=\"{$this->url}\" $ajax $target $linkattr>{$this->name}</a>";
	}

	public function __tostring()
	{
		return $this->render();
	}
}