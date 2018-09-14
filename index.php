<?php
ini_set("display_errors", 1);
define('APP_PATH',      realpath(dirname(__FILE__).'/../../'));
$paths = explode('/', APP_PATH);
$app_name = $paths[count($paths)-1];
define('APP_NAME', $app_name);

if (count($_GET) == 0) {
    show_header();
    echo "<div class='pad'>"
    . "<div class='leftPad'>";
    show_modules();
    echo "</div></div>";
    show_footer();

} else if (isset($_GET['test']) && isset($_GET['module'])) {
    show_header();
    echo "<div class='pad'>"
    . "<div class='leftPad'>";
    show_modules();
    echo "</div><div class='rightPad'>";
    show_test();
    show_footer();
}

function parse_class_comment($comment) {
    $lines = explode("\n", $comment);
    foreach ($lines as $line) {
        $line = trim($line, " *");
        if (strncmp($line, "@description", 10) === 0) {
            list($name, $desc) = explode(" ", $line, 2);
            return trim($desc);
        }
    }
    return '';
}

function getDoc($comment) {
    $lines = explode("\n", $comment);
    $rets = [];
    foreach ($lines as $line) {
        $line = trim($line, " *");
        $line = trim($line, " /**");
        $line = trim($line, " */");
        if (!empty($line)) {
            $rets[] = $line;
        }
    }
    return join("</br>", $rets);
}

function show_modules() {
    echo "<h2>All Support Modules</h2>";
    echo '<table class="dataintable">
            <tr><th>Modules</th> <th></th></tr>';
    
    $moduleDir = APP_PATH . '/Controllers/';
    $files = [];
    foreach (glob($moduleDir . '*.php') as $file) {
        $tmparg = explode('/', $file);
        $tmparg = array_reverse($tmparg, false);
        $controller =  strtolower(str_replace('.php', '', $tmparg[0]));
        echo "<tr> ";
        echo "<td><a>index/$controller  </a></td>";
        echo "<td><a href='/?module=".str_replace('\\', '/', "index/$controller")."&test'>Suites</a></td>";
        echo "</tr>";
    }
    
    $moduleDir = APP_PATH . '/Modules/';
    $files = [];
    foreach (glob($moduleDir . '*') as $tmppath) {
       $files = array_merge($files, glob($tmppath. '/Controllers/*.php'));
    }
    foreach ($files as $file) {
        $tmparg = explode('/', $file);
        $tmparg = array_reverse($tmparg, false);
        $controller =  strtolower(str_replace('.php', '', $tmparg[0]));
        $module = strtolower($tmparg[2]);
        echo "<tr> ";
        echo "<td><a>$module/$controller  </a></td>";
        echo "<td><a href='/?module=".str_replace('\\', '/', "$module/$controller")."&test'>Suites</a></td>";
        echo "</tr>";
        
    }
    echo "</table>";
}

function show_form_header($module, $method) {
    $module = str_replace('\\', '/', $module);
    $uri = get_uri();
    $url = $uri.'/'.$module.'/'.$method;
    echo <<<HTML
    <form action="$url" id="{$module}_{$method}" method='post' target='_blank'>
HTML;
}

function show_form_footer($module) {
    echo <<<HTML
    <br>
    <h4>返回结果</h4>
    <div class='result'>返回结果</div>
    </form>
HTML;
}

/**
 * @param $module
 * @param $method
 * @param array  $parameters [
 *   @type string $name
 *   @type string $type
 *   @type string description
 * ]
 * @param $headers
 */
function show_form_fields($module, $method, $parameters) {
    $module = str_replace('\\', '/', $module);
    
    $url = get_uri();;
    echo "<h4>参数列表:</h4>";
    echo "<ul>";
    echo "<li><label>URL:</label><div class='url'>$url/$module/$method</div></li>";
    foreach ($parameters as $v) {
        $must = $v['must'] ? '<span>*</span>' : '';
        $type = empty($v['type']) ? '' : ('('.$v['type'].')');
        echo "<li class=''><label class=''>{$must}{$type} {$v['name']}:</label><input type=text name='{$v['name']}' value='{$v['default']}'>&nbsp;";
        echo "<i class=''>{$v['description']}</i></li>";
    }

    echo "</ul>";
    echo <<<EOF
    <button type='submit' id='openCurrent'>Submit</button>
    <input type='button' class='openNewWindow' url='$url/$module/$method' form_id='{$module}_{$method}' value='openNewWindow'/>
EOF;

}

function filter_description($str) {
    $str = str_replace(array("MUST", "OPTIONAL"), "", $str);
    $str = preg_replace("/##[^#]+##/", "", $str);
    return $str;
}

