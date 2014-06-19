termify
=======

This project started as a way for me to quickly organize ideas without being burdened by the tedious aspects. I like to write games and that usually requires evolving design documents. As projects grow larger, single-file notepad documents become ever more unwieldy. I needed a way to break up my documents into logical segments, but I didn't want to lose the ability to quickly navigate between pages for cross-reference. I did some searching online for a solution, but didn't find one that fit my needs. I wanted something I could drop in to my existing documents and just work. So I wrote my own.

Termify is a single-page app that takes the content of a regular htm page and creates links automatically for terms found in the content, if a page with the same name as the term already exists. Htm pages were chosen because of the ease of formatting (lists, tables, etc), rather than creating a separate markup language. Take the following proto-rpg folder for example:

<pre>
myproject
+- attributes.htm
+- character creation.htm
+- game basics
|  +- rolling dice.htm
|  \- rounds.htm
+- __index__.htm
\- skills.htm
</pre>

Simply drop the index.php file into the myproject folder and navigate to it.
<table>
<tr><td>If you call this page as:</td><td>you get:</td></tr>
<tr><td>index.php</td><td>the contents of __index__.htm rendered (same as index.php?t=__index__)</td></tr>
<tr><td>index.php?t=term</td><td>the contents of term.htm rendered, if it exists. If not, a 404-style page</td></tr>
</table>

\_\_index\_\_.htm is the default returned page for any folder navigation, as 'index' might be a term we want to use.

Say for example the 'character creation' page contains this text:
"During character creation, you specify the attributes and skills you want a character to have."
When rendered in termify, 'attributes' and 'skills' get turned into hyperlinks, to index.php?t=attributes and index.php?t=skills, respectively.

A simple table of contents is also created on the left side, along with next and previous links for quick navigation through the tree. To specify that terms appear in a certain order in the table of contents, use the "##_ " syntax at the beginning of the document. This will be stripped off by termify when rendering. For example:

<pre>
myproject
+- 01_ character creation.htm
+- 02_ game basics
|  +- rolling dice.htm
|  \- rounds.htm
+- 03_ attributes.htm
+- 04_ skills.htm
\- __index__.htm
</pre>

You can also have certain terms ignored by the ToC generator by putting them in an \_\_ignore\_\_ folder at each level of the structure. For example:

<pre>
myproject
+- 01_ character creation.htm
+- 02_ game basics
|  +- rolling dice.htm
|  \- rounds.htm
+- __ignore__
|  +- attributes.htm
|  \- skills.htm
\- __index__.htm
</pre>

Terms do not have to be in the current folder to be linked. In the case of duplicate terms, termify uses the following rules for context:
- Terms in the current folder share a similar context, so that is most likely to be meant
- Terms that are children of the current term are next most likely to be meant 
- Terms nearer to the root folder are more likely or important than terms buried in subfolders

These rules manifest themselves as the following:
- Look in the current folder first
- Look in children of the current folder second
- Do a breadth-first search starting at the root folder

For example, consider the following folder structure:

<pre>
root
+- sub1
|  +- sub3
|  |  \- term1
|  +- term2
|  \- term3
+- sub2
|  +- term1
|  \- term4
\- term5
</pre>

sub1/sub3/term1 and sub2/term1 are the same term. 
<table>
<tr><td>If you are on:</td><td>Then term1 links to:</td></tr>
<tr><td>sub2/term4</td><td>sub2/term1</td></tr>
<tr><td>sub1/term3</td><td>sub3/term1</td></tr>
<tr><td>term5</td><td>sub2/term1</tr></tr>
</table>

There are plenty of things that could improve this. Please feel free to contribute, port it to another language, or modify for your own purposes. I hope you find it useful and it saves you some time as it has for me.

