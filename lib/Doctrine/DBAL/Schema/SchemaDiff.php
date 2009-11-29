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
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\DBAL\Schema;

/**
 * Schema Diff
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class SchemaDiff
{
    /**
     * All added tables
     *
     * @var array(string=>ezcDbSchemaTable)
     */
    public $newTables = array();

    /**
     * All changed tables
     *
     * @var array(string=>ezcDbSchemaTableDiff)
     */
    public $changedTables = array();

    /**
     * All removed tables
     *
     * @var array(string=>Table)
     */
    public $removedTables = array();

    /**
     * @var array
     */
    public $newSequences = array();

    /**
     * @var array
     */
    public $changedSequences = array();

    /**
     * @var array
     */
    public $removedSequences = array();

    /**
     * Constructs an SchemaDiff object.
     *
     * @param array(string=>Table)      $newTables
     * @param array(string=>TableDiff)  $changedTables
     * @param array(string=>bool)                  $removedTables
     */
    public function __construct( $newTables = array(), $changedTables = array(), $removedTables = array() )
    {
        $this->newTables = $newTables;
        $this->changedTables = $changedTables;
        $this->removedTables = $removedTables;
    }
}
