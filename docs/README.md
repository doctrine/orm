# Doctrine ORM Documentation

## How to Generate:

The following will generate the documentation into the `build` directory of the checkout.

### Using Ubuntu 14.04 LTS:

1. Run ./bin/install-dependencies.sh
2. Run ./bin/generate-docs.sh

### Using macOS:

Sphinx Installation instructions can be found at the
[Sphinx installation guide](http://www.sphinx-doc.org/en/master/usage/installation.html#macos).

After install, make sure `squinx-build` is globally available by e.g. executing
`export PATH="/usr/local/opt/sphinx-doc/bin:$PATH"`.

Then run `./bin/install-dependencies.sh` from this `docs` directory.

## Theme issues

If you get a "Theme error", check if the `en/_theme` subdirectory is empty,
in which case you will need to run:

1. git submodule init
2. git submodule update
