# Doctrine ORM Documentation

The documentation is written in [ReStructured Text](https://docutils.sourceforge.io/rst.html).

## How to Generate:

In the `docs/` folder, run

    composer update

Then compile the documentation with:

    make html

This will generate the documentation into the `build` subdirectory.

To browse the documentation, you need to run a webserver:

    cd build/html
    php -S localhost:8000

Now the documentation is available at [http://localhost:8000](http://localhost:8000).
