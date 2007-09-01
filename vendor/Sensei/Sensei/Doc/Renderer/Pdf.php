<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://sourceforge.net/projects/sensei>.
 */

/**
 * Sensei_Doc_Renderer_Pdf
 *
 * @package     Sensei_Doc
 * @category    Documentation
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL
 * @link        http://sourceforge.net/projects/sensei
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @version     $Revision$
 * @since       1.0
 */
class Sensei_Doc_Renderer_Pdf extends Sensei_Doc_Renderer
{
    protected $_options = array(
        'temp_dir'       => '/tmp',
        'pdflatex_path'  => '/usr/bin/pdflatex',
        'lock'           => false
    );
    
    protected static $_tmpFileExtensions = array('tex', 'aux', 'out', 'log', 'toc', 'pdf');
    
    protected $_tmpFilename;

    
    public function __construct(Sensei_Doc_Toc $toc, array $options = array())
    {
        parent::__construct($toc, $options);
    }
    
    public function testRequirements()
    {
        exec($this->_options['pdflatex_path'], $output);
        
        if ( ! isset($output[0]) || ! preg_match('/^This is pdfe?TeXk?/', $output[0])) {
            $message = 'pdfLaTeX does not seem to be installed, or pdflatex_path'
                     . ' option does not point to pdfLaTeX executable.';
            throw new Sensei_Doc_Renderer_Exception($message);
        }
    }
    
    /**
     * Deletes temporary files generated during LaTeX to PDF conversion.
     */
    protected function _cleanUp()
    {
        foreach (self::$_tmpFileExtensions as $extension) {
            $filename = $this->_options['temp_dir'] . DIRECTORY_SEPARATOR
                      . $this->tmpFilename . '.' . $extension;
            @unlink($filename);
        }   
    }

    public function render()
    {
        $this->testRequirements();
        
        // Filename to be used by temporary files       
        $this->tmpFilename = md5($this->_options['title']);
        
        // Change working directory to the temporary directory
        $currentDir = getcwd();
        if ( ! @chdir($this->_options['temp_dir'])) {
            throw new Sensei_Doc_Renderer_Exception('Could not change to temporary directory.');
        }
        
        if ($this->_options['lock']) {
            $lockFile = $this->tmpFilename . '.lock';
            
            // Check if lock exists
            if (file_exists($lockFile)) {
                throw new Sensei_Doc_Renderer_Exception('Pdf is being generated at the moment.');
            }
            
            // Create a lock (just an empty file)
            if (($fp = @fopen($lockFile, 'w')) === false) {
                throw new Sensei_Doc_Renderer_Exception('Could not create a lock file.');
            }
            fclose($fp);
        }

        $latexRenderer = new Sensei_Doc_Renderer_Latex($this->_toc);
        
        // Set the options of the Latex renderer to be the same as this instance
        // of PDF renderer.
        foreach ($this->_options as $option => $value) {
            try {
                $latexRenderer->setOption($option, $value);
            } catch (Exception $e){
                // Do nothing. Latex renderer does not have all options of PDF
                // renderer.
            }
        }

        // Render the wiki source to latex
        $latex = $latexRenderer->render();

        // Open a temporary file for writing the latex source to
        if ( ! @file_put_contents($this->tmpFilename . '.tex', $latex, LOCK_EX)) {
            $this->_cleanUp();  // delete temporary files
            throw new Sensei_Doc_Renderer_Exception('Could not write latex source to a temporary file.');
        }

        // Run pdfLaTeX to create the PDF file.
        $command = $this->_options['pdflatex_path'] . ' -interaction=nonstopmode '
                 . $this->tmpFilename . '.tex'; 
        exec($command);
        
        // Second run generates table of contents
        exec($command);
        
        // Since table of contents is multiple pages long, we need a third run
        // in order to fix incorrect page numbers in table of contents
        exec($command);
        
        // Read the generated PDF file
        $pdf = @file_get_contents($this->tmpFilename . '.pdf');
        
        if ($pdf === false) {
            $this->_cleanUp();  // delete temporary files
            throw new Sensei_Doc_Renderer_Exception('An error occured during the Latex to PDF conversion.');
        }
        
        // Delete temporary files
        $this->_cleanUp();
        
        // Remove lock file
        if ($this->_options['lock']) {
            @unlink($lockFile);
        }
        
        // Switch back to the previous working directory
        chdir($currentDir);
        
        return $pdf;
    }
    
}
