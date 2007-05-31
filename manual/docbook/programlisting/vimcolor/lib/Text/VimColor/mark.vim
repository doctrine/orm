" mark.vim - turn Vim syntax highlighting into an ad-hoc markup language that
" can be parsed by the Text::VimColor Perl module.
"
" Maintainer: Geoff Richards <qef@laxan.com>
" Based loosely on 2html.vim, by Bram Moolenaar <Bram@vim.org>,
"   modified by David Ne\v{c}as (Yeti) <yeti@physics.muni.cz>.

set report=1000000

" For some reason (I'm sure it used to work) we now need to get Vim
" to make another attempt to detect the filetype if it wasn't set
" explicitly.
if !strlen(&filetype)
   filetype detect
endif
syn on

" Set up the output buffer.
new
set modifiable
set paste

" Expand tabs. Without this they come out as '^I'.
set isprint+=9

wincmd p

" Loop over all lines in the original text
let s:end = line("$")
let s:lnum = 1
while s:lnum <= s:end

  " Get the current line
  let s:line = getline(s:lnum)
  let s:len = strlen(s:line)
  let s:new = ""

  " Loop over each character in the line
  let s:col = 1
  while s:col <= s:len
    let s:startcol = s:col " The start column for processing text
    let s:id = synID(s:lnum, s:col, 1)
    let s:col = s:col + 1
    " Speed loop (it's small - that's the trick)
    " Go along till we find a change in synID
    while s:col <= s:len && s:id == synID(s:lnum, s:col, 1) | let s:col = s:col + 1 | endwhile

    " Output the text with the same synID, with class set to c{s:id}
    let s:id = synIDtrans(s:id)
    let s:name = synIDattr(s:id, 'name')
    let s:new = s:new . '>' . s:name . '>' . substitute(substitute(substitute(strpart(s:line, s:startcol - 1, s:col - s:startcol), '&', '\&a', 'g'), '<', '\&l', 'g'), '>', '\&g', 'g') . '<' . s:name . '<'

    if s:col > s:len
      break
    endif
  endwhile

  exe "normal \<C-W>pa" . strtrans(s:new) . "\n\e\<C-W>p"
  let s:lnum = s:lnum + 1
  +
endwhile

" Strip whitespace from the ends of lines
%s:\s\+$::e

wincmd p
normal dd
