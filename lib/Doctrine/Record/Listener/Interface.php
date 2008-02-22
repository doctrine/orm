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
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine_Record_Listener
 *
 * @package     Doctrine
 * @subpackage  Record
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
interface Doctrine_Record_Listener_Interface
{
    public function preSerialize(Doctrine_Event $event);

    public function postSerialize(Doctrine_Event $event);

    public function preUnserialize(Doctrine_Event $event);

    public function postUnserialize(Doctrine_Event $event);

    public function preSave(Doctrine_Event $event);

    public function postSave(Doctrine_Event $event);

    public function preDelete(Doctrine_Event $event);

    public function postDelete(Doctrine_Event $event);

    public function preUpdate(Doctrine_Event $event);

    public function postUpdate(Doctrine_Event $event);

    public function preInsert(Doctrine_Event $event);

    public function postInsert(Doctrine_Event $event);
    
    public function preHydrate(Doctrine_Event $event);
    
    public function postHydrate(Doctrine_Event $event);
}
