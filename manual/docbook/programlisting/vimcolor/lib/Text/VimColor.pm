package Text::VimColor;
use warnings;
use strict;

use IO::File;
use File::Copy qw( copy );
use File::Temp qw( tempfile );
use Path::Class qw( file );
use Carp;

die "Text::VimColor can't see where it's installed"
   unless -f __FILE__;
our $SHARED = file(__FILE__)->dir->subdir('VimColor')->stringify;

our $VERSION = '0.11';
our $VIM_COMMAND = 'vim';
our @VIM_OPTIONS = (qw( -RXZ -i NONE -u NONE -N ), "+set nomodeline");
our $NAMESPACE_ID = 'http://ns.laxan.com/text-vimcolor/1';

our %VIM_LET = (
   perl_include_pod => 1,
   'b:is_bash' => 1,
);

our %SYNTAX_TYPE = (
   Comment    => 1,
   Constant   => 1,
   Identifier => 1,
   Statement  => 1,
   PreProc    => 1,
   Type       => 1,
   Special    => 1,
   Underlined => 1,
   Error      => 1,
   Todo       => 1,
);

# Set to true to print the command line used to run Vim.
our $DEBUG = 0;

sub new
{
   my ($class, %options) = @_;

   $options{vim_command} = $VIM_COMMAND
      unless defined $options{vim_command};
   $options{vim_options} = \@VIM_OPTIONS
      unless defined $options{vim_options};

   $options{html_inline_stylesheet} = 1
      unless exists $options{html_inline_stylesheet};
   $options{xml_root_element} = 1
      unless exists $options{xml_root_element};

   $options{vim_let} = {
      %VIM_LET,
      (exists $options{vim_let} ? %{$options{vim_let}} : ()),
   };

   croak "only one of the 'file' or 'string' options should be used"
      if defined $options{file} && defined $options{string};

   my $self = bless \%options, $class;
   $self->_do_markup
      if defined $options{file} || defined $options{string};

   return $self;
}

sub vim_let
{
   my ($self, %option) = @_;

   while (my ($name, $value) = each %option) {
      $self->{vim_let}->{$name} = $value;
   }

   return $self;
}

sub syntax_mark_file
{
   my ($self, $file, %options) = @_;

   local $self->{filetype} = exists $options{filetype} ? $options{filetype}
                                                       : $self->{filetype};

   local $self->{file} = $file;
   $self->_do_markup;

   return $self;
}

sub syntax_mark_string
{
   my ($self, $string, %options) = @_;

   local $self->{filetype} = exists $options{filetype} ? $options{filetype}
                                                       : $self->{filetype};

   local $self->{string} = $string;
   $self->_do_markup;

   return $self;
}

sub html
{
   my ($self) = @_;
   my $syntax = $self->marked;

   my $html = '';
   $html .= $self->_html_header
      if $self->{html_full_page};

   foreach (@$syntax) {
      $html .= _xml_escape($_->[1]), next
         if $_->[0] eq '';

      $html .= "<span class=\"syn$_->[0]\">" .
               _xml_escape($_->[1]) .
               '</span>';
   }

   $html .= "</pre>\n\n </body>\n</html>\n"
      if $self->{html_full_page};

   return $html;
}

sub xml
{
   my ($self) = @_;
   my $syntax = $self->marked;

   my $xml = '';
   if ($self->{xml_root_element}) {
      my $filename = $self->input_filename;
      $xml .= "<syn:syntax xmlns:syn=\"$NAMESPACE_ID\"";
      $xml .= ' filename="' . _xml_escape($filename) . '"'
         if defined $filename;;
      $xml .= '>';
   }

   foreach (@$syntax) {
      $xml .= _xml_escape($_->[1]), next
         if $_->[0] eq '';

      $xml .= "<syn:$_->[0]>" .
              _xml_escape($_->[1]) .
              "</syn:$_->[0]>";
   }

   $xml .= "</syn:syntax>\n"
      if $self->{xml_root_element};

   return $xml;
}

sub marked
{
   my ($self) = @_;

   exists $self->{syntax}
      or croak "an input file or string must be specified, either to 'new' or".
               " 'syntax_mark_file/string'";

   return $self->{syntax};
}

sub input_filename
{
   my ($self) = @_;

   my $file = $self->{file};
   return $file if defined $file && !ref $file;

   return undef;
}

