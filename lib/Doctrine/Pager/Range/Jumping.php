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
 * <http://www.phpdoctrine.com>.
 */

Doctrine::autoload('Doctrine_Pager_Range');

/**
 * Doctrine_Pager_Range_Jumping
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @package     Doctrine
 * @subpackage  Pager
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @link        www.phpdoctrine.org
 * @since       0.9
 */
class Doctrine_Pager_Range_Jumping extends Doctrine_Pager_Range
{
    /**
     * @var int $_chunkLength     Chunk length to be returned
     */
    private $_chunkLength;


    /**
     * _initialize
     *
     * Initialize Doctrine_Pager_Range_Jumping and does custom assignments
     *
     * @return void
     */
    protected function _initialize()
    {
        if (isset($this->options['chunk'])) {
            $this->_setChunkLength($this->options['chunk']);
        } else {
            throw new Doctrine_Pager_Exception('Missing parameter \'chunk\' that must be define in options.');
        }
    }


    /**
     * getChunkLength
     *
     * Returns the size of the chunk defined
     *
     * @return int        Chunk length
     */
    public function getChunkLength()
    {
        return $this->_chunkLength;
    }


    /**
     * _setChunkLength
     *
     * Defines the size of the chunk
     *
     * @param $chunkLength       Chunk length
     * @return void
     */
    protected function _setChunkLength($chunkLength)
    {
        $this->_chunkLength = $chunkLength;
    }


    /**
     * rangeAroundPage
     *
     * Calculate and returns an array representing the range around the current page
     *
     * @return array
     */
    public function rangeAroundPage()
    {
        $pager = $this->getPager();

        if ($pager->getExecuted()) {
            $page = $pager->getPage();

            // Define initial assignments for StartPage and EndPage
            $startPage = $page - ($page - 1) % $this->getChunkLength();
            $endPage = ($startPage + $this->getChunkLength()) - 1;

            // Check for EndPage out-range
            if ($endPage > $pager->getLastPage()) {
                $endPage = $pager->getLastPage();
            }

            // No need to check for out-range in start, it will never happens

            return range($startPage, $endPage);
        }

        throw new Doctrine_Pager_Exception(
            'Cannot retrieve the range around the page of a not yet executed Pager query'
        );
    }
}
