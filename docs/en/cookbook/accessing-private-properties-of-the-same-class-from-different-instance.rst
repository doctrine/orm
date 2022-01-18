Accessing private/protected properties/methods of the same class from different instance
========================================================================================

.. sectionauthor:: Michael Olsavsky (olsavmic)

As explained in the :doc:`restrictions for entity classes in the manual <../reference/architecture>`,
it is dangerous to access private/protected properties of different entity instance of the same class because of lazy loading.

The proxy instance that's injected instead of the real entity may not be initialized yet
and therefore not contain expected data which may result in unexpected behavior.
That's a limitation of current proxy implementation - only public methods automatically initialize proxies.

It is usually preferable to use a public interface to manipulate the object from outside the `$this`
context but it may not be convenient in some cases. The following example shows how to do it safely.

Safely accessing private properties from different instance of the same class
-----------------------------------------------------------------------------

To safely access private property of different instance of the same class, make sure to initialise
the proxy before use manually as follows:

.. code-block:: php

    <?php

    use Doctrine\Common\Proxy\Proxy;
    use Doctrine\ORM\Mapping as ORM;

    /**
     * @ORM\Entity
     */
    class Entity
    {
        // ...

        /**
         * @ORM\ManyToOne(targetEntity="Entity")
         * @ORM\JoinColumn(nullable=false)
         */
        private self $parent;

        /**
         * @ORM\Column(type="string", nullable=false)
         */
        private string $name;

        // ...

        public function doSomethingWithParent()
        {
            // Always initializing the proxy before use
            if ($this->parent instanceof Proxy) {
                $this->parent->__load();
            }

            // Accessing the `$this->parent->name` property without loading the proxy first
            // may throw error in case the Proxy has not been initialized yet.
            $this->parent->name;
        }

        public function doSomethingWithAnotherInstance(self $instance)
        {
            // Always initializing the proxy before use
            if ($instance instanceof Proxy) {
                $instance->__load();
            }

            // Accessing the `$instance->name` property without loading the proxy first
            // may throw error in case the Proxy has not been initialized yet.
            $instance->name;
        }

        // ...
    }