function parse_comment($comment) {
    $parameters = array();
    $caption = '';
    
    $lines = explode(PHP_EOL, $comment);
    foreach ($lines as $line) {
        $line = ltrim($line, " */");
        
        if (strncmp($line, "@param", 6) === 0) {
            $line = preg_replace("/\s+/", " ", $line);
            $data = explode(" ", $line, 4);
            if (count($data) < 2) {
                continue;
            }
            $parameters[] = array(
                'type' => isset($data[2]) ? $data[2] : '',
                'name' => str_replace("\$", "", $data[1]),
                'description' => isset($data[3]) ? filter_description($data[3]) : '',
                'must' => strpos($line, 'MUST') > 0,
                'default' => get_default_value($line),
            );
        } else if (strncmp($line, "@description", 12) === 0) {
            $caption = substr($line, 12);
        }
    }
    return array($parameters, $caption);
}

function get_default_value($line) {
    if (preg_match("/##([^#]+)##/", $line, $matches)) {
        if (preg_match("/\(\"([^\"]+)\"\)/", $matches[1], $code)) {
            $data = "";
            eval("\$data=" . $code[1]);
            return $data;
        }
        return $matches[1];
    }
    return '';
}

function show_methods($module, $methods) {
    echo "<a name='ml'></a>";
    echo "<table class='dataintable method_list method_listDown'>";
    echo "<tr><th>Module <i>$module</i> methods <a href='#TOP'>TOP</a></th></tr>";
    getMethodsHtml($methods);
    echo "</table>";

    echo "<table class='dataintable method_list'>";
    echo "<tr><th colspan=2>Module <i>$module</i> methods </a></tr>";
    getMethodsHtml($methods);
    echo "</table>";
}

function getMethodsHtml($methods) {
    foreach ($methods as $name) {
        echo "<tr><td><a href='#$name'><strong class=''>$name</strong>();</a></td></tr>";
    }
}


function getExcludeMethod() {
    return array(
        "getInstance",
        "registerProcessBeforeSendEvent",
        "registerProcessParams",
        "fireProcessParams",
        "getResultType",
        "setResultType",
    );
}

function show_test($module = '') {
    $module = $module ? : $_GET['module'];
    if (empty($module)) {
        return;
    }
    echo "<h2 name='TOP'>Module <i>$module</i></h2>";
    $tmpm = explode('/', $module);
    if(ucfirst($tmpm[0])=='Index'){
        $filename = APP_PATH.'/Controllers/'.ucfirst($tmpm[1]).'.php';
    }else{
        $filename = APP_PATH.'/Modules/'.ucfirst($tmpm[0]).'/Controllers/'.ucfirst($tmpm[1]).'.php';
    }
    
    $methods = get_method_from_file($filename);
    $methodsname = array_keys($methods);
    show_methods($module, $methodsname);
    echo "</div></div>";
    
    $content  = file_get_contents($filename);
    preg_match('/<\?php.*?class/s', $content, $com_preg);
    $class_comstr = $com_preg[0];
    preg_match('{/\*.*?\*/}s', $class_comstr, $com_preg2);
    $comment = isset($com_preg2[0]) ? $com_preg2[0] : '';
    echo "<h2>类说明</h2><h3>";
    if(!empty($comment)){
        echo getDoc($comment);
    }
    echo "</h3>";
    
    $i = 0;
    foreach ($methods as $name=>$md_str) {
        $i ++;
        $className = '';
        list($parameters, $caption) = parse_comment($md_str);
        echo "<a name='$name'></a>";
        echo "<h3 class='$className'>$i. public function $name(\$params); </h3>";
        $caption = str_replace(array('；', "\r", "\n"), array(';', '', ''), $caption);
        if(!empty($caption)){
            echo '<ol style="margin: 0; padding: 0 35px;">';
            foreach (explode(';', $caption) as $item) {
                echo '<li>'.$item.'</li>';
            }
            echo '</ol>';
        }
        show_form_header($module, $name);
        show_form_fields($module, $name, $parameters);
        show_form_footer($name);
    }
    echo "</ul>";
}

function getDeprecatedClass($deprecated) {
    $className = "";
    if ($deprecated) {
        $className = "line-through";
    }
    return $className;
}