# Return a string consisting of the start of an XHTML file, with a stylesheet
# either included inline or referenced with a <link>.
sub _html_header
{
   my ($self) = @_;

   my $input_filename = $self->input_filename;
   my $title = defined $self->{html_title} ? _xml_escape($self->{html_title})
             : defined $input_filename     ? _xml_escape($input_filename)
             : '[untitled]';

   my $stylesheet;
   if ($self->{html_inline_stylesheet}) {
      $stylesheet = "<style>\n";
      if ($self->{html_stylesheet}) {
         $stylesheet .= _xml_escape($self->{html_stylesheet});
      }
      else {
         my $file = $self->{html_stylesheet_file};
         $file = file($SHARED, 'light.css')->stringify
            unless defined $file;
         unless (ref $file) {
            $file = IO::File->new($file, 'r')
               or croak "error reading stylesheet '$file': $!";
         }
         local $/;
         $stylesheet .= _xml_escape(<$file>);
      }
      $stylesheet .= "</style>\n";
   }
   else {
      $stylesheet =
         "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .
         _xml_escape($self->{html_stylesheet_url} ||
                     "file://$SHARED/light.css") .
         "\" />\n";
   }

   "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"" .
   " \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n" .
   "<html>\n" .
   " <head>\n" .
   "  <title>$title</title>\n" .
   "  $stylesheet" .
   " </head>\n" .
   " <body>\n\n" .
   "<pre>";
}

# Return a string safe to put in XML text or attribute values.  It doesn't
# escape single quotes (&apos;) because we don't use those to quote
# attribute values.
sub _xml_escape
{
   my ($s) = @_;
   $s =~ s/&/&amp;/g;
   $s =~ s/</&lt;/g;
   $s =~ s/>/&gt;/g;
   $s =~ s/"/&quot;/g;
   return $s;
}

# Actually run Vim and turn the script's output into a datastructure.
sub _do_markup
{
   my ($self) = @_;
   my $vim_syntax_script = file($SHARED, 'mark.vim')->stringify;

   croak "Text::VimColor syntax script '$vim_syntax_script' not installed"
      unless -f $vim_syntax_script && -r $vim_syntax_script;

   my $filename = $self->{file};
   my $input_is_temporary = 0;
   if (ref $self->{file}) {
      my $fh;
      ($fh, $filename) = tempfile();
      $input_is_temporary = 1;

      binmode $self->{file};
      binmode $fh;
      copy($self->{file}, $fh);
   }
   elsif (exists $self->{string}) {
      my $fh;
      ($fh, $filename) = tempfile();
      $input_is_temporary = 1;

      binmode $fh;
      print $fh (ref $self->{string} ? ${$self->{string}} : $self->{string});
   }
   else {
      croak "input file '$filename' not found"
         unless -f $filename;
      croak "input file '$filename' not accessible"
         unless -r $filename;
   }

   # Create a temp file to put the output in.
   my ($out_fh, $out_filename) = tempfile();

   # Create a temp file for the 'script', which is given to vim
   # with the -s option.  This is necessary because it tells Vim not
   # to delay for 2 seconds after displaying a message.
   my ($script_fh, $script_filename) = tempfile();
   my $filetype = $self->{filetype};
   my $filetype_set = defined $filetype ? ":set filetype=$filetype" : '';
   my $vim_let = $self->{vim_let};
   print $script_fh (map { ":let $_=$vim_let->{$_}\n" }
                     grep { defined $vim_let->{$_} }
                     keys %$vim_let),
                    ":filetype on\n",
                    "$filetype_set\n",
                    ":source $vim_syntax_script\n",
                    ":write! $out_filename\n",
                    ":qall!\n";
   close $script_fh;

   $self->_run(
      $self->{vim_command},
      @{$self->{vim_options}},
      $filename,
      '-s', $script_filename,
   );

   unlink $filename
      if $input_is_temporary;
   unlink $out_filename;
   unlink $script_filename;

   my $data = do { local $/; <$out_fh> };

   # Convert line endings to ones appropriate for the current platform.
   $data =~ s/\x0D\x0A?/\n/g;

   my $syntax = [];
   LOOP: {
      _add_markup($syntax, $1, $2), redo LOOP
         if $data =~ /\G>(.*?)>(.*?)<\1</cgs;
      _add_markup($syntax, '', $1), redo LOOP
         if $data =~ /\G([^<>]+)/cgs;
   }

   $self->{syntax} = $syntax;
}

