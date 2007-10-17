\documentclass[11pt,a4paper]{book}

\usepackage{ifpdf}
\ifpdf
\usepackage{thumbpdf}
\pdfcompresslevel=9
\fi

\usepackage[latin1]{inputenc}
\usepackage[margin=2cm, twoside, bindingoffset=1cm]{geometry}
\usepackage{fancyhdr}
\usepackage{url}
\usepackage{calc}
\usepackage{longtable}
\usepackage{listings}
\usepackage{color}

\usepackage[
    pdftex,
    colorlinks,
    bookmarks,
    pdftitle={%TITLE%},
    pdfauthor={%AUTHOR%},
    pdfsubject={%SUBJECT%},
    pdfkeywords={%KEYWORDS%}]{hyperref}

\pdfadjustspacing=1

\newdimen\newtblsparewidth

\pagestyle{fancy}
\renewcommand{\chaptermark}[1]{\markboth{\chaptername\ \thechapter.\ #1}{}}
\renewcommand{\sectionmark}[1]{\markright{\thesection.\ #1}}
\fancyhf{}
\fancyhead[LE]{\nouppercase{\bfseries\leftmark}}
\fancyhead[RO]{\nouppercase{\bfseries\rightmark}}
\fancyhead[RE, LO]{\bfseries{%TITLE%}}
\fancyfoot[RO, LE]{\bfseries\thepage}
\renewcommand{\headrulewidth}{0.5pt}
\renewcommand{\footrulewidth}{0.5pt}
\addtolength{\headheight}{2.5pt}
\fancypagestyle{plain}{
  \fancyhead{}
  \fancyfoot{}
  \renewcommand{\headrulewidth}{0pt}
  \renewcommand{\footrulewidth}{0pt}
}

\definecolor{light-gray}{gray}{0.97}
\definecolor{keyword}{rgb}{0.47, 0.53, 0.6}
\definecolor{string}{rgb}{0.73, 0.53, 0.27}
\definecolor{comment}{rgb}{0.6, 0.6, 0.53}

\lstset{
  columns=fixed,
  basicstyle=\footnotesize\ttfamily,
  identifierstyle=\color{black},
  keywordstyle=\color{keyword},
  stringstyle=\color{string},
  commentstyle=\color{comment}\itshape,
  backgroundcolor=\color{light-gray},
  frame=single,
  framerule=0pt,
  breaklines,
  showstringspaces=false
}

\lstdefinelanguage{PHP}
  {morekeywords={
    % Case-sensitive keywords:
    abstract,and,as,break,case,catch,class,const,continue,default,die,do,echo,
    else,elseif,endfor,endforeach,endswitch,endwhile,extends,for,global,final,
    foreach,function,if,implements,import,include,include_once,instanceof,
    interface,list,namespace,new,or,print,private,protected,public,require,
    require_once,return,static,switch,throw,try,var,while,xor,
    % Case-insensitive keywords:
    true,True,TRUE,false,False,FALSE,null,Null,NULL},
   morekeywords=[2]{
     array,bool,boolean,double,float,int,integer,object,real,string,unset},
   otherkeywords={-,.,~,^,@,;,:,\%,|,=,+,!,?,&,<,>},
   sensitive=true,
   morecomment=[l]{//},      % C++ line comment
   morecomment=[l]{\#},      % Bash line comment
   morecomment=[s]{/*}{*/},  % C block comment
   morestring=[b]{'},        % single-quoted string
   morestring=[b]{"}         % double-quoted string
  }


\setcounter{tocdepth}{5}
\setcounter{secnumdepth}{5}

\title{%TITLE%}
\author{%AUTHOR%}
\date{%VERSION%\\ \today}

\begin{document}

\maketitle
\tableofcontents

\setlength{\parindent}{0pt}
\setlength{\parskip}{1.5ex plus 0.7ex minus 0.6ex}

%CONTENT%

\end{document}
