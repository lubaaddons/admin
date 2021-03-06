<?php

namespace Luba;

use Luba\Framework\View;
use Flo\MySQL\MySQLResult;
use SQL;
use Redirect;
use Form;
use Input;
use URL;
use Auth;
use Session;
use Luba\Helpers\Header;
use Luba\Framework\Paginator;
use Luba\Excel;
use Flo\MySQL\Collection;
use Luba\Framework\Controller;
use Luba\Exceptions\PermissionDeniedException;

class AdminBackend extends Controller
{
	/**
	 * Admin Config
	 *
	 * @var Luba\AdminConfig
	 */
	protected $config;

	/**
	 * Current selected table
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * Disable controller method check
	 *
	 * @var bool
	 */
	protected $global = true;

	/**
	 * Route used to access admin interface
	 *
	 * @var string
	 */
	protected $adminroute = 'admin';

	/**
	 * Constructor
	 *
	 * @param array|AdminConfig $config
	 */
	public function __construct($config)
	{
		if (is_array($config))
			$config = AdminConfig::make($config);

		$this->config = $config;

        try
        {
            $this->config->authenticate();
        }
        catch (PermissionDeniedException $e)
        {
            if($loginlink = $this->config->loginlink()) {
                Redirect::to($loginlink);
                exit;
            } else {
                throw $e;
            }
        }
		if (!is_dir(public_path('tempimages')))
			mkdir(public_path('tempimages'));

		//$this->cleanThumbs();
	}

	/**
	 * Load the css style
	 *
	 * @return View
	 */
    public function css()
    {
        Header::contentType('text/css');
        return new View('admincss', [], __DIR__.'/views/');
    }

    public function assets()
    {
        $filename = __DIR__.'/assets/'.implode("/", func_get_args());
        if(file_exists($filename)){
            $ext = pathinfo($filename, PATHINFO_EXTENSION);

            $mimes = new \Mimey\MimeTypes;
            $mimetype = $mimes->getMimeType($ext);

            header('Content-Type: ' . $mimetype);

            //No cache
            // header('Expires: 0');
            // header('Cache-Control: must-revalidate');
            // header('Pragma: public');
            //Define file size
            header('Content-Length: ' . filesize($filename));

            ob_clean();
            flush();
            readfile($filename);
            exit;
        }
    }

    /**
     * Get the dashboard page
     *
     * @return View
     */
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

	/**
	 * Get the create form
	 *
	 * @return View
	 */
	public function create()
	{
		$form = $this->itemform();
		$form->action(url("admin/{$this->table}/store"));

        if(isset($_GET['camefrom']))
            Session::set('__camefrom', $_GET['camefrom']);

		return new View('edit', ['form' => $form], __DIR__.'/views/');
	}

	/**
	 * Get the edit form
	 *
	 * @param int $id
	 * @return View
	 */
	public function edit($id)
	{
		$item = SQL::table($this->table)->find($id);
        $data = $this->fillValues($item);
		$form = $this->itemform($data);
		$form->action(url("admin/{$this->table}/update/$id"));

        if(isset($_GET['camefrom']))
            Session::set('__camefrom', $_GET['camefrom']);

		return new View('edit', ['item' => $item, 'form' => $form], __DIR__.'/views/');
	}

    public function fillValues($item) {
        $data = $item->toArray();
        $editfields = $this->getEditFields();

        foreach ($editfields as $key => $value)
        {
            if(isset($value['getvalues']))
            {
                $data[$key] = $value['getvalues']($item);
            }
        }

        return $data;
    }

	/**
	 * Get the Edit / Add form fields
	 *
	 * @return array
	 */
    public function getFieldType($key, $formdef)
    {
        if(is_array($formdef))
        {
            $type = $formdef['type'];
        }
        else
        {
            $type = TypeToInput::make(SQL::table($this->table)->getColumnType($key));
        }

        return $type;
    }

    public function getEditFields()
    {
        $tableconf = $this->getTableConfig();
        if (!isset($tableconf['editable']))
            throw new AdminException('No editable fields are defined!');
        $editfields = $tableconf['editable'];
        return $editfields;
    }

    /**
     * Get the config for an edit field
     *
     * @param string $field
     * @return array
     */
    public function getEditConfig($field)
    {
        $editfields = $this->getEditFields();

        return isset($editfields[$field]) ? $editfields[$field] : [];
    }

