package Path::Class::Entity;

use strict;
use File::Spec;
use File::stat ();

use overload
  (
   q[""] => 'stringify',
   fallback => 1,
  );

sub new {
  my $from = shift;
  my ($class, $fs_class) = (ref($from)
			    ? (ref $from, $from->{file_spec_class})
			    : ($from, $Path::Class::Foreign));
  return bless {file_spec_class => $fs_class}, $class;
}

sub is_dir { 0 }

sub _spec_class {
  my ($class, $type) = @_;

  die "Invalid system type '$type'" unless ($type) = $type =~ /^(\w+)$/;  # Untaint
  my $spec = "File::Spec::$type";
  eval "require $spec; 1" or die $@;
  return $spec;
}

sub new_foreign {
  my ($class, $type) = (shift, shift);
  local $Path::Class::Foreign = $class->_spec_class($type);
  return $class->new(@_);
}

sub _spec { $_[0]->{file_spec_class} || 'File::Spec' }
  
sub is_absolute { 
    # 5.6.0 has a bug with regexes and stringification that's ticked by
    # file_name_is_absolute().  Help it along.
    $_[0]->_spec->file_name_is_absolute($_[0]->stringify) 
}

sub cleanup {
  my $self = shift;
  my $cleaned = $self->new( $self->_spec->canonpath($self) );
  %$self = %$cleaned;
  return $self;
}

sub absolute {
  my $self = shift;
  return $self if $self->is_absolute;
  return $self->new($self->_spec->rel2abs($self->stringify, @_));
}

sub relative {
  my $self = shift;
  return $self->new($self->_spec->abs2rel($self->stringify, @_));
}

sub stat  { File::stat::stat("$_[0]") }
sub lstat { File::stat::lstat("$_[0]") }

1;