# Given an array ref ($syntax), we add a new syntax chunk to it, unescaping
# the text and making sure that consecutive chunks of the same type are
# merged.
sub _add_markup
{
   my ($syntax, $type, $text) = @_;

   # Ignore types we don't know about.  At least one syntax file (xml.vim)
   # can produce these.  It happens when a syntax type isn't 'linked' to
   # one of the predefined types.
   $type = ''
      unless exists $SYNTAX_TYPE{$type};

   # Unescape ampersands and pointies.
   $text =~ s/&l/</g;
   $text =~ s/&g/>/g;
   $text =~ s/&a/&/g;

   if (@$syntax && $syntax->[-1][0] eq $type) {
      # Concatenate consecutive bits of the same type.
      $syntax->[-1][1] .= $text;
   }
   else {
      # A new chunk of marked-up text.
      push @$syntax, [ $type, $text ];
   }
}

# This is a private internal method which runs a program.
# It takes a list of the program name and arguments.
sub _run
{
   my ($self, $prog, @args) = @_;

   if ($DEBUG) {
      print STDERR __PACKAGE__."::_run: $prog " .
            join(' ', map { s/'/'\\''/g; "'$_'" } @args) . "\n";
   }

   my ($err_fh, $err_filename) = tempfile();
   my $old_fh = select($err_fh);
   $| = 1;
   select($old_fh);

   my $pid = fork;
   if ($pid) {
      my $gotpid = waitpid($pid, 0);
      croak "couldn't run the program '$prog'" if $gotpid == -1;
      my $error = $? >> 8;
      if ($error) {
         seek $err_fh, 0, 0;
         my $errout = do { local $/; <$err_fh> };
         $errout =~ s/\n+\z//;
         close $err_fh;
         unlink $err_filename;
         my $details = $errout eq '' ? '' :
                       "\nVim wrote this error output:\n$errout\n";
         croak "$prog returned an error code of '$error'$details";
      }
      close $err_fh;
      unlink $err_filename;
   }
   else {
      defined $pid
         or croak "error forking to run $prog: $!";
      open STDIN, '/dev/null';
      open STDOUT, '>/dev/null';
      open STDERR, '>&=' . fileno($err_fh)
         or croak "can't connect STDERR to temporary file '$err_filename': $!";
      exec $prog $prog, @args;
      die "\n";   # exec() will already have sent a suitable error message.
   }
}

1;

__END__

=head1 NAME

Text::VimColor - syntax color text in HTML or XML using Vim

=head1 SYNOPSIS

   use Text::VimColor;
   my $syntax = Text::VimColor->new(
      file => $0,
      filetype => 'perl',
   );

   print $syntax->html;
   print $syntax->xml;

=head1 DESCRIPTION

This module tries to markup text files according to their syntax.  It can
be used to produce web pages with pretty-printed colourful source code
samples.  It can produce output in the following formats:

=over 4

=item HTML

Valid XHTML 1.0, with the exact colouring and style left to a CSS stylesheet

=item XML

Pieces of text are marked with XML elements in a simple vocabulary,
which can be converted to other formats, for example, using XSLT

=item Perl array

A simple Perl data structure, so that Perl code can be used to turn it
into whatever is needed

=back

This module works by running the Vim text editor and getting it to apply its
excellent syntax highlighting (aka 'font-locking') to an input file, and mark
pieces of text according to whether it thinks they are comments, keywords,
strings, etc.  The Perl code then reads back this markup and converts it
to the desired output format.

This is an object-oriented module.  To use it, create an object with
the C<new> function (as shown above in the SYNOPSIS) and then call methods
to get the markup out.

=head1 METHODS

=over 4

=item new(I<options>)

Returns a syntax highlighting object.  Pass it a hash of options.

The following options are recognised:

=over 4

=item file

The file to syntax highlight.  Can be either a filename or an open file handle.

Note that using a filename might allow Vim to guess the file type from its
name if none is specified explicitly.

If the file isn't specified while creating the object, it can be given later
in a call to the C<syntax_mark_file> method (see below), allowing a single
Text::VimColor object to be used with multiple input files.

=item string

Use this to pass a string to be used as the input.  This is an alternative
to the C<file> option.  A reference to a string will also work.

The C<syntax_mark_string> method (see below) is another way to use a string
as input.

=item filetype

Specify the type of file Vim should expect, in case Vim's automatic
detection by filename or contents doesn't get it right.  This is
particularly important when providing the file as a string of file
handle, since Vim won't be able to use the file extension to guess
the file type.

The filetypes recognised by Vim are short strings like 'perl' or 'lisp'.
They are the names of files in the 'syntax' directory in the Vim
distribution.

This option, whether or not it is passed to C<new()>, can be overridden
when calling C<syntax_mark_file> and C<syntax_mark_string>, so you can
use the same object to process multiple files of different types.

