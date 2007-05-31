#!/usr/bin/perl
# Take vim colors file and convert it to an XSLT stylesheet

# The only types vim-textcolor produces
my %syntax_data = (
   'Comment'    => undef,
   'Constant'   => undef,
   'Identifier' => undef,
   'Statement'  => undef,
   'PreProc'    => undef,
   'Type'       => undef,
   'Special'    => undef,
   'Underlined' => undef,
   'Error'      => undef,
   'Todo'       => undef
);