function show_header() {
    $dir = __DIR__;
    $branch = `cd $dir ;git branch 2>&1|grep '*'`;
    $app_name = APP_NAME;
    
    echo <<<HTML
<!doctype html>
<head>
    <meta charset='utf8'>
    <title>{$app_name} Test Suites</title>
    <style>
    body{font-family:Verdana,Arial,"Microsoft YaHei",微软雅黑,"MicrosoftJhengHei",华文细黑,STHeiti,MingLiu, monospace; font-size: 12px;}
    table.dataintable {
        margin-top:10px;
        border-collapse:collapse;
        border:1px solid #aaa;
        max-width:90%;
	}
    
    table.dataintable th {
        vertical-align:baseline;
        padding:5px 5px 5px 6px;
        background-color:#d5d5d5;
        border:1px solid #aaa;
        text-align:left;
    }

    table.dataintable td {
        vertical-align:text-top;
        padding:6px 5px 6px 6px;
        background-color:#efefef;
        border:1px solid #aaa;
    }

    table.dataintable td a{
        padding:0px;
    }

    table.dataintable pre {
        width:auto;
        margin:0;
        padding:0;
        border:0;
        background-color:transparent;
    }

    .dataintable > tbody > tr:hover > td,
    .table-hover > tbody > tr:hover > th {
        background-color: #CBCBCB;
    }

    .line-through {
        text-decoration: line-through
    }

    a{text-decoration:none;color:blue;}
    h2{color:#555;}
    h3,h4,h5,h6{color:#777;}
    h2,h3,h4{width:90%;display:inline-block;border-bottom:solid 1px #ccc;}
    form li,form ul{list-style:none;margin:0;padding:0;white-space:pre-wrap;}
    form ul li i {  font-size: 10px;}
    form li label{text-align:right;width:200px;display:inline-block;padding:0;margin:0;}
    form li input{width:20em;margin-left:1em;}
    form li.caseList {float:left;width:100%}
    form li.case label{text-align:right;width:70px;display:inline-block;padding:0;margin:0;}
     .tools{width:30px;height:30px;float:left;cursor: pointer;}
    form li textarea{width:65%;margin-left:1em;}
    form button{margin-left:25em;width:10em;}
    form span{color:black;padding-right:0.5em;}
    form .result{margin-left:11em;border:solid 1px #ccc;display:none;width:auto;max-width:900px;white-space:pre-wrap;}
    form .url{display:inline-block;margin-left:1em;}
    .module-list{display:inline-block;width:20em;}
    .ml li a{color:blue;}
    .method_list  a{padding:1em;}
    .method_listTop{position:fixed;right:10px;top:10px;border:solid #ccc 1px;padding:1em; }
    .method_listDown{position:fixed;right:10px;bottom:10px;border:solid #ccc 1px;padding:1em;}
    .pad{width:100%;}
    .leftPad{; float:left;}
    .rightPad{width:70%;float:left;}
    .set_show_case{position:fixed;top:10px;right:1em;border:solid 1px #ccc;padding:1em;}
    .red{color:red;}
    .yellow{color:#acac00;}
    .blue{color:#0000ff;font-weight: bold;}
    </style>
    <script src='http://apps.bdimg.com/libs/jquery/1.6.4/jquery.min.js'></script>
</head>
<body>
<h1>{$app_name} <strong style="color:red;">[{$branch}]</strong> </h1>

<h2>调用{$app_name}接口必须 </h2>
<ol id='notice'>
<li><strong>设置Develop.ini文件或存在sessionid</strong></li>
<li><strong>设定User-Agent, 格式 &lt;project name&gt;/&lt;project version&gt;</strong> 示例: <i>User-Agent: smart_alliance/1.0</i></li>
</ol>
HTML;
}

function show_footer() {
    echo <<<'HTML'
<script src='/base64.js'></script>
<script src='/index.js'></script>
<div class='method_list method_listDown'><a href='#TOP'>^</a></div>
<script>
$(".set_show_case").click(function() {
    var c = document.cookie, 
        suffix = 'domain=.alliance.bytedance.net; expires=Fri, 31 Dec 9999 23:59:59 GMT;';
    if(c.indexOf("sc=1") >= 0) {
        document.cookie = 'sc=0; ' + suffix;
    } else {
        document.cookie = 'sc=1; ' + suffix;
    }
    location.reload();
});
</script>
</body>
</html>
HTML;
}

function set_show_case() {
    $GLOBALS['show_case'] = $_COOKIE['sc'];
}

function get_method_from_file($file){
    $content = file($file);
    $lnum = count($content);
    $methods = [];
    for ($i=$lnum;$i>0;$i--){
        $line = $content[$i-1];
        preg_match('/(?<=function).*?(?=Action)/', $line, $method);
        if(!empty($method)){
            $curmethod = trim(current($method));
            $methodstr = '';
            continue;
        }
        
        preg_match('/[\}\{]/', $line, $end);
        if(!empty($curmethod) && !empty($end)){
            preg_match('{/\*.*?\*/}s', $methodstr, $method_preg);
            $methods[$curmethod] = isset($method_preg[0]) ? $method_preg[0] : '';
            $curmethod  = false;
            continue;
        }
        
        if(!empty($curmethod) && empty($end)){
            $methodstr = $line.PHP_EOL.$methodstr;
        }
        
    }
    $methods = array_reverse($methods);
    return $methods;
}

function get_uri(){
    $host = rtrim(str_replace('test.', '', explode('?', $_SERVER['HTTP_HOST'])[0]), '/');
    if($_SERVER['SERVER_PORT']==443){
        $xiyi = 'https://';
    }else{
        $xiyi = 'http://';
    }
    return $xiyi.$host;
    
}
