# PHPLaTeX

## Introduction

PHPLaTeX is a program for converting LaTeX documents into HTML5 with MathML and SVG.
It's main selling points are that it is written entirely in PHP and that it attempts to mimic the basic TeX method of parsing a document by reading and expanding tokens.
The main consequence of the first of these is that it is extremely portable.
The main consequence of the second is that it can handle macro expansion.

Not everything is implemented yet, and things that are may not be implemented fully.
A full list of the currently available commands, together with some notes on specific commands, is given at the end.

This is not intended to ever be a PHP implementation of TeX.
It would be nice if one could load in a reasonably straightforward TeX or LaTeX document and convert it to HTML5 with MathML and SVG, however I do not intend it ever to be able to load in a style file and use it with no alterations.
The reason being that TeX is a _renderer_ whereas this program is a _converter_.
The rendering is done by another program (i.e. your web browser).
Therefore the aim of this program is to tranlate a document into something a web browser can understand.
The reason that it has to be fairly complicated is that TeX is actually a programming language whereas HTML is merely a markup language.
Therefore some of the programming commands in a normal TeX document have to be carried out before they can be converted to markup.
Unfortunately, the division between pre-rendering and post-rendering processing is not clean and so cannot be fully automatic (or at least, I've no plans to attempt it).
In essence, the closer one is to the actual TeX engine, the less likely one is going to be able to "drag and drop" it into this program.
Fortunately, most LaTeX documents are a long, long way from that.

## History

I originally wrote this in 2009 and abandoned it in 2013 by which time
I'd shifted to developing a system that used TeX itself to parse a
document and output a webpage.
I've returned to it because I keep wanting a webpage that converts
LaTeX code to something that I can cut and paste into other documents
and the TeX project doesn't provide for that.
As this already exists, I figure it can be the base of that.

## License, Source Code, etc

This program is made available under the GPL.
It does not have an official release yet, being only in alpha stage,
but you can get it via git from this repository.