=item html_full_page

By default the C<html()> output method returns a fragment of HTML, not a
full file.  To make useful output this must be wrapped in a C<E<lt>preE<gt>>
element and a stylesheet must be included from somewhere.  Setting the
C<html_full_page> option will instead make the C<html()> method return a
complete stand-alone XHTML file.

Note that while this is useful for testing, most of the time you'll want to
put the syntax highlighted source code in a page with some other content,
in which case the default output of the C<html()> method is more appropriate.

=item html_inline_stylesheet

Turned on by default, but has no effect unless C<html_full_page> is also
enabled.

This causes the CSS stylesheet defining the colours to be used
to render the markup to be be included in the HTML output, in a
C<E<lt>styleE<gt>> element.  Turn it off to instead use a C<E<lt>linkE<gt>>
to reference an external stylesheet (recommended if putting more than one
page on the web).

=item html_stylesheet

Ignored unless C<html_full_page> and C<html_inline_stylesheet> are both
enabled.

This can be set to a stylesheet to include inline in the HTML output (the
actual CSS, not the filename of it).

=item html_stylesheet_file

Ignored unless C<html_full_page> and C<html_inline_stylesheet> are both
enabled.

This can be the filename of a stylesheet to copy into the HTML output,
or a file handle to read one from.  If neither this nor C<html_stylesheet>
are given, the supplied stylesheet F<light.css> will be used instead.

=item html_stylesheet_url

Ignored unless C<html_full_page> is enabled and C<html_inline_stylesheet>
is disabled.

This can be used to supply the URL (relative or absolute) or the stylesheet
to be referenced from the HTML C<E<lt>linkE<gt>> element in the header.
If this isn't given it will default to using a C<file:> URL to reference
the supplied F<light.css> stylesheet, which is only really useful for testing.

=item xml_root_element

By default this is true.  If set to a false value, XML output will not be
wrapped in a root element called <syn:syntax>, but will be otherwise the
same.  This could allow XML output for several files to be concatenated,
but to make it valid XML a root element must be added.  Disabling this
option will also remove the binding of the namespace prefix C<syn:>, so
an C<xmlns:syn> attribute would have to be added elsewhere.

=item vim_command

The name of the executable which will be run to invoke Vim.
The default is C<vim>.

=item vim_options

A reference to an array of options to pass to Vim.  The default options are:

   qw( -RXZ -i NONE -u NONE -N )

=item vim_let

A reference to a hash of options to set in Vim before the syntax file
is loaded.  Each of these is set using the C<:let> command to the value
specified.  No escaping is done on the values, they are executed exactly
as specified.

Values in this hash override some default options.  Use a value of
C<undef> to prevent a default option from being set at all.  The
defaults are as follows:

   (
      perl_include_pod => 1,     # Recognize POD inside Perl code
      'b:is_bash' => 1,          # Allow Bash syntax in shell scripts
   )

These settings can be modified later with the C<vim_let()> method.

=back

=item vim_let(I<name> =E<gt> I<value>, ...)

Change the options that are set with the Vim C<let> command when Vim
is run.  See C<new()> for details.

=item syntax_mark_file(I<file>, I<options...>)

Mark up the specified file.  Subsequent calls to the output methods will then
return the markup.  It is not necessary to call this if a C<file> or C<string>
option was passed to C<new()>.

Returns the object it was called on, so an output method can be called
on it directly:

   my $syntax = Text::VimColor->new(
      vim_command => '/usr/local/bin/special-vim',
   );

   foreach (@files) {
      print $syntax->syntax_mark_file($_)->html;
   }

You can override the filetype set in new() by passing in a C<filetype>
option, like so:

   $syntax->syntax_mark_file($filename, filetype => 'perl');

This option will only affect the syntax colouring for that one call,
not for any subsequent ones on the same object.

=item syntax_mark_string(I<string>, I<options...>)

Does the same as C<syntax_mark_file> (see above) but uses a string as input.
I<string> can also be a reference to a string.
Returns the object it was called on.  Supports the C<filetype> option
just as C<syntax_mark_file> does.

=item html()

Return XHTML markup based on the Vim syntax colouring of the input file.

Unless the C<html_full_page> option is set, this will only return a fragment
of HTML, which can then be incorporated into a full page.  The fragment
will be valid as either HTML and XHTML.

