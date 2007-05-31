vim_colors_dir = /usr/share/vim/colors

vim_files := $(wildcard $(vim_colors_dir)/*.vim)
xslt_files = $(patsubst %.vim,%.xsl,$(notdir $(vim_files)))

all: $(xslt_files)

%.xsl: $(vim_colors_dir)/%.vim
	perl vim2xslt.pl $< >$@
