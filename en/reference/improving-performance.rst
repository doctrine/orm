Improving Performance
=====================

Bytecode Cache
--------------

It is highly recommended to make use of a bytecode cache like APC.
A bytecode cache removes the need for parsing PHP code on every
request and can greatly improve performance.

    "If you care about performance and don't use a bytecode
    cache then you don't really care about performance. Please get one
    and start using it."
    
    *Stas Malyshev, Core Contributor to PHP and Zend Employee*


Metadata and Query caches
-------------------------

As already mentioned earlier in the chapter about configuring
Doctrine, it is strongly discouraged to use Doctrine without a
Metadata and Query cache (preferably with APC or Memcache as the
cache driver). Operating Doctrine without these caches means
Doctrine will need to load your mapping information on every single
request and has to parse each DQL query on every single request.
This is a waste of resources.

Alternative Query Result Formats
--------------------------------

Make effective use of the available alternative query result
formats like nested array graphs or pure scalar results, especially
in scenarios where data is loaded for read-only purposes.

Apply Best Practices
--------------------

A lot of the points mentioned in the Best Practices chapter will
also positively affect the performance of Doctrine.