    /**
     * Get the form for Edit / Add
     *
     * @param array $field
     * @param array $bindings
     * @return Form
     */
	public function itemform($bindings = false)
	{
		$form = new Form;
        $form->templates(__DIR__."/views/form/");

		if ($bindings)
			$form->bind($bindings);

        $editfields = $this->getEditFields();

        foreach ($editfields as $name => $formdef)
		{
            if(is_int($name)) {
                $name = $formdef;
            }
            $fieldtype = $this->getFieldType($name, $formdef);
            $attributes = [];
            $config = is_array($formdef)?$formdef:NULL;
            $label = $config?$config['name']:$formdef;

            if($config && isset($config['attributes']))
                $attributes = $config['attributes'];

            if($fieldtype == "file")
            {
                $form->$fieldtype($name, $attributes)->label(ucfirst($name));
            }
            elseif ($fieldtype == "select")
            {
            	$listings = $config['listings'];
            	$list = $listings();

            	if ($list instanceof Collection)
            		$list = $list->toArray();
            	else
            		$list = (array) $list;

            	if (isset($config['multiple']) && $config['multiple'])
                    $attributes["multiple"] = true;

        		$form->select($name, $list, NULL, $attributes)->label($label);
            }
            elseif ($fieldtype == 'password')
            {
            	$form->password($name.'__password', $attributes)->label($label);
            }
            else
            {
                $form->$fieldtype($name, null, $attributes)->label($label);
            }
		}

		$form->submit('save', 'Save', ['value' => 'Save']);

		return $form;
	}

	/**
	 * Get all fields that have a file
	 *
	 * @return array
	 */
    protected function getFileFields()
    {
        $editfields = $this->getEditFields();
        $filefields = [];

        foreach ($editfields as $key => $value)
        {
            if(is_array($value) && $value['type'] == 'file')
            {
                $filefields[$key] = $value;
            }
        }

        return $filefields;
    }

    /**
     * Get the input data
     *
     * @return array
     */
    protected function getInputData()
    {
        $inputs = Input::except('_token', 'save');

        $data = [];

        foreach ($inputs as $key => $value)
        {
        	if (stripos($key, '__password') !== false)
        	{
        		if (!$value or $value == '' or is_null($value))
        			continue;

        		$data[str_replace('__password', '', $key)] = Auth::hash($value);
        	}
        	else
        		$data[$key] = $value;
        }

        $filefields = $this->getFileFields();

        foreach($filefields as $name => $config)
        {
            if (Input::file($name))
            {
                $file = Input::file($name)->move(public_path($config['path']));
                $data[$name] = $config['path'].'/'.$file->fullName();
            }
            else
            {
                unset($data[$name]);
            }
        }

        //Get n-m-relations, relations data
        //$data["relations"] = ...

        return $data;
    }

    /**
     * Update an entry
     *
     * @param int $id
     * @return void
     */
    public function update($id)
    {
        $data = $this->getInputData();
        // dd($data);
        $editfields = $this->getEditFields();
        $specialfields = [];
        foreach ($editfields as $key => $value)
        {
            if(isset($value['setvalues']))
            {
                if(isset($data[$key])) {
                    $value['setvalues']($id, $data[$key]); //Manual save
                    unset($data[$key]);
                } else {
                    $value['setvalues']($id, null); //Manual save
                }
            }
        }
        SQL::table($this->table)->update($id, $data);

        //Save relations
        if($camefrom = Session::get('__camefrom')) {
            Redirect::to(urldecode($camefrom));
        } else {
            Redirect::to(url("admin/{$this->table}"));
        }

    }

    /**
     * Create an entry
     *
     * @return void
     */
	public function store()
	{
        $data = $this->getInputData();

        $editfields = $this->getEditFields();

        $savedata = [];

        foreach ($editfields as $key => $value)
        {
            if(isset($value['setvalues']))
            {
                $savedata[$key] = isset($data[$key])?$data[$key]:null;
                unset($data[$key]);
            }
        }

        $id = SQL::table($this->table)->insert($data);

        //Save after insert
        foreach ($editfields as $key => $value)
        {
            if(isset($value['setvalues']))
            {
                $value['setvalues']($id, $savedata[$key]);
            }
        }
        if($camefrom = Session::get('__camefrom')) {
            Redirect::to(urldecode($camefrom));
        } else {
    		Redirect::to(url("admin/{$this->table}"));
        }
	}

	/**
	 * Get the delete view
	 *
	 * @param int $id
	 * @return View
	 */
	public function delete($id)
	{
		$item = SQL::table($this->table)->where('id', $id)->first();
		$action = url("admin/{$this->table}/postdelete/$id");

		return new View('delete', ['item' => $item, 'action' => $action, 'displayed' => $this->config->tables()[$this->table]['displayed']], __DIR__.'/views/');
	}

	/**
	 * Delete an entry
	 *
	 * @param int $id
	 * @return void
	 */
	public function postdelete($id)
	{
		SQL::table($this->table)->where('id', $id)->delete();

		Redirect::to(url("admin/{$this->table}"));
	}

	/**
	 * Export a table
	 *
	 * @return file
	 */
    public function export()
    {
        $items = SQL::table($this->table)->get();

        $tables = $this->config->tables();
        $export_config = $tables[$this->table]['export'];
        $fields = $export_config['fields'];

        $excel = Excel::create()->fromDataCollection($items, $fields);
        return $excel->output($export_config['filename']);
    }

