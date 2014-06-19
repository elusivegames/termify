<?php
  // Copyright (c) 2013, Keith Wagner
  // All rights reserved.
  //
  // Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
  // following conditions are met:
  //
  // 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
  // 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the
  //    following disclaimer in the documentation and/or other materials provided with the distribution.
  // 3. Neither the name of the copyright holder nor the names of its contributors may be used to endorse or
  //    promote products derived from this software without specific prior written permission.
  //
  // THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, 
  // INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE 
  // DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
  // SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR 
  // SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
  // WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE 
  // USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

  // termify.php
  // created Mar 11 2013
  // by Keith Wagner
  // this takes the content of a regular htm page and creates links automatically for terms found in the content,
  // if a page with the same name as the term already exists.
  // if you call this page as:      you get:
  // index.php                      the contents of __index__.htm rendered (same as index.php?t=__index__)
  // index.php?t=term               the contents of term.htm rendered, if it exists. if not, a 404-style page
  //
  // __index__.htm is the index page of the website. this is because "index" may be used in text for something separate.
  // changes: 
  // TODO:
  // - add in __<filename>-whitelist__.txt and __<filename>-blacklist__.txt support
  // - verify security for pathing (can't get into relative paths outside of the directory)
  // - add in __<filename>__.css for a 'global css' applied to every page
  // - add in __<filename>-config__.php for things like title
  // - caching is present in __cache__.txt. this file will populate the directories needed by the program so as to
  //   eliminate unnecessary hits on the disk. this does mean that changes won't be instant. cache time is settable.
  //   if no caching is desired, set cachetime to 0.
  // 2014-06-19:
  //  - Some cleanup in prep for putting on github
  //  - Fixed issue with __ignore__ not linking properly
  //  - Removed prev/next navigation for terms that aren't in table of contents
  // 2013-06-15:
  //  - Put title of page in between prev and next nav buttons
  //  - Removed header/footer to simplify code into a single page for easier use
  // 2013-03-16:
  //  - bugfix for child term scope
  //  - added 'home' for main __index__ file in root
  //  - bugfix for TOC generator with stray </ul>'s
  // 2013-03-13:
  // - make linking happen for __index__ files based on their parent directory.
  // 2013-03-12:
  // - toc is orderable using "##_ " before the folder or filename. this will be stripped when toc is shown or term is looked up.
  // - header.php and footer.php implemented for styling and configurability
  // - toc is now auto-generated based on the directory tree. anything that is not to be in the table of contents
  //   is put into a __ignore__ folder (but still searched at that level).
  // - term linking is done first in the current directory, then children if none found, then globally if none found.
  //   this will allow duplicate terms on different subjects/context to exist and work correctly.
  // 2013-03-11:
  // - initial implementation

  // configurable variables
  // create the header and bring in required variables
  // now put in here because I want to just have a single php file be added to someone's folder for this to work
  $title = "Termify Example"; // title of the document
  $cachetime = 60; // number of seconds to cache directory results
  
  // separate the current term into the folder and the node.
  if (isset($_GET['t']))
  {
    $t = $_GET['t'];
    // filter for crap
    $t = str_replace('../','',$t);
    $t = str_replace('./','',$t);
    $t = str_replace('//','',$t);
    $t = str_replace('$','',$t);
    $checkt = $t;
    $pos = strrpos($t,'/');    
    if ($pos === false)
    {
      $tdir = '.';
    }
    else
    {
      $tdir = substr($t,0,$pos+1);
      $t = substr($t,$pos+1);
      if ($t == '')
        $t = '__index__';
    }
  }
  else
  {
    $tdir = '.';
    $t = '__index__';
    $checkt = '';
  }
  $prevstring = '';
  $nextstring = '';
  $tset = false;
  $actualt = $t;
  
  /*
  // read in cache file if it exists
  if (file_exists('__cache__.txt'))
  {
  }
  // determine if should use cache or read in values
  */
  
  // first, create an array for every htm file in this folder.
  $temptermlist = scandir($tdir);
  $tc = count($temptermlist);
  // filter termlist for correct stuff (.htm files)
  $localtermlist = array();
  for ($ti = 0; $ti < $tc; $ti++)
  {
    if (substr($temptermlist[$ti],-4) == '.htm')
      array_push($localtermlist,$temptermlist[$ti]);
  }
  $ltc = count($localtermlist);

  // next, create an array for every htm file in children of this folder.
  $temptermlist = DirToArray($tdir);
  $tc = count($temptermlist);
  // filter termlist for correct stuff (.htm files)
  $childtermlist = array();
  $childdirlist = array();
  for ($ti = 0; $ti < $tc; $ti++)
  {
    if (substr($temptermlist[$ti],-4) == '.htm')
      array_push($childtermlist,$temptermlist[$ti]);
  }
  $ctc = count($childtermlist);
  
  // finally, create an array for every html file in the root and all children folders.
  $temptermlist = DirToArray('.');
  $tc = count($temptermlist);
  // filter termlist for correct stuff (.htm files)
  $fulltermlist = array();
  $fulldirlist = array();
  for ($ti = 0; $ti < $tc; $ti++)
  {
    if (substr($temptermlist[$ti],-4) == '.htm')
      array_push($fulltermlist,$temptermlist[$ti]);
  }
  $ftc = count($fulltermlist);

  // create the table of contents naturally by the tree structure of the directory.
  $toc = DirToTOC('.');
  echo '<!DOCTYPE html><html><head><title>'.$title.' - '.$actualt.'</title></head><body><table><tr><td width="300px" valign="top">';
  echo $toc;
  // remove prev/next for terms that don't exist in the table of contents
  if (stripos($tdir,'__ignore__') !== false)
  {
    $prevstring = '';
    $nextstring = '';
  }
  // now handle the content of the page
  echo '</td><td valign="top">';
  echo '<center>'.$prevstring.$actualt.$nextstring.'</center><br>';
  $th = $t.'.htm';
  $found = false;
  $tfi = 0;
  // make sure the page exists
  for ($ti = 0; $ti < $ltc; $ti++)
  {
    if ($th == $localtermlist[$ti])
    {
      $found = true;
      $tfi = $ti;
      break;
    }
  }
  if ($found)
  {
    // short arrays (minus htm) for quicker searching
    $localshortterm = array();
    $childshortterm = array();
    $fullshortterm = array();
    $childdirlist = array();
    $fulldirlist = array();
    for ($ti = 0; $ti < $ltc; $ti++)
    {
      array_push($localshortterm,substr($localtermlist[$ti],0,strlen($localtermlist[$ti])-4));
    }
    for ($ti = 0; $ti < $ctc; $ti++)
    {
      // check for existence of an element in localshortterm. if so, don't create it.
      $temp = substr($childtermlist[$ti],0,strlen($childtermlist[$ti])-4);
      //if (array_search($temp,$localshortterm) === false)
        array_push($childshortterm,$temp);
    }
    $ctc = count($childshortterm);
    for ($ti = 0; $ti < $ftc; $ti++)
    {
      $temp = substr($fulltermlist[$ti],0,strlen($fulltermlist[$ti])-4);
      //if ((array_search($temp,$localshortterm) === false) && (array_search($temp,$childshortterm) === false))
        array_push($fullshortterm,$temp);
    }
    $ftc = count($fullshortterm);
    usort($localshortterm,'SortByLength');
    usort($childshortterm,'SortByLength');
    usort($fullshortterm,'SortByLength');
    // in child and full, you need to do a sort first by size as normal, but then do a 'breadth first' search in the event of
    // duplicates, as higher up terms are more important than lower terms. ie:
    // root
    // +-sub1
    // | +-sub3
    // | | \-term1
    // | \-term2
    // \-sub2
    //   \-term1
    // sub1/sub3/term1 and sub2/term1 are the same term, but sub1/sub3 will appear before sub2. sub2 is more
    // important than sub1/sub3 as far as context goes, so it needs to be found first in the search.
    
    // now split out the dir from the term, for child and full
    for ($ti = 0; $ti < $ctc; $ti++)
    {
      $pos = strrpos($childshortterm[$ti],'/');
      if ($pos === false)
      {
        array_push($childdirlist,'');
      }
      else
      {
        array_push($childdirlist,substr($childshortterm[$ti],0,$pos+1));
        $childshortterm[$ti] = substr($childshortterm[$ti],$pos+1);
      }
    }
    for ($ti = 0; $ti < $ftc; $ti++)
    {
      $pos = strrpos($fullshortterm[$ti],'/');
      if ($pos === false)
      {
        array_push($fulldirlist,'');
      }
      else
      {
        array_push($fulldirlist,substr($fullshortterm[$ti],0,$pos+1));
        $fullshortterm[$ti] = substr($fullshortterm[$ti],$pos+1);
      }
    }
    // read in file
    if ($tdir == '.')
      $tdir = '';
    $fc = file_get_contents($tdir.$th);
    if ($fc == '')
    {
      echo '<h1>Term not defined</h1><br>'
           .'The term "'.$t.'" was found, but its contents were blank.<br>'
           .'<a href="index.php">Click here</a> to go back to the home page.';
    }
    else
    {
      // go through the whole thing, and replace any text with the appropriate link if one exists (but nothing in tags!)
      $d = new DOMDocument;
      $d->loadHTML($fc);
      $x = new DOMXPath($d);
      foreach ($x->query('//text()') as $node)
      {
        for ($ti = 0; $ti < $ltc; $ti++)
        {
          if ($t != $localshortterm[$ti])
            $node->nodeValue = ReplaceValue($node->nodeValue,GetRealNode($localshortterm[$ti]),'L',$ti);
        }
      }
      // do this for each level of terms
      foreach ($x->query('//text()') as $node)
      {
        for ($ti = 0; $ti < $ctc; $ti++)
        {
          if ($tdir.$t != $childdirlist[$ti].$childshortterm[$ti])
            $node->nodeValue = ReplaceValue($node->nodeValue,GetRealNode($childshortterm[$ti]),'C',$ti);
        }
      }
      foreach ($x->query('//text()') as $node)
      {
        for ($ti = 0; $ti < $ftc; $ti++)
        {
          if ($tdir.$t != $fulldirlist[$ti].$fullshortterm[$ti])
            $node->nodeValue = ReplaceValue($node->nodeValue,GetRealNode($fullshortterm[$ti]),'F',$ti);
        }
      }
      $fc = $d->saveHTML();

      // remove stray doctype, body, and html attributes
      $pos = stripos($fc,'<!DOCTYPE');
      $pos2 = stripos($fc,'<body>',$pos);
      $fc = substr($fc,0,$pos).substr($fc,$pos2+6);
      
      while (stripos($fc,'%%TERMSTART%%') !== false)
      {
        $pos = stripos($fc,'%%TERMSTART%%');
        $pos2 = stripos($fc,'%%TERMEND%%',$pos+1);
        $extract = substr($fc,$pos+13,$pos2-($pos+14));
        $tempa = explode('__',$extract);
        $replacement = '<a href="index.php?t=';
        if ($tempa[1] == 'L')
        {
          if ($localshortterm[$tempa[2]] == '__index__')
            $replacement .= $tdir.'">';
          else
            $replacement .= $tdir.$localshortterm[$tempa[2]].'">';
        }
        elseif ($tempa[1] == 'C')
        {
          if ($childshortterm[$tempa[2]] == '__index__')
            $replacement .= $childdirlist[$tempa[2]].'">';
          else
            $replacement .= $childdirlist[$tempa[2]].$childshortterm[$tempa[2]].'">';
        }
        else
        {
          if ($fullshortterm[$tempa[2]] == '__index__')
            $replacement .= $fulldirlist[$tempa[2]].'">';
          else
            $replacement .= $fulldirlist[$tempa[2]].$fullshortterm[$tempa[2]].'">';
        }
        $fc = substr($fc,0,$pos).$replacement.substr($fc,$pos2);
      }
      while (stripos($fc,'%%TERMEND%%') !== false)
      {
        $pos = stripos($fc,'%%TERMEND%%');
        $pos2 = stripos($fc,'%%TERMFINISH%%',$pos+1);
        $extract = substr($fc,$pos+13,$pos2-($pos+14));
        $tempa = explode('_',$extract);
        $replacement = implode('',$tempa);
        $fc = substr($fc,0,$pos).$replacement.substr($fc,$pos2);
      }
      $fc = str_replace('%%TERMFINISH%%','</a>',$fc);
//      $nnv = $tbegin.'%%TERMSTART%%__'.$st.'__'.$ti.'__"%%TERMEND%%'.$newchange.'%%TERMFINISH%%'.$tend;
      echo $fc;
    }
  }
  else
  {
    echo '<h1>Term not found</h1><br>'
         .'The term "'.$t.'" was not found.<br>'
         .'<a href="index.php">Click here</a> to go back to the home page.';
  }
  echo '<center>'.$prevstring.$actualt.$nextstring.'</center><br>';
  // create the footer
  echo '</body></html>';
  
  function SortByLength($a,$b)
  {
    // sort into array based on length, so longer terms take precedence over shorter ones
    // ideally this will only sort by the length of the term, not including the directory.
    $justa = $a;
    $pos = strrpos($a,'/');
    if ($pos !== false)
      $justa = substr($a,$pos+1);
    $justa = GetRealNode($justa);
    $justb = $b;
    $pos = strrpos($b,'/');
    if ($pos !== false)
      $justb = substr($b,$pos+1);        
    $justb = GetRealNode($justb);
    if (strlen($justb) == strlen($justa))
    {
      $ssca = substr_count($a,'/');
      $sscb = substr_count($b,'/');
      if ($justa == '__index__')
        $ssca--;
      if ($justb == '__index__')
        $sscb--;
      return $ssca-$sscb;
    }
    else
      return strlen($justb)-strlen($justa);
  }

  function DirToArray($dir)
  {
    if (substr($dir,-1) == '/')
      $dir = './'.substr($dir,0,strlen($dir)-1);
    $tempstring = DirToArrayHelper($dir);
    $contents = explode('__|__',$tempstring);
    return $contents;
  }

  function DirToArrayHelper($dir)
  {
    if ($dir == '.')
      $tdir = '';
    else
      $tdir = substr($dir,2).'/';
    $contents = '';
    foreach (scandir($dir) as $node)
    {
      //if ($node == '.' || $node == '..' || $node == '__ignore__') continue;
      if ($node == '.' || $node == '..') continue;
      if (is_dir($dir.'/'.$node))
        $contents .= DirToArrayHelper($dir.'/'.$node);
      else
        $contents .= '__|__'.$tdir.$node;
    }
    return $contents;
  }
  
  function DirToTOC($dir)
  {
    $contents = DirToTOCHelper($dir,'')."\n";
    return $contents;
  }
  
  function DirToTOCHelper($dir,$fnode)
  {
    global $checkt;
    global $tset;
    global $prevstring;
    global $nextstring;
    global $actualt;
    
    $dir .= $fnode;
    $contents = '';
    $realfnode = GetRealNode($fnode);
    // check for existence of __index__ in the current folder. if so, make it a link. if not, don't.
    $found = false;
    foreach (scandir($dir) as $node)
    {
      if ($node == '__index__.htm')
      {
        $found = true;
        break;
      }
    }
    if (strlen($dir) > 2)
    {
      $path = substr($dir,2).'/';
      if ($found)
      {
        if ($path == $checkt)
        {
          $tset = true;
          $actualt = $realfnode;
          $contents .= '<li><a href="index.php?t='.$path.'"><b>'.$realfnode.'</b></a></li>'."\n";
        }
        else
        {
          if (!$tset)
            $prevstring = '<a href="index.php?t='.$path.'">Prev</a>--|';
          if ($tset && ($nextstring == ''))
            $nextstring = '|--<a href="index.php?t='.$path.'">Next</a>';
          $contents .= '<li><a href="index.php?t='.$path.'">'.$realfnode.'</a></li>'."\n";
        }
      }
      else
        $contents .= '<li>'.$realfnode.'</li>'."\n";
      $contents .= '<ul>';
    }
    else
    {
      $contents .= '<ul>';
      $path = '';
      if ($found)
      {
        if ($path == $checkt)
        {
          $tset = true;
          $actualt = 'Home';
          $contents .= '<li><a href="index.php"><b>Home</b></a></li>'."\n";
        }
        else
        {
          if (!$tset)
            $prevstring = '<a href="index.php">Prev</a>--|';
          if ($tset && ($nextstring == ''))
            $nextstring = '|--<a href="index.php">Next</a>';
          $contents .= '<li><a href="index.php">Home</a></li>'."\n";
        }
      }
    }

    foreach (scandir($dir) as $node)
    {
      if ($node == '.' || $node == '..' || $node == '__ignore__') continue;
      if (is_dir($dir.'/'.$node))
      {
        $contents .= DirToTOCHelper($dir.'/',$node);
      }
      else
      {
        if (substr($node,-4) == '.htm')
        {
          $node = substr($node,0,strlen($node)-4);
          $realnode = GetRealNode($node);
          if ($node != '__index__')
          {
            if ($path.$node == $checkt)
            {
              $tset = true;
              $actualt = $realnode;
              $contents .= '<li><a href="index.php?t='.$path.$node.'"><b>'.$realnode.'</b></a></li>'."\n";
            }
            else
            {
              if (!$tset)
                $prevstring = '<a href="index.php?t='.$path.$node.'">Prev</a>--|';
              if ($tset && ($nextstring == ''))
                $nextstring = '|--<a href="index.php?t='.$path.$node.'">Next</a>';
              $contents .= '<li><a href="index.php?t='.$path.$node.'">'.$realnode.'</a></li>'."\n";
            }
          }
        }
      }
    }
//    if (strlen($dir) > 2)
      $contents .= '</ul>'."\n";
    return $contents;
  }
  
  function GetRealNode($lnode)
  {
    // checks for the existence of ##_ at the beginning of the folder or file.
    // if so, remove it. this is only valid if the file/folder begins with "######_ " (minus quotes).
    $pos = stripos($lnode,'_');
    if ($pos !== -1)
    {
      if (substr($lnode,$pos+1,1) == ' ')
        return substr($lnode,$pos+2);
    }
    return $lnode;
  }
  
  function ReplaceValue($nnv,$term,$st,$ti)
  {
    // this does more than just a regex. it changes the found value "example" to "e_x_a_m_p_l_e" so that it is not found again by smaller searches.
    // this will be modified later on after all the searching is done to put the correct html in place.    
    $count = 0;
    $indexterm = false;
    // check for __index__. if so, change the term to be the previous folder name (realnode-corrected) but make the link still work right.
    if ($term == '__index__')
    {
      global $tdir;
      global $childdirlist;
      global $fulldirlist;
      $indexterm = true;
      $dirtoterm = '';
      if ($st == 'L')
        $dirtoterm = $tdir;
      elseif ($st == 'C')
        $dirtoterm = $childdirlist[$ti];
      else
        $dirtoterm = $fulldirlist[$ti];
      // grab just the last dir and use that
      $pos = strrpos($dirtoterm,'/');
      $tempdir = substr($dirtoterm,0,$pos);
      $pos = strrpos($tempdir,'/');
      $term = GetRealNode(substr($tempdir,$pos+1));
    }
    while (stripos($nnv,$term) !== false)
    {
      $count++;
      $pos = stripos($nnv,$term);
      $tbegin = substr($nnv, 0, $pos);
      $tend = substr($nnv, $pos+strlen($term), strlen($nnv)-($pos+strlen($term)));
      $tochange = substr($nnv,$pos,strlen($term));
      $tochangea = str_split($tochange);
      $newchange = '__';
      for ($tci = 0; $tci < strlen($tochange); $tci++)
        $newchange .= $tochangea[$tci].'_';
      $newchange .= '_';
      // explode tochange so it doesn't get used again
      $nnv = $tbegin.'%%TERMSTART%%__'.$st.'__'.$ti.'__"%%TERMEND%%'.$newchange.'%%TERMFINISH%%'.$tend;
    }
    return $nnv;
  }
?>
