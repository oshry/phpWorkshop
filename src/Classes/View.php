<?php //defined('SYSPATH') OR die('No direct script access.');
/**
 * Acts as an object wrapper for HTML pages with embedded PHP, called "views".
 * Variables can be assigned with the view object and referenced locally within
 * the view.
 **/
namespace Classes;

class View {

    // Array of global variables
    protected static $_global_data = array();

    /**
     * Returns a new View object. If you do not define the "file" parameter,
     * you must call [View::set_filename].
     *
     *     $view = View::factory($file);
     *
     * @param   string  $file   view filename
     * @param   array   $data   array of values
     * @return  View
     */
    public static function factory($file = NULL, array $data = NULL)
    {
        return new View($file, $data);
    }

    /**
     * Captures the output that is generated when a view is included.
     * The view data will be extracted to make local variables. This method
     * is static to prevent object scope resolution.
     *
     *     $output = View::capture($file, $data);
     *
     * @param   string  $view_filename   filename
     * @param   array   $view_data       variables
     * @return  string
     * @throws  Exception
     */
    public static function convert_array_to_obj_recursive($a) {
        if (is_array($a) ) {
            foreach($a as $k => $v) {
                if (is_integer($k)) {
                    // only need this if you want to keep the array indexes separate
                    // from the object notation: eg. $o->{1}
                    $a['index'][$k] = self::convert_array_to_obj_recursive($v);
                }
                else {
                    $a[$k] = self::convert_array_to_obj_recursive($v);
                }
            }

            return (object) $a;
        }

        // else maintain the type of $a
        return $a;
    }
    protected static function capture($view_filename, array $view_data)
    {
        View::set_global($view_data);
        extract(View::$_global_data, EXTR_SKIP);
        $doc = View::convert_array_to_obj_recursive($doc);

        // Capture the view output
        ob_start();

        try
        {
            // Load the view within the current scope
            include $view_filename;
        }
        catch (Exception $e)
        {
            // Delete the output buffer
            ob_end_clean();

            // Re-throw the exception
            throw $e;
        }

        // Get the captured output and close the buffer
        return ob_get_clean();
    }

    /**
     * Sets a global variable, similar to [View::set], except that the
     * variable will be accessible to all views.
     *
     *     View::set_global($name, $value);
     *
     * You can also use an array or Traversable object to set several values at once:
     *
     *     // Create the values $food and $beverage in the view
     *     View::set_global(array('food' => 'bread', 'beverage' => 'water'));
     *
     * [!!] Note: When setting with using Traversable object we're not attaching the whole object to the view,
     * i.e. the object's standard properties will not be available in the view context.
     *
     * @param   string|array|Traversable  $key    variable name or an array of variables
     * @param   mixed                     $value  value
     * @return  void
     */
    public static function set_global($key, $value = NULL)
    {
        if (is_array($key) OR $key instanceof Traversable)
        {
            foreach ($key as $name => $value)
            {
                View::$_global_data[$name] = $value;
            }
        }
        else
        {
            View::$_global_data[$key] = $value;
        }
    }

    /**
     * Assigns a global variable by reference, similar to [View::bind], except
     * that the variable will be accessible to all views.
     *
     *     View::bind_global($key, $value);
     *
     * @param   string  $key    variable name
     * @param   mixed   $value  referenced variable
     * @return  void
     */
    public static function bind_global($key, & $value)
    {
        View::$_global_data[$key] =& $value;
    }

    // View filename
    protected $_file;

    // Array of local variables
    protected $_data = array();

    /**
     * Sets the initial view filename and local data. Views should almost
     * always only be created using [View::factory].
     *
     *     $view = new View($file);
     *
     * @param   string  $file   view filename
     * @param   array   $data   array of values
     * @uses    View::set_filename
     */
    public function __construct($file = NULL, array $data = NULL)
    {
        if ($file !== NULL)
        {
            $this->set_filename($file);
        }

        if ($data !== NULL)
        {
            // Add the values to the current data
            $this->_data = $data + $this->_data;
        }
    }

    /**
     * Magic method, searches for the given variable and returns its value.
     * Local variables will be returned before global variables.
     *
     *     $value = $view->foo;
     *
     * [!!] If the variable has not yet been set, an exception will be thrown.
     *
     * @param   string  $key    variable name
     * @return  mixed
     * @throws  Exception
     */
    public function & __get($key)
    {
        if (array_key_exists($key, $this->_data))
        {
            return $this->_data[$key];
        }
        elseif (array_key_exists($key, View::$_global_data))
        {
            return View::$_global_data[$key];
        }
        else
        {
//            throw new Exception('View variable is not set: :var',
//                array(':var' => $key));
        }
    }