    /**
     * Catchall for all URLs called
     *
     * @param string $tablename
     * @param array $args
     */
	public function __call($tablename, $args)
	{
		// Get all table configs
		$tables = $this->config->tables();

		// Set the current table
		$this->table = $tablename;

		// Check if the table config exists
		if (isset($tables[$tablename]))
		{
			$table = $tables[$tablename];

			// Check permissions
			$auth = isset($table['auth']) ? $table['auth'] : NULL;

			if ($auth && $auth() === false)
				throw new PermissionDeniedException(URL::withoutParams());

			// Check if the called method is defined in controller
			if (isset($args[0]) && method_exists($this, $args[0]))
			{
				$method = array_shift($args);
				return call_user_func_array([$this, $method], $args);
			}

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

			// Set logout link
			$logoutlink = $this->config->logoutlink();

			// Set export link
            $exportlink = "";

            if(isset($table['export']))
            {
                $exportlink = url("admin/{$this->table}/export");
            }

            // Set import link
            $importlink = NULL;

            if (isset($table['import']))
            {
            	$i = $table['import']['action'];
            	$importlink = url("admin/$i");
            }

            // Custom links
            $otherlinks = [];

            if (isset($table['links']))
            {
            	foreach ($table['links'] as $callback)
            	{
            		$callback($customlink = new AdminCustomLink);
            		$otherlinks[] = $customlink;
            	}
            }

            $adminfilter = false;

            if (isset($table['filter']))
            {
            	$filters = $table['filter'];
            	$filters($adminfilter = new AdminFilter);
            }

            $title = isset($table['menuname']) ? $table['menuname'] : ucfirst($name);

            $currenturl = urlencode($_SERVER["REQUEST_URI"]);

            // Return view
			return new View('index', [

				'logoutlink'	=> $logoutlink,
				'items'			=> $items,
				'pagination'	=> $pagination,
				'tableconf'		=> $table,
				'nav'			=> $this->config->getNav(),
				'tablename'		=> $tablename,
                'title'         => $title,
				'exportlink'	=> $exportlink,
				'importlink'	=> $importlink,
				'otherlinks'	=> $otherlinks,
				'adminfilter'	=> $adminfilter,
                'currenturl'    => $currenturl

			], $this->config->templateDir());
		}
		else
			throw new AdminException("Action \"$tablename\" has not been configured!");
	}

	/**
	 * Get the items to show in the table view
	 *
	 * @param array $tableconf
	 * @param string $tablename
	 * @param callable $otherfilter
	 * @param bool $returnquery
	 * @return Flo\MySQL\Collection
	 */
	private function getItems(array $tableconf, $tablename, callable $otherfilter = NULL, $returnquery = false)
	{
		$select = [];

		$displayed = isset($tableconf['displayed']) ? $tableconf['displayed'] : '*';

		$primarykey = isset($tableconf['primarykey']) ? $tableconf['primarykey'] : $tablename.'.id';

		if ((!isset($displayed[$primarykey]) or array_search($primarykey, $displayed) === false) && array_search('*', $displayed) === false)
			$displayed = [$primarykey => mb_strtoupper($primarykey)] + $displayed;

		foreach ($displayed as $col => $title)
		{
			if (is_int($col))
				$select[] = $title;
			else {
                if(is_array($title) && isset($title["col"])) {
                    $select[] = "$col as $title[col]";
                } else {
                    $select[] = "$col";
                }
            }
		}

		$items = SQL::table($tablename)->select(implode(', ', $select));

		if (isset($tableconf['query']))
		{
			$customquery = $tableconf['query'];
			$customquery($items);
		}

		if ($otherfilter)
			$otherfilter($items);

		if (Input::get() && isset($tableconf['filter']))
		{
			$filters = $tableconf['filter'];
			$filters($adminfilter = new AdminFilter);
			$items = $adminfilter->filter($items);
		}
        // dd($items->toSql());
        // dd($items->get()->first());
		if ($returnquery)
			return $items;

        if(isset($tableconf["model"]))
            $items = $items->toModel($tableconf["model"]);
        else
		    $items = $items->get();
		return $items;
	}

	/**
	 * Get the table config
	 *
	 * @return array
	 */
	public function getTableConfig()
	{
		return $this->config->tables()[$this->table];
	}

	/**
	 * Clean the cretaed thumbnails
	 *
	 * @return void
	 */
	public function cleanThumbs()
	{
		$files = glob(public_path('tempimages/*'));

		foreach($files as $file)
		{
			if (is_file($file))
				unlink($file);
		}
	}

	/**
	 * Create the pagination
	 *
	 * @param int $perpage
	 * @param array $tableconf
	 * @param string $tablename
	 * @return Paginator
	 */
	public function makePagination($perpage, $tableconf, $tablename)
	{
		$items = $this->getItems($tableconf, $tablename, NULL, true);

        if(isset($tableconf["model"]))
		    $paginator = new Paginator($items, $perpage, "page", $tableconf["model"]);
        else
            $paginator = new Paginator($items, $perpage);

		return $paginator;
	}
}