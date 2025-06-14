# Makefile for Doctrine ORM documentation
#

# You can set these variables from the command line.
DOCOPTS  =
BUILD    = vendor/bin/guides
BUILDDIR = build

# Internal variables.
ALLGUIDESOPTS   = $(DOCOPTS) en/

.PHONY: help clean html

help:
	@echo "Please use \`make <target>' where <target> is one of"
	@echo "  html      to make standalone HTML files"

clean:
	-rm -rf ./$(BUILDDIR)/*

html:
	$(BUILD) $(ALLGUIDESOPTS) --output=$(BUILDDIR)/html
	@echo
	@echo "Build finished. The HTML pages are in $(BUILDDIR)/html."