    /**
     * Magic method, calls [View::set] with the same parameters.
     *
     *     $view->foo = 'something';
     *
     * @param   string  $key    variable name
     * @param   mixed   $value  value
     * @return  void
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Magic method, determines if a variable is set.
     *
     *     isset($view->foo);
     *
     * [!!] `NULL` variables are not considered to be set by [isset](http://php.net/isset).
     *
     * @param   string  $key    variable name
     * @return  boolean
     */
    public function __isset($key)
    {
        return (isset($this->_data[$key]) OR isset(View::$_global_data[$key]));
    }

    /**
     * Magic method, unsets a given variable.
     *
     *     unset($view->foo);
     *
     * @param   string  $key    variable name
     * @return  void
     */
    public function __unset($key)
    {
        unset($this->_data[$key], View::$_global_data[$key]);
    }

    /**
     * Magic method, returns the output of [View::render].
     *
     * @return  string
     * @uses    View::render
     */
    public function __toString()
    {
        try
        {
            return $this->render();
        }
        catch (Exception $e)
        {
            /**
             * Display the exception message.
             *
             * We use this method here because it's impossible to throw an
             * exception from __toString().
             */
            $error_response = Exception::_handler($e);
//
            return $error_response->body();
        }
    }

    /**
     * Sets the view filename.
     *
     *     $view->set_filename($file);
     *
     * @param   string  $file   view filename
     * @return  View
     * @throws  View_Exception
     */

    public function set_filename($file)
    {
        if (is_file(VIEW.$file.".php"))
        {
            // This path has a file, add it to the list
            // Store the file path locally
            $this->_file = VIEW.$file.".php";
        }else{
            die('The requested view :file could not be found'.VIEW.$file.".php");
        }



        return $this;
    }

    /**
     * Assigns a variable by name. Assigned values will be available as a
     * variable within the view file:
     *
     *     // This value can be accessed as $foo within the view
     *     $view->set('foo', 'my value');
     *
     * You can also use an array or Traversable object to set several values at once:
     *
     *     // Create the values $food and $beverage in the view
     *     $view->set(array('food' => 'bread', 'beverage' => 'water'));
     *
     * [!!] Note: When setting with using Traversable object we're not attaching the whole object to the view,
     * i.e. the object's standard properties will not be available in the view context.
     *
     * @param   string|array|Traversable  $key    variable name or an array of variables
     * @param   mixed                     $value  value
     * @return  $this
     */
    public function set($key, $value = NULL)
    {
        if (is_array($key) OR $key instanceof Traversable)
        {
            foreach ($key as $name => $value)
            {
                $this->_data[$name] = $value;
            }
        }
        else
        {
            $this->_data[$key] = $value;
        }

        return $this;
    }

    /**
     * Assigns a value by reference. The benefit of binding is that values can
     * be altered without re-setting them. It is also possible to bind variables
     * before they have values. Assigned values will be available as a
     * variable within the view file:
     *
     *     // This reference can be accessed as $ref within the view
     *     $view->bind('ref', $bar);
     *
     * @param   string  $key    variable name
     * @param   mixed   $value  referenced variable
     * @return  $this
     */
    public function bind($key, & $value)
    {
        $this->_data[$key] =& $value;

        return $this;
    }

    /**
     * Renders the view object to a string. Global and local data are merged
     * and extracted to create local variables within the view file.
     *
     *     $output = $view->render();
     *
     * [!!] Global variables with the same key name as local variables will be
     * overwritten by the local variable.
     *
     * @param   string  $file   view filename
     * @return  string
     * @throws  View_Exception
     * @uses    View::capture
     */
    public function render($file = NULL)
    {
        if ($file !== NULL)
        {
            $this->set_filename($file);
        }

        if (empty($this->_file))
        {
//            throw new View_Exception('You must set the file to use within your view before rendering');
            die('You must set the file to use within your view before rendering');
        }

        // Combine local and global data and capture the output
//        die($this->_file);
//        die(print_r($this->_data));
        return View::capture($this->_file, $this->_data);
    }

}

