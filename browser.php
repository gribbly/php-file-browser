<?php
    /*  Browser V4.00 (2011, Brian Lai)
        This script cannot edit itself.
    */
    // settings
    define ("THINC_BROWSER_VERSION", 4.00);
        
    // make the interface change colour.
    $hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
    $hex = dechex (intval (ord (substr ($hostname, 0, 1))/10)*11) . 
           dechex (intval (ord (substr ($hostname, 1, 1))/10)*11) . 
           dechex (intval (ord (substr ($hostname, 2, 1))/10)*11);
    define ("HEADER_COLOR", "#$hex"); // any html colour will do
    define ("BACKUP_BEFORE_SAVING", true);
    define ("SHOW_HIDDEN_OBJECTS", true); //only checks if objects' names begin with '.'
    define ("SHOW_BACKUP_OBJECTS", false); //remove .b??????.bak files from the list
    define ("CHECK_PASSWORD", false); //show login window if...
     
    // vars
    $a = isset ($_GET['act']) ? $_GET['act']  : @$_POST['act'];
    $c = isset ($_GET['cwd']) ? $_GET['cwd']  :(isset ($_POST['cwd']) ? $_POST['cwd'] : getcwd());
    $m = isset ($_GET['mode'])? $_GET['mode'] :(isset ($_POST['mode'])? $_POST['mode']: 0);
    $f = isset ($_GET['file'])? $_GET['file'] : @$_POST['file'];
    $p1= isset ($_GET['p1'])  ? $_GET['p1']   : @$_POST['p1']; // params for $a
    $p2= isset ($_GET['p2'])  ? $_GET['p2']   : @$_POST['p2'];
    $un= isset ($_POST['username']) ? $_POST['username'] : @$_GET['username'];
    $pw= isset ($_POST['password']) ? $_POST['password'] : @$_GET['password'];
    
    // add user / sha1(pass) combinations here.
    if (CHECK_PASSWORD) {
        $allowed_users = array ('brian'=>'526242588032599f491f36c10137c88c076384ef');
        if (strlen ($un) > 0) { // login request
            if (array_key_exists ($un, $allowed_users) && 
               (sha1 ($pw) == $allowed_users[$un])) { // basically, password check
                setcookie ("username", $un, time() + 36000);
                setcookie ("password", $pw, time() + 36000);
            } else {
                $m = 8; // wrong password, switch to mode 8 (login window)
            }
        } else {
            if (isset ($_COOKIE["username"]) && isset ($_COOKIE["password"]) && 
                array_key_exists ($_COOKIE["username"], $allowed_users) && 
                $allowed_users[$_COOKIE["username"]] == sha1 ($_COOKIE["password"])) {
                // do nothing. user is authenticated.
            } else {
                // user not logged in or password is wrong
                $m = 8; // switch to mode 8 (login window)
            }    
        }
    }

    chdir ($c); // because
        
    function filelist($base,$what=2) {
        /*  what
            0 = dirs only
            1 = files only
            2 = everything  */
        $da = array();
        $mdr = opendir($base);                  // open this directory 
        while($fn = readdir($mdr)) {            // get each entry
            if (is_dir ($fn) 
                && $what != 1
                && $fn != '.'
                && $fn != '..') {
                if (SHOW_HIDDEN_OBJECTS || substr ($fn,0,1) != '.') {
                    $da[] = $fn;
                }
            } elseif ( !is_dir ($fn) 
                       && $what != 0
                       && $fn != '.'
                       && $fn != '..') {
                if (SHOW_HIDDEN_OBJECTS || substr ($fn,0,1) != '.') {
                    $da[] = $fn;
                }
            }
        }
        closedir($mdr);                         // close directory
        $indexCount = count($da);               // count elements in array
        if($indexCount>0) {
            sort($da);                          // sort will explode if count=0
            if (SHOW_BACKUP_OBJECTS != true) {
                $da = array_filter ($da, "filterbackupobjects");
            }
        }
        return $da;
    }
    
    function filterbackupobjects($var) {
        return !(substr ($var, -4, 4) == '.bak');
    }
    
    function extension ($filename) {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }
    
    function fileperm ($filename) {
        return substr(sprintf('%o', fileperms($filename)), -4);
    }
    
    function fileicon ($pn) {
        //$pn is the file name, not the path name
        switch (extension ($pn)) {
            case 'jpg'; case 'bmp'; case 'gif'; case 'png': // icons for different types
                $pp='http://img203.imageshack.us/img203/8251/iconjpg.gif';
                break; 
            case 'doc'; case 'docx'; case 'rtf'; case 'txt':
                $pp='http://img63.imageshack.us/img63/545/writedocumenticon.png';
                break;
            case 'pdf':
                $pp='http://img810.imageshack.us/img810/2958/pdficon.gif';
                break;
            case 'php'; case 'htm'; case 'html':
                $pp='http://img843.imageshack.us/img843/5929/codeicon.png';
                break; 
            case 'zip'; case 'rar'; case '7z'; case 'gz':
                $pp='http://img693.imageshack.us/img693/2083/zipicon.gif';
                break; 
            default;
                $pp='http://img266.imageshack.us/img266/5201/iconunknown.gif';
        }
        return $pp;
    }
    
    /*  modes
        0   frame                   -
        1   tree                    tree / editor
        2   editor                  editor
        3   download                _blank
        4   actions: commands       -
        5   actions: group actions  -
        6   current dir, download   -
        7   current dir, upload     tree
        8   login window (set cookies)
        9   ajax file transfer (accepts $_POST)
        99    debug
    */
    if ($m==0 || 
        $m==1 || 
        $m==2 || 
        $m==6) { // modes with html heads
?>
        <html>
            <head>
                <style type="text/css">
                    html, body, tr, th, td {
                        font-family:'Segoe UI', Arial, sans-serif;
                        font-size: 12px; }
                    body {
                        margin:0;
                        padding:0; }
                    a {
                        text-decoration:none; }
                        a:hover {
                            text-decoration:underline; }
                        a img {
                            border:0; }
                    .header {
                        margin:0 0 5px 0;
                        padding:5px;
                        background-color:<?php echo (HEADER_COLOR); ?>;
                        color:white;
                        vertical-align:top;
                        text-align:center; }
                </style>
                <!--script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js'></script-->
                <script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js'></script>
                <script type='text/javascript' src="http://thinc.netfirms.com/edit_area/edit_area_full.js"></script>
                <script type='text/javascript'>

                    function my_save (id) {
                        // alert("Here is the content of the EditArea '"+ id +"' as received by the save callback function:\n"+content);
                        // document.getElementById('save').click();
                        $(document).ready (function () {
                            $.ajax({
                                type: 'POST',
                                url: '<?php echo (basename (__FILE__)); ?>',
                                data: {
                                    mode: '9',
                                    file: '<?php echo ($f); ?>',
                                    cwd:  '<?php echo ($c); ?>',
                                    p: editAreaLoader.getValue(id)
                                },
                                success: function (data) {
                                    alert (data);
                                },
                                error: function (data) {
                                    document.getElementById('save').click(); // non-ajax
                                },
                                dataType: 'html'
                            });
                        });
                    }

                    function my_save_2 (id, content) {
                        // alert("Here is the content of the EditArea '"+ id +"' as received by the save callback function:\n"+content);
                        document.getElementById('save').click();
                        /*$(document).ready (function () {
                            $.ajax({
                                type: 'POST',
                                url: '<?php echo (basename (__FILE__)); ?>',
                                data: {
                                    mode: '9',
                                    file: '<?php echo ($f); ?>',
                                    cwd:  '<?php echo ($c); ?>',
                                    p: content
                                },
                                success: function (data) {
                                    alert (data);
                                },
                                dataType: 'html'
                            });
                        });*/
                    }

                    $(document).ready (function () {
                        function rot13(s) {
                            return s.replace(/[a-zA-Z]/g,function(c){
                            return String.fromCharCode((c<="Z"?90:122)>=(c=c.charCodeAt(0)+13)?c:c-26);})
                        }
                        editAreaLoader.init({
                            id: "p" // id of the textarea to transform      
                            ,start_highlight: true  // if start with highlight
                            ,allow_toggle: false
                            ,word_wrap: false
                            ,syntax: "php"
                            ,replace_tab_by_spaces:4
                            ,toolbar: "save,undo,redo,search,reset_highlight,word_wrap,fullscreen,select_font,syntax_selection"
                            ,font_family: "monaco, consolas, monospace"
                            ,font_size: "9"
                            // ,load_callback: "my_save_2"
                            ,save_callback: "my_save"
                        });                    
                    });
                </script>
            </head>
<?php
    }
    switch ($m) { case 0:
        // frame
?>
        <frameset cols="300px,*">
            <frame name="tree" src="<?php echo ("?mode=1"); ?>" />
            <frame name="editor" src="<?php echo ("?mode=2"); ?>" />
        </frameset><noframes></noframes>
<?php
    break; case 1:
        // tree 
        echo("<body>
                <p class='header'>
                    <a href='?cwd=" . dirname ($c) . "&amp;mode=1' target='tree'>
                        <img src='http://img707.imageshack.us/img707/1033/iconfolderup.gif' />
                    </a>
                    <b>$c</b>
                </p>
                <form method='post' target='tree' action='?mode=5'>
                <!-- ?mode=5 is needed -->
                    <table cellspacing='0' cellpadding='2'>
                        <tr>
                            <th>&nbsp;</th>
                            <th>&nbsp;</th>
                            <th style='width:100%'>Name</th>
                            <th>Size</th>
                            <th>FilePerm</th>
                        </tr>");

        $da = filelist ($c);
        $i = 0;
        foreach ($da as $pn) {
            $i++;
            
            $pp = fileicon ($pn);

            @printf ("    <tr %s>
                            <td>
                                <input type='checkbox' name='c$i' value='1' />
                                <input type='hidden' name='f$i' value='$c/$pn' />
                            </td>
                            <td style='width:1px'>
                                %s
                                <img src='$pp' style='width:16px;max-height:16px;' />
                                %s
                            </td>
                            <td style='width:100%%'>
                                <a href='?cwd=%s&amp;file=$pn&amp;mode=%d'
                                   target='%s' style='%s'>$pn</a>
                            </td>
                            <td style='text-align:right;'>%d</td>
                            <td style='text-align:right;'>%s</td>
                        </tr>",
                    (($i % 2 == 0)? "style='background-color:#eee;'": ''), //tr
                    (is_dir ("$c/$pn"))? 
                        '':
                        "<a href='?cwd=$c&amp;file=$pn&amp;mode=3' target='_blank'>",
                    (is_dir ("$c/$pn"))? 
                        '':
                        '</a>',
                    (is_dir ("$c/$pn"))? "$c/$pn" :$c, //cwd
                    (is_dir ("$c/$pn"))? 1 : 2,
                    (is_dir ("$c/$pn"))? "tree" : "editor",
                    (is_dir ("$c/$pn")?
                        'background-color:' . HEADER_COLOR . ';padding:3px;color:white;':
                        ''),
                    @filesize ("$c/$pn"),
                    @fileperm ("$c/$pn"));
        }

        $dts=disk_total_space(getcwd());
        $dpf=($dts!=0)?round(disk_free_space(getcwd())/$dts*100,2):0; //calculate disk space
        $phv=phpversion();
        echo("      </table>
                    <p>($i items shown)</p>
                    <p class='header'>Selected items:</p>
                    <input type='radio' name='act' value='rm'>rm <br />
                    <input type='radio' name='act' value='archive'>archive <br /><br />
                    <input type='hidden' name='cwd' value='$c' />
                    <input type='hidden' name='mode' value='5' />
                    <input type='hidden' name='fcount' value='$i' />
                    <input type='submit' />
                </form>
                <form method='post' target='tree' action='?mode=7'
                      enctype='multipart/form-data'>
                <!-- ?mode=7 is needed -->
                    <p class='header'>Upload</p>
                    <table>
                        <tr><td>File:</td>
                            <td>
                                <input type='file' name='fileobj' />
                            </td>
                        </tr>
                        <tr><td>Overwrite?</td>
                            <td>
                                <input type='checkbox' id='overwrite' name='overwrite' value='1'/>
                            </td>
                        </tr>
                        <tr><td></td>
                            <td>
                                <input type='hidden' name='mode' value='7' />
                                <input type='hidden' name='cwd' value='$c' />
                                <input type='submit' />
                            </td>
                        </tr>
                    </table>
                </form>
                <form method='post' target='tree' action='?mode=4'>
                <!-- ?mode=4 is needed -->
                    <p class='header'>Execute</p>
                    <table>
                        <tr><td>Command:</td>
                            <td>
                                <input type='text' name='act' />
                            </td>
                        </tr>
                        <tr><td>Param 1:</td>
                            <td>
                                <input type='text' id='p1' name='p1' value='$c'/>
                            </td>
                        </tr>
                        <tr><td>Param 2:</td>
                            <td>
                                <input type='text' id='p2' name='p2' />
                            </td>
                        </tr>
                        <tr><td></td>
                            <td>
                                <input type='hidden' name='mode' value='4' />
                                <input type='hidden' name='cwd' value='$c' />
                                <input type='submit' />
                            </td>
                        </tr>
                    </table>
                </form>
                <p>Commands: chmod(p1,p2), cp(p1,p2), delete(p1), exec(p1), 
                    mkdir(p1), mkfile(p1), mv(p1,p2), 
                    rename(p1,p2), rmdir(p1), touch(p1)</p>
                <hr />
                <p><b>&copy; 2011 Sparta File Manager V " . THINC_BROWSER_VERSION . "</b><br />
                   <a href='http://ohai.ca'>Brian Lai</a><br />
                   php version $phv<br />
                   cwd: " . getcwd() . "<br />
                   disk $dpf% free</p>
            </body>
        </html>");
?>
<?php
    break; case 2:
        // editor
        if ($f) { // if I need to open/save a file then show...
            if (isset($_POST['p'])) { // save?
                $p=$_POST['p'];
               
                $pr = false; 
                //pretend this is a backup
                if (BACKUP_BEFORE_SAVING) {
                    $pcd = date('ymd');
                    $pr = @copy ("$c/$f","$c/$f.b$pcd.bak");
                }
                
                if ($pr == BACKUP_BEFORE_SAVING) {
                    $fh = @fopen("$c/$f", 'w') or die();
                    @fwrite ($fh, stripslashes($p));
                    fclose ($fh);
                    echo("<p>$c/<b>$f</b> is supposedly saved. (?)</p>");  
                } else {
                    echo("<p><b>$f</b> 
                    is <span style='color:red'>NOT</b> saved.</p>");  
                }  
            } 

            $fh = @fopen ("$c/$f", 'r') or die('Failed to read file.');
            $p = @fread ($fh, filesize("$c/$f")); //the @ is required because fread complains about a 0-len read
            fclose ($fh);
            
            echo("  <body style='overflow: hidden;'>
                        <form method='post'>
                            <textarea class='php editor' 
                                       name='p' 
                                         id='p'
                                      style='width:100%;height:100%;'>" . 
                                htmlspecialchars ($p) . "</textarea><input type='hidden' name='cwd' value='$c' />
                            <input type='hidden' name='file' value='$f' />
                            <input type='hidden' name='mode' value='2' />
                            <input type='submit' name='save' id='save' value='Save' style='display:none' />
                        </form>
                    </body>
                </html>");
        }
?>
<?php
    break; case 3:
        // download - will fail if server RAM limit < filesize
        header ("Content-type: application/force-download");
        header ("Content-Disposition: attachment; filename=\"$f\"");
        header ("Content-Length: " . filesize("$c/$f"));
        @readfile ("$c/$f");
        exit();
?>
<?php
    break; case 4:
        // commands

        // transform params
        $p1 = htmlspecialchars(urldecode ($p1));
        $p2 = htmlspecialchars(urldecode ($p2));

        switch (strtolower ($a)) {
            case 'mv'; case 'rename':
                rename ($p1, $p2);
                break;
            case 'chmod':
                chmod ($p1, $p2);
                break;
            case 'cp':
                copy ($p1, $p2);
                break;
            case 'exec':
                exec ($p1);
                break;
            case 'mkdir':
                mkdir ($p1);
                break;
            case 'mkfile'; case 'touch':
                touch ($p1);
                break;
            case 'delete'; case 'rm':
                unlink ($p1);
                break;
            case 'rmdir':
                rmdir ($p1);
                break;
            default:
                die("No such command: $a");
                exit();
        }

        $cf = basename ($_SERVER['SCRIPT_FILENAME']);
        $pf = 'http://' . $_SERVER['SERVER_NAME'];

        header ("location: $pf/$cf?cwd=$c&file=$f&mode=1");
?>

<?php
    break; case 5:
        // group actions
        
        $ub = $_POST['fcount']; //upper bound of files in pane
        if (!$ub) die(); // do not proceed if you don't have anything to do
        
        for ($i=1; $i<=$ub; $i++) {
            $p1 = $_POST["c$i"]; // these are not the same params as mode 4
            $p2 = $_POST["f$i"];
            
            if ($p1 == '1') { // checkbox for this file is enabled
                switch (strtolower ($a)) {
                    case 'delete'; case 'rm'; case 'rmdir':
                        unlink ($p2);
                        break;
                    case 'archive':
                        $pcd = date('ymd');
                        mkdir ("$c/archive.b$pcd/");
                        rename ($p2, "$c/archive.b$pcd/" . basename ($p2));
                    default:
                }
            }
        }

        $cf = basename ($_SERVER['SCRIPT_FILENAME']);
        $pf = 'http://' . $_SERVER['SERVER_NAME'];

        header ("location: $pf/$cf?cwd=$c&file=$f&mode=1");
?>
<?php
    break; case 6:
        // current folder, download only
        $c = getcwd ();
        echo("<body>
                <p class='header'>
                    <b>" . basename ($c) . "</b>
                </p>
                <table cellspacing='0' 
                       cellpadding='2' 
                       style='display:block;margin:auto;width:500px;'>");

        $da = filelist ($c,1);
        $i = 0;
        foreach ($da as $pn) {
            if (substr($pn,0,1) != '.') { // don't show hidden files
                $i++;
                $pp = fileicon ($pn);

                printf ("    <tr %s>
                                <td style='width:1px'>
                                    <img src='$pp' style='width:16px;max-height:16px;' />
                                </td>
                                <td style='width:100%%;'>
                                    <a href='?file=$pn&amp;mode=3' target='_blank'>$pn</a>
                                </td>
                            </tr>",
                        (($i % 2 == 0)? "style='background-color:#eee;'": ''));
            }
        }
        echo("      </table>
                </body>
            </html>");
?>
<?php
    break; case 7:
        // current folder, upload only
        // this provides no feedback, and overwrites any files.
        if (isset ($_FILES['fileobj'])) {
            if (isset ($_POST['overwrite']) && $_POST['overwrite'] == '1') {
                // upload if file exists (too).
                move_uploaded_file ($_FILES['fileobj']['tmp_name'], 
                            "$c/" . $_FILES['fileobj']['name']);
            } else {
                // upload if file doesn't exist.
                if (!file_exists ("$c/" . $_FILES['fileobj']['name'])) {
                    move_uploaded_file ($_FILES['fileobj']['tmp_name'], 
                                "$c/" . $_FILES['fileobj']['name']);
                }
            }
        }
        $cf = basename ($_SERVER['SCRIPT_FILENAME']);
        $pf = 'http://' . $_SERVER['SERVER_NAME'];
        header ("location: $pf/$cf?cwd=$c&file=$f&mode=1");
?>
<?php
    break; case 8:
        // login window to set login cookies
        // if no cookie is set, all modes will redirect here.
        echo ("<html>
                    <head><style type='text/css'>
                        input {border: 1px solid silver;padding:5px;}
                    </style></head>
                    <body style='background-color:#eee;font-family:sans-serif;
                                 line-height:1.5em;font-size:0.8em;'>
                        <div style='background-color:#fff;position:fixed;
                                    left:50%;top:50%;width:250px;margin-left:-125px;
                                    height:150px;margin-top:-75px;text-align:center;
                                    padding:20px;border:1px solid silver;'>
                            <form method='post'>
                                <label for='username'>User name: </label><br />
                                <input id='username' name='username' type='text' /><br />
                                <label for='password'>Password: </label><br />
                                <input id='password' name='password' type='password' /><br />
                                <br />
                                <input type='submit' value='Log in' />
                            </form>
                        </div>
                    </body>
                </html>");
?>
<?php
    break; case 9:
        // ajax file upload
        if ($f) { // if I need to open/save a file then show...
            if (isset($_POST['p'])) { // save?
                $p=$_POST['p'];
               
                $pr = false; 
                //pretend this is a backup
                if (BACKUP_BEFORE_SAVING) {
                    $pcd = date('ymd');
                    $pr = @copy ("$c/$f","$c/$f.b$pcd.bak");
                }
                
                if ($pr == BACKUP_BEFORE_SAVING) {
                    $fh = @fopen("$c/$f", 'w') or die();
                    @fwrite ($fh, stripslashes($p));
                    fclose ($fh);
                    echo ("Saved.");
                } else {
                    echo ("Failed to save $c/$f !");
                }  
            } 
        }
?>
<?php
    break; case 99:
        // debug
        echo ("<pre>");
        print_r ($_GET);
        print_r ($_POST);
        echo ("</pre>");
?>
<?php
    break; default:
    }
?>