The only markup used for the actual text will be C<E<lt>spanE<gt>> elements
wrapped round appropriate pieces of text.  Each one will have a C<class>
attribute set to a name which can be tied to a foreground and background
color in a stylesheet.  The class names used will have the prefix C<syn>,
for example C<synComment>.  For the full list see the section
HIGHLIGHTING TYPES below.

=item xml()

Returns markup in a simple XML vocabulary.  Unless the C<xml_root_element>
option is turned off (it's on by default) this will produce a complete XML
document, with all the markup inside a C<E<lt>syntaxE<gt>> element.

This XML output can be transformed into other formats, either using programs
which read it with an XML parser, or using XSLT.  See the
text-vimcolor(1) program for an example of how XSLT can be used with
XSL-FO to turn this into PDF.

The markup will consist of mixed content with elements wrapping pieces
of text which Vim recognized as being of a particular type.  The names of
the elements used are the ones listed in the HIGHLIGHTING TYPES section
below.

The C<E<lt>syntaxE<gt>> element will declare the namespace for all the
elements prodeced, which will be C<http://ns.laxan.com/text-vimcolor/1>.
It will also have an attribute called C<filename>, which will be set to the
value returned by the C<input_filename> method, if that returns something
other than undef.

The XML namespace is also available as C<$Text::VimColor::NAMESPACE_ID>.

=item marked()

This output function returns the marked-up text in the format which the module
stores it in internally.  The data looks like this:

   use Data::Dumper;
   print Dumper($syntax->marked);

   $VAR1 = [
      [ 'Statement', 'my' ],
      [ '', ' ' ],
      [ 'Identifier', '$syntax' ],
      [ '', ' = ' ],
       ...
   ];

The C<marked()> method returns a reference to an array.  Each item in the
array is itself a reference to an array of two items: the first is one of
the names listed in the HIGHLIGHTING TYPES section below (or the empty
string if none apply), and the second is the actual piece of text.

=item input_filename()

Returns the filename of the input file, or undef if a filename wasn't
specified.

=back

=head1 HIGHLIGHTING TYPES

The following list gives the names of highlighting types which will be
set for pieces of text.  For HTML output, these will appear as CSS class
names, except that they will all have the prefix C<syn> added.  For XML
output, these will be the names of elements which will all be in the
namespace C<http://ns.laxan.com/text-vimcolor/1>.

Here is the complete list:

=over 4

=item *

Comment

=item *

Constant

=item *

Identifier

=item *

Statement

=item *

PreProc

=item *

Type

=item *

Special

=item *

Underlined

=item *

Error

=item *

Todo

=back

=head1 RELATED  MODULES

These modules allow Text::VimColor to be used more easily in particular
environments:

=over 4

=item L<Apache::VimColor>

=item L<Kwiki::VimMode>

=item L<Template-Plugin-VimColor>

=back

=head1 SEE ALSO

=over 4

=item text-vimcolor(1)

A simple command line interface to this module's features.  It can be used
to produce HTML and XML output, and can also generate PDF output using
an XSLT/XSL-FO stylesheet and the FOP processor.

=item http://www.vim.org/

Everything to do with the Vim text editor.

=item http://ungwe.org/blog/

The author's weblog, which uses this module.  It is used to make the code
samples look pretty.

=back

=head1 BUGS

Quite a few, actually:

=over 4

=item *

Apparently this module doesn't always work if run from within a 'gvim'
window, although I've been unable to reproduce this so far.
CPAN bug #11555.

=item *

Things can break if there is already a Vim swapfile, but sometimes it
seems to work.

=item *

There should be a way of getting a DOM object back instead of an XML string.

=item *

It should be possible to choose between HTML and XHTML, and perhaps there
should be some control over the DOCTYPE declaration when a complete file is
produced.

=item *

With Vim versions earlier than 6.2 there is a 2 second delay each time
Vim is run.

=item *

It doesn't work on Windows.  I am unlikely to fix this, but if anyone
who knows Windows can sort it out let me know.

=back

=head1 AUTHOR

Geoff Richards E<lt>qef@laxan.comE<gt>

The Vim script F<mark.vim> is a crufted version of F<2html.vim> by
Bram Moolenaar E<lt>Bram@vim.orgE<gt> and
David Ne\v{c}as (Yeti) E<lt>yeti@physics.muni.czE<gt>.

=head1 COPYRIGHT

Copyright 2002-2006, Geoff Richards.

This library is free software; you can redistribute it and/or
modify it under the same terms as Perl itself.

=cut

# Local Variables:
# mode: perl
# perl-indent-level: 3
# perl-continued-statement-offset: 3
# End:
# vi:ts=3 sw=3 expandtab:
