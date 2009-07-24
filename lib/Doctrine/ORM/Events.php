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

namespace Doctrine\ORM;

/**
 * Container for all ORM events.
 *
 * This class cannot be instantiated.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
final class Events
{
    private function __construct() {}
    /**
     * The preRemove event occurs for a given entity before the respective
     * EntityManager remove operation for that entity is executed.
     * 
     * This is an entity lifecycle event.
     * 
     * @var string
     */
    const preRemove = 'preRemove';
    /**
     * The postRemove event occurs for an entity after the entity has 
     * been deleted. It will be invoked after the database delete operations.
     * 
     * This is an entity lifecycle event.
     * 
     * @var string
     */
    const postRemove = 'postRemove';
    /**
     * The prePersist event occurs for a given entity before the respective
     * EntityManager persist operation for that entity is executed.
     * 
     * This is an entity lifecycle event.
     * 
     * @var string
     */
    const prePersist = 'prePersist';
    /**
     * The postPersist event occurs for an entity after the entity has 
     * been made persistent. It will be invoked after the database insert operations.
     * Generated primary key values are available in the postPersist event.
     * 
     * This is an entity lifecycle event.
     * 
     * @var string
     */
    const postPersist = 'postPersist';
    /**
     * The preUpdate event occurs before the database update operations to 
     * entity data. 
     * 
     * This is an entity lifecycle event.
     * 
     * @var string
     */
    const preUpdate = 'preUpdate';
    /**
     * The postUpdate event occurs after the database update operations to 
     * entity data. 
     * 
     * This is an entity lifecycle event.
     * 
     * @var string
     */
    const postUpdate = 'postUpdate';
    /**
     * The postLoad event occurs for an entity after the entity has been loaded
     * into the current EntityManager from the database or after the refresh operation
     * has been applied to it.
     * 
     * This is an entity lifecycle event.
     * 
     * @var string
     */
    const postLoad = 'postLoad';
    /**
     * The loadClassMetadata event occurs after the mapping metadata for a class
     * has been loaded from a mapping source (annotations/xml/yaml).
     * 
     * @var string
     */
    const loadClassMetadata = 'loadClassMetadata';
}