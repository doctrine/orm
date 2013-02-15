Doctrine explained in 10 quick steps
====================================

You can follow this tutorial step by step yourself and end up with a simple
Doctrine application. It assumed that you installed Doctrine via Composer.
For more information take a look at the :doc:`Installation help
<../reference/introduction>`.

1. Allows you to map PHP Objects to database tables
---------------------------------------------------

.. code-block:: php

    <?php
    class Post
    {
        protected $id;
        protected $title;
        protected $body;
    }

::

   mysql> CREATE TABLE Post (id INT AUTO_INCREMENT PRIMARY KEY, title
   VARCHAR(255), body TEXT);

   mysql> DESCRIBE Post;
   +-------+--------------+------+-----+---------+----------------+
   | Field | Type         | Null | Key | Default | Extra          |
   +-------+--------------+------+-----+---------+----------------+
   | id    | int(11)      | NO   | PRI | NULL    | auto_increment |
   | title | varchar(255) | YES  |     | NULL    |                |
   | body  | text         | YES  |     | NULL    |                |
   +-------+--------------+------+-----+---------+----------------+

.. tip::

    Objects mapped with Doctrine are called Entities. They don't need to extend
    a base class and even allow constructors with required parameters.

    You are responsible for implementing getters, setters and constructors of
    your entities yourself. This gives you full freedom to design your business
    objects as you wish.

2. Using Annotations, XML or YAML for Metadata Mapping
------------------------------------------------------

.. configuration-block::

    .. code-block:: php

        <?php
        /** @Entity **/
        class Post
        {
            /** @Id @GeneratedValue @Column(type="integer") **/ 
            protected $id;
            /** @Column(type="string") **/
            protected $title;
            /** @Column(type="text") **/
            protected $body;
        }

    .. code-block:: yaml

        Post:
          type: entity
          id:
            id:
              type: integer
              generator:
                strategy: AUTO
          fields:
            title:
              type: string
            body:
              type: text

    .. code-block:: xml 
    
        <?xml version="1.0" ?>
        <doctrine-mapping>
            <entity name="Post">
                <id name="id type="integer">
                    <generator strategy="AUTO" />
                </id>
                <field name="title" type="string" />
                <field name="body" type="text" />
            </entity>
        </doctrine-mapping>


3. Object References map to Foreign keys
----------------------------------------

.. code-block:: php

    <?php
    /** @Entity **/
    class Post
    {
        // .. previous code
        
        /**
         * @ManyToOne(targetEntity="User")
         **/
        protected $author;

        public function __construct(User $user)
        {
            $this->author = $user;
        }
    }
    
    /** @Entity **/
    class User
    {
        /** @Id @GeneratedValue @Column(type="integer") **/ 
        protected $id;
        /** @Column(type="string") **/
        protected $name;
    }

    $user = new User();
    $post = new Post($user);


::

    mysql> CREATE TABLE Post (id INT AUTO_INCREMENT PRIMARY KEY, title
    VARCHAR(255), body TEXT, author_id INT);

    mysql> CREATE TABLE User (id INT AUTO_INCREMENT PRIMARY KEY, name
    VARCHAR(255));

    mysql> ALTER TABLE Post ADD FOREIGN KEY (author_id) REFERENCES User (id);

    mysql> DESCRIBE Post;
    +-----------+--------------+------+-----+---------+----------------+
    | Field     | Type         | Null | Key | Default | Extra          |
    +-----------+--------------+------+-----+---------+----------------+
    | id        | int(11)      | NO   | PRI | NULL    | auto_increment |
    | title     | varchar(255) | YES  |     | NULL    |                |
    | body      | text         | YES  |     | NULL    |                |
    | author_id | int(11)      | YES  | MUL | NULL    |                |
    +-----------+--------------+------+-----+---------+----------------+

.. tip::

    This means you don't have to mess with foreign keys yourself, just use
    references to connect objects with each other and let Doctrine handle the
    rest.

4. Collections handle sets of objects references
------------------------------------------------

.. code-block:: php

    <?php
    use Doctrine\Common\Collections\ArrayCollection;

    class Post
    {
        // .. previous code

        /**
         * @OneToMany(targetEntity="Comment", mappedBy="post",
         *   cascade={"persist"}) 
         **/
        protected $comments;

        public function __construct(User $author)
        {
            $this->author = $author;
            $this->comments = new ArrayCollection();
        }

        public function addComment($text)
        {
            $this->comments[] = new Comment($this, $text);
        }
    }

    /** @Entity **/
    class Comment
    {
        /** @Id @GeneratedValue @Column(type="integer") **/ 
        protected $id;
        /** @Column(type="text") **/
        protected $comment;
        /**
         * @ManyToOne(targetEntity="Post", inversedBy="comments") 
         **/
        protected $post;

        public function __construct(Post $post, $text)
        {
            $this->post = $post;
            $this->comment = $text;
        }
    }

    $post->addComment("First..");
    $post->addComment("Second!");

5. Easy to setup for the default configuration case
---------------------------------------------------

.. code-block:: php

    <?php
    use Doctrine\ORM\Tools\Setup;
    use Doctrine\ORM\EntityManager;

    require_once "vendor/autoload.php";

    $dbParams = array(
        'driver' => 'pdo_mysql',
        'user' => 'root',
        'password' => '',
        'dbname' => 'tests'
    );
    $path = 'path/to/entities';
    $config = Setup::createAnnotationMetadataConfiguration($path, true);
    $entityManager = EntityManager::create($dbParams, $config);


6. The EntityManager needs to know about your new objects
---------------------------------------------------------

.. code-block:: php

    <?php

    $entityManager->persist($user);
    $entityManager->persist($post);

.. warning::

    This does not lead to INSERT/UPDATE statements yet. You need to call
    EntityManager#flush()


7. EntityManager#flush() batches SQL INSERT/UPDATE/DELETE statements
--------------------------------------------------------------------

.. code-block:: php

    <?php

    $entityManager->flush();

.. tip::

    Batching all write-operations against the database allows Doctrine to wrap all
    statements into a single transaction and benefit from other performance
    optimizations such as prepared statement re-use.

8. You can fetch objects from the database through the EntityManager
--------------------------------------------------------------------

.. code-block:: php

    <?php

    $post = $entityManager->find("Post", $id);

9. ..or through a Repository
----------------------------

.. code-block:: php

    <?php

    $authorRepository = $entityManager->getRepository("Author");
    $author = $authorRepository->find($authorId);

    $postRepository = $entityManager->getRepository("Post");
    $post = $postRepository->findOneBy(array("title" => "Hello World!"));
    
    $posts = $repository->findBy(
        array("author" => $author),
        array("title" => "ASC")
    );


10. Or complex finder scenarios with the Doctrine Query Language
----------------------------------------------------------------

.. code-block:: php

    <?php
    // all posts and their comment count
    $dql = "SELECT p, count(c.id) AS comments " . 
           "FROM Post p JOIN p.comments GROUP BY p";
    $results = $entityManager->createQuery($dql)->getResult();
