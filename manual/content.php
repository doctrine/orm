<table width=100% cellspacing=0 cellpadding=0>
    <tr>
        <td class='bordered' valign='top'>
            <?php
            if ( ! isset($_GET['chapter'])) {
            
                foreach ($i as $k => $v) {
                    $indexes = explode('.', $v['index']);
                    $level = count($indexes);
                    $e = explode(' - ', $v['name']);
                    $file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . $v['name'] . '.php';

                    print '<div class=level' . $level . '><font class=level' . $level . '>&nbsp;'. $v['index'] . '. <a href=documentation2.php?chapter='
                          . urlencode($v['name']) . ">" . end($e) ."</a></font>";
                    if ( ! file_exists($file)) {
                        //print "<font color='red'>[code]</font>";
                    }
                    print "</div>";
                }
            } else {
            
            
                $e = explode(' - ', $_GET['chapter']);
                $subchapters = false;
                $found = false;
            
                foreach ($i as $k => $v) {
                    if ($found) {
                        if (strncmp($v['name'], $_GET['chapter'], strlen($_GET['chapter'])) === 0) {
                            $subchapters = true;                                                                     	
                        }
                        break;
                    }
                    $parts = explode(' - ', $v['name']);
                    $indexes = explode('.', $v['index']);
            
                    if ($v['name'] === $_GET['chapter']) {
                        if (isset($i[($k - 1)])) {
                            $prev = $i[($k - 1)];
                        }
                        if (isset($i[($k + 1)])) {
                            $next = $i[($k + 1)];
                        }
            
                        $foundKey   = ($k + 1);
                        $found = $v;
                    }
                }
            ?>
            <table width=100% cellspacing=5 cellpadding=1>
                <tr>
                    <td align='center' colspan=2>
                        <b class='title'>
                        <?php 
                            $chapter = array_shift($parts);
                            print 'Chapter ' . $indexes[0] . '. ' . $chapter;
                        ?>
                        </b>
                    </td>
                </tr>
                <tr>
                    <td align='left'>
                    <?php
                    if(isset($prev)) {
                    ?>
                        <b><a href=documentation2.php?chapter=<?php print urlencode($prev['name']); ?>>Prev</a></b>
                    <?php
                    }
                    ?>
                    </td>
                    <td align='right'>
                    <?php 
                    if(isset($next)) {
                    ?>
                    <b><a href=documentation2.php?chapter=<?php print urlencode($next['name']); ?>>Next</a></b></td>
                    <?php
                    }
                    ?>
                </tr>
                <tr>
                    <td colspan=2>


                        <b class='title'>
                        <?php
                    
                        //print implode('.', $indexes) . '. ' . implode(' - ', $parts);
                        ?>
                        </b>
                        <hr>
                        <b class='title'>
                        <?php
                            print implode('.', $indexes) . '. ';
                            $stack = array();
                            $links = array();
                            $tmp = array_merge(array($chapter), $parts);
                            foreach($tmp as $k => $v) {
                                $stack[] = $v;
                                $links[] = "<a href=documentation2.php?chapter=" . urlencode(implode(' - ', $stack)) . '>' . $v . '</a>';
                            }
                            print implode(' - ', $links);
                        ?>
                        <br \>
                        </b>
                        <hr class='small'>
                        <?php
                        if ($subchapters) {
                        ?>
                        <b class='title'>
                        <div class='level1'> Table of contents</div>
                        </b>
                        <?php
                            for ($x = $foundKey; $x < count($i); $x++) {
                                $p = explode(' - ', $i[$x]['name']);
                                $count = (count($parts) + 1);
                                $l = count($p) - count($parts);
                                while($count--) {
                                    array_shift($p);
                                }
                                if ( ! empty($p)) {
                                    print "<div class=level" . $l . '><font class=level' . $l . '>' . $i[$x]['index'];
                                    ?>
    
                                    <a href=documentation2.php?chapter=<?php print urlencode($i[$x]['name']) . '>' . end($p); ?></a>
                                    </font></div>
                                    <?php 
                                }
                                if (strncmp($i[$x]['name'], $_GET['chapter'], strlen($_GET['chapter'])) !== 0) {
                                    break;
                                }
                            }
                        }
                        $doc->renderBlock($found['name']);
                        }
                        ?>
                    </td>
                </tr>
              </table>
        </td>
        <td width=10>
        </td>
        <td valign='top' width=300>
            <div class='smallmenu'>
                <font class=smallmenu>&nbsp; -- <a href=documentation2.php>index</a></font><br \>
                <?php
                foreach ($i as $k => $v) {
                    $indexes = explode('.', $v['index']);
                    $level = count($indexes);
                    $e = explode(' - ', $v['name']);
                    if($level === 1) {
                        $level++;
                        print '<font class=smallmenu>&nbsp;'. $v['index'] . '. <a href=documentation2.php?chapter='
                            . urlencode($v['name']) . ">" . end($e) ."</a></font><br \>";
                    }
                }
                ?>
            </div>
        </td>
    </tr>
</table>
