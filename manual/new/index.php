<?php
error_reporting(E_ALL);

$includePath = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'lib' . PATH_SEPARATOR
             . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . PATH_SEPARATOR
             . dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'vendor';

set_include_path($includePath);

require_once('Doctrine.php');
require_once('Sensei/Sensei.php');

spl_autoload_register(array('Doctrine', 'autoload'));
spl_autoload_register(array('Sensei', 'autoload'));
spl_autoload_register('autoload');

/**
 * A generic autoload function
 * 
 * Filename is generated from class name by replacing underscores with
 * directory separators and by adding a '.php' extension.
 * 
 * Then the filename is searched from include paths, and if found it is
 * included with require_once().
 *
 * @param $class string  class name to be loaded
 * @return bool  true if a class was loaded, false otherwise
 */
function autoload($class)
{
    if (class_exists($class, false)) {
        return false;
    }
    
    $paths = explode(PATH_SEPARATOR, get_include_path());
    $filename = str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
   
    foreach($paths as $path) {
        if (file_exists($path . DIRECTORY_SEPARATOR . $filename)) {
            require_once($filename);
            return true;
        }
    }
    
    return false;
}

/**
 * Returns the revision of a SVN controlled file.
 *
 * The revision is acquired by executing the 'svn info' command for the file and
 * parsing the last changed revision from the output.
 *
 * @param $file string filename
 * @return int|false revision of the file, or false on failure
 */
function getSvnRevision($file)
{
    $cmd = 'HOME=/tmp /usr/bin/svn info ' . escapeshellarg($file);
    exec($cmd, $output);
    foreach ($output as $line) {
        if (preg_match('/^Last Changed Rev: ([0-9]+)$/', $line, $matches)) {
            return $matches[1];
        }
    }
    
    return false;
}

/**
 * Wraps a Doctrine_Cache_Db and suppresses all exceptions thrown by caching
 * operations. Uses Sqlite as database backend.
 */
class Cache
{
    protected $_cache = null;
    
    /**
     * Constructs a cache object.
     * 
     * If cache table does not exist, creates one.
     *
     * @param $cacheFile string  filename of the sqlite database
     */
    public function __construct($cacheFile)
    {
        try {
            $dsn = 'sqlite:' . $cacheFile;
            $dbh = new PDO($dsn);
            $conn = Doctrine_Manager::connection($dbh);
            
            $options = array(
                'connection' => $conn,
                'tableName' => 'cache'
            );

            $this->_cache = new Doctrine_Cache_Db($options);
            
            try {
                $this->_cache->createTable();
            } catch (Doctrine_Connection_Exception $e) {
                if ($e->getPortableCode() !== Doctrine::ERR_ALREADY_EXISTS) {
                    $this->_cache = null;
                }
            }

        } catch (Exception $e) {
            $this->_cache = null;            
        }
    }
    
    /**
     * Fetches a cache record from cache.
     *
     * @param $id string  the id of the cache record
     * @return string  fetched cache record, or false on failure
     */
    public function fetch($id)
    {
        if ($this->_cache !== null) {
            try {
                return $this->_cache->fetch($id);
            } catch (Exception $e) {
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * Saves a cache record to cache.
     *
     * @param $data mixed  the data to be saved to cache
     * @param $id  string  the id of the cache record
     * @return bool  True on success, false on failure
     */
    public function save($data, $id)
    {
        if ($this->_cache !== null) {
            try {
                return $this->_cache->save($data, $id);
            } catch (Exception $e) {
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * Deletes all cached records from cache.
     *
     * @return True on success, false on failure
     */
    public function deleteAll()
    {
        if ($this->_cache !== null) {
            try {
                return $this->_cache->deleteAll();
            } catch (Exception $e) {
                return false;
            }
        }
        
        return false;
    }
}

// Temporary directory used by cache and LaTeX to Pdf conversion
$tempDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tmp';

// The file where cached data is saved
$cacheFile = $tempDir . DIRECTORY_SEPARATOR . 'cache.sq3';
       
$cache = new Cache($cacheFile);

// Fetch the revision of cached data
$cacheRev = $cache->fetch('revision');

// Check the revision of documentation files
$revision = getSvnRevision('.');

// Is current SVN revision greater than the revision of cached data?
if ($revision > $cacheRev) {
    $cache->deleteAll(); // cached data is not valid anymore
    $cache->save($revision, 'revision');
}

// Load table of contents from cache
$toc = $cache->fetch('toc');

// If table of contents was not cached, parse it from documentation files
if ( ! $toc instanceof Sensei_Doc_Toc) {
    $toc = new Sensei_Doc_Toc('docs/en.txt');
    $cache->save($toc, 'toc');
}
    
// Which format to output docs
if (isset($_GET['format'])) {
    $format = ucfirst(strtolower($_GET['format']));
    
    switch ($format) {
        case 'Xhtml':
        case 'Latex':
        case 'Pdf':
        break;
        default:
            $format = 'Xhtml';  // default if invalid format is specified
        break;
    }
    
} else {
    $format = 'Xhtml';  // default if no format is specified
}

$rendererClass = 'Sensei_Doc_Renderer_' . $format;
$renderer = new $rendererClass($toc);

$renderer->setOptions(array(
    'title'    => 'Doctrine Manual',
    'author'   => 'Konsta Vesterinen',
    'version'  => 'Rev. ' . $revision,
    'subject'  => 'Object relational mapping',
    'keywords' => 'PHP, ORM, object relational mapping, Doctrine, database'
));

$cacheId = $format;

switch ($format) {
    case 'Latex':
        $renderer->setOption('template', file_get_contents('templates/latex.tpl.php'));

        $headers = array(
            'Content-Type: application/latex',
            'Content-Disposition: attachment; filename=doctrine-manual.tex'
        );
    break;
    
    case 'Pdf':
        $renderer->setOption('template', file_get_contents('templates/latex.tpl.php'));

        $renderer->setOptions(array(
            'temp_dir'      => $tempDir,
            'pdflatex_path' => '/usr/bin/pdflatex',
            'lock'          => true
        ));
        
        $headers = array(
            'Content-Type: application/pdf',
            'Content-Disposition: attachment; filename=doctrine-manual.pdf'
        );
    break;
    
    case 'Xhtml':
    default:
        $renderer->setOption('template', file_get_contents('templates/xhtml.tpl.php'));
        
        $viewIndex = true;

        if (isset($_GET['one-page'])) {
            $viewIndex = false;
        }
        
        if (isset($_GET['chapter'])) {
            $section = $toc->findByPath($_GET['chapter']);
            
            if ($section && $section->getLevel() === 1) {
                $title = $renderer->getOption('title') . ' - Chapter '
                       . $section->getIndex() . ' ' . $section->getName();
                       
                $renderer->setOptions(array(
                    'section'    => $section,
                    'url_prefix' => '?chapter=',
                    'title'      => $title
                ));
                
                $cacheId .= '-' . $section->getPath();
                $viewIndex = false;
            } 
        }
    break;
    
}

if (isset($viewIndex) && $viewIndex) {

    $title = $renderer->getOption('title');
    include 'templates/index.tpl.php';

} else {
    $output = $cache->fetch($cacheId);
    
    if ($output === false) {
        try {
            $output = $renderer->render();
        } catch (Exception $e) {
            die($e->getMessage());
        }
        $cache->save($output, $cacheId);
    }
    
    if (isset($headers)) {
        foreach ($headers as $header) {
            header($header);
        }
    }   
    
    // buffer output
    ob_start();
    echo $output;
    ob_end_flush();
    
}

