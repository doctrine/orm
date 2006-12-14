<?php
class Request {
    private $data = array();
    public function __construct() {
        $this->data['files'] = isset($_GET['files']) ? $_GET['files'] : null;
        $this->data['path'] = isset($_GET['path']) ? $_GET['path'] : null;
    
        foreach($this->data as $k => $v) {
            $this->data[$k] = stripslashes($v);
        }
    }
    public function __get($name) {
        if(isset($this->data[$name])) 
            return $this->data[$name];
        
        return null;
    }
}
function renderError($string) {
    print "<table width=500 border=1 class='error' cellpadding=0 cellspacing=0><tr><td>" . $string . "</td></tr></table>";
}
$request = new Request();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<HTML>
<HEAD>
<TITLE>Doctrine ORM Framework</TITLE>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=iso-8859-1">
<link rel="stylesheet" type="text/css" href="styles/basic.css">
</HEAD>

<table width="100%" cellspacing=0 cellpadding=0>
    <tr>
        <td width=50>
        <td>
        <td>
            <img src="images/logotext.jpg">
        </td>
    </tr>
</table>
<table width="100%" cellspacing=0 cellpadding=0>
    <tr>
        <td width=50>
        <td>
        <td align="left" valign="top">
            <table width="100%" cellspacing=1 cellpadding=1>
            <tr>
                <td colspan=2 bgcolor="white">
                <b class="title">Doctrine Export</b>
                <hr>
                <table width="700">
                    <tr>
                        <td>
                        This script is used for exporting existing Doctrine record classes to database schema. Doctrine tries to create database tables
                
                        according to definitions given in the records.
                        <table>
                        <form action="<?php print $_SERVER['PHP_SELF']; ?>" method='GET'>
                            <tr>
                            <td>Path  : </td><td><input class='big' type='text' name='path' value="<?php print $request->path; ?>"></td>
                            </tr>
                            <tr>
                            <td>Files : </td><td><textarea name='files' cols=47 rows=10><?php print $request->files; ?></textarea></td>
                            </tr>
                            <tr>
                            <td></td><td><input type='submit' value='export'></td>
                            </tr>
                        </form>
                        </table>
                        </td>
                    </tr>
                    <tr>
                        <td>
                        <?php
                            if(isset($_GET['files'])) {

                                $files = explode("\n", $_GET['files']);

                                if( ! is_dir($request->path))
                                    renderError('Directory \'' . $request->path . '\' does not exist');

                                foreach($files as $k => $file) {
                                    $file = $request->path . $file;

                                    if( ! file_exists($file)) {
                                        renderError('File \'' . $file . '\' does not exist');

                                        break;
                                    }
                                    $files[$k] = $file;
                                }
                                
                            }
                        ?>
                        </td>
                    </tr>
                </table>

                </td>

            </tr>
            </table>
        </td>
    </tr>
</table>
