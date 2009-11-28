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
 * Table Diff
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 */
class TableDiff
{
    /**
     * All added fields
     *
     * @var array(string=>ezcDbSchemaField)
     */
    public $addedFields;

    /**
     * All changed fields
     *
     * @var array(string=>ezcDbSchemaField)
     */
    public $changedFields;

    /**
     * All removed fields
     *
     * @var array(string=>bool)
     */
    public $removedFields;

    /**
     * All added indexes
     *
     * @var array(string=>ezcDbSchemaIndex)
     */
    public $addedIndexes;

    /**
     * All changed indexes
     *
     * @var array(string=>ezcDbSchemaIndex)
     */
    public $changedIndexes;

    /**
     * All removed indexes
     *
     * @var array(string=>bool)
     */
    public $removedIndexes;

    /**
     * Constructs an TableDiff object.
     *
     * @param array(string=>Column) $addedFields
     * @param array(string=>Column) $changedFields
     * @param array(string=>bool)             $removedFields
     * @param array(string=>Index) $addedIndexes
     * @param array(string=>Index) $changedIndexes
     * @param array(string=>bool)             $removedIndexes
     */
    function __construct( $addedFields = array(), $changedFields = array(),
            $removedFields = array(), $addedIndexes = array(), $changedIndexes =
            array(), $removedIndexes = array() )
    {
        $this->addedFields = $addedFields;
        $this->changedFields = $changedFields;
        $this->removedFields = $removedFields;
        $this->addedIndexes = $addedIndexes;
        $this->changedIndexes = $changedIndexes;
        $this->removedIndexes = $removedIndexes;
    }
}