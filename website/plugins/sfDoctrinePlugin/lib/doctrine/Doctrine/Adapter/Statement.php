<?php
/*
 *  $Id: Statement.php 1917 2007-07-01 11:27:45Z zYne $
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
/**
 * Doctrine_Adapter_Statement
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1917 $
 */
abstract class Doctrine_Adapter_Statement
{
    public function bindValue($no, $value)
    {
    }
    /**
     * fetch
     *
     * @see Doctrine::FETCH_* constants
     * @param integer $fetchStyle           Controls how the next row will be returned to the caller.
     *                                      This value must be one of the Doctrine::FETCH_* constants,
     *                                      defaulting to Doctrine::FETCH_BOTH
     *
     * @param integer $cursorOrientation    For a PDOStatement object representing a scrollable cursor, 
     *                                      this value determines which row will be returned to the caller. 
     *                                      This value must be one of the Doctrine::FETCH_ORI_* constants, defaulting to
     *                                      Doctrine::FETCH_ORI_NEXT. To request a scrollable cursor for your 
     *                                      Doctrine_Adapter_Statement_Interface object,
     *                                      you must set the Doctrine::ATTR_CURSOR attribute to Doctrine::CURSOR_SCROLL when you
     *                                      prepare the SQL statement with Doctrine_Adapter_Interface->prepare().
     *
     * @param integer $cursorOffset         For a Doctrine_Adapter_Statement_Interface object representing a scrollable cursor for which the
     *                                      $cursorOrientation parameter is set to Doctrine::FETCH_ORI_ABS, this value specifies
     *                                      the absolute number of the row in the result set that shall be fetched.
     *                                      
     *                                      For a Doctrine_Adapter_Statement_Interface object representing a scrollable cursor for 
     *                                      which the $cursorOrientation parameter is set to Doctrine::FETCH_ORI_REL, this value 
     *                                      specifies the row to fetch relative to the cursor position before 
     *                                      Doctrine_Adapter_Statement_Interface->fetch() was called.
     *
     * @return mixed
     */
    public function fetch()
    {
    }
    public function nextRowset()
    {
    }
    public function execute()
    {
    }
    public function errorCode()
    {
    }
    public function errorInfo()
    {
    }
    public function rowCount()
    {
    }
    public function setFetchMode($mode)
    {
    }
    public function columnCount()
    {
    }
}
