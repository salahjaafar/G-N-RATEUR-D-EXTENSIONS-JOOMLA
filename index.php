<?php
/**
 * =============================================================
 * GÉNÉRATEUR D'EXTENSIONS JOOMLA — Version 6.0
 * Composants • Modules • Plugins
 * Simple • Basic • Avancé
 * Joomla 3.x • 4.x • 5.x • 6.x
 * =============================================================
 */
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $extensionType  = $_POST['extension_type'] ?? 'component';
    $name           = trim($_POST['extension_name'] ?? '');
    $alias          = strtolower(trim($_POST['extension_alias'] ?? ''));
    $vendor         = ucfirst(preg_replace('/[^a-zA-Z0-9]/', '', $_POST['vendor_name'] ?? '')) ?: 'MyCompany';
    $author         = trim($_POST['author'] ?? '');
    $authorEmail    = trim($_POST['author_email'] ?? '');
    $authorUrl      = trim($_POST['author_url'] ?? '');
    $version        = trim($_POST['version'] ?? '1.0.0');
    $description    = trim($_POST['description'] ?? '');
    $license        = trim($_POST['license'] ?? 'GNU/GPL');
    $structureType  = $_POST['structure_type'] ?? 'advanced';
    $pluginGroup    = $_POST['plugin_group'] ?? 'content';
    $moduleClient   = $_POST['module_client'] ?? 'site';
    $joomlaVersion  = $_POST['joomla_version'] ?? '5';

    $errors = [];
    if (empty($name))  $errors[] = "Le nom est requis";
    if (empty($alias)) $errors[] = "L'alias est requis";
    if (!preg_match('/^[a-z][a-z0-9_]*$/', $alias)) $errors[] = "Alias invalide";
    if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) $errors[] = "Version au format x.y.z";

    if (empty($errors)) {
        $params = compact('name','alias','vendor','author','authorEmail','authorUrl','version','description','license','structureType','pluginGroup','moduleClient','joomlaVersion');
        switch ($extensionType) {
            case 'module':  generateModule($params); break;
            case 'plugin':  generatePlugin($params); break;
            default:        generateComponent($params); break;
        }
    }
}

/* ═══════════════════ UTILITAIRES ═══════════════════ */

function idx($p) { file_put_contents($p,'<html><body bgcolor="#FFFFFF"></body></html>'); }
function mkdirs(array $dirs) { foreach ($dirs as $d) if (!is_dir($d)) mkdir($d,0755,true); }
function isJ3($p) { return $p['joomlaVersion']==='3'; }
function isJ4Plus($p) { return in_array($p['joomlaVersion'],['4','5','6']); }
function isJ5Plus($p) { return in_array($p['joomlaVersion'],['5','6']); }

function meta($p) {
    return ['date'=>date('F Y'),'year'=>date('Y'),'uc'=>ucfirst($p['alias']),'upper'=>strtoupper($p['alias'])];
}

function jVer($p) {
    $map = ['3'=>'3.10','4'=>'4.4','5'=>'5.2','6'=>'6.0'];
    return $map[$p['joomlaVersion']] ?? '5.0';
}

function phpMin($p) {
    $map = ['3'=>'7.4','4'=>'7.4','5'=>'8.1','6'=>'8.2'];
    return $map[$p['joomlaVersion']] ?? '8.1';
}

function useStmt($p, $classes) {
    if (isJ3($p)) return '';
    $r = '';
    foreach ($classes as $c) $r .= "use $c;\n";
    return $r;
}

function fct($p)  { return isJ3($p) ? 'JFactory' : 'Factory'; }
function txt($p)  { return isJ3($p) ? 'JText'    : 'Text'; }
function tbh($p)  { return isJ3($p) ? 'JToolbarHelper' : 'ToolbarHelper'; }

function fileHeader($p, $pkg, $sub) {
    $jv = jVer($p);
    return "<?php\n/**\n * @package     $pkg\n * @subpackage  $sub\n * @author      {$p['author']}\n * @copyright   Copyright (C) ".date('Y')." {$p['author']}\n * @license     {$p['license']}\n * @version     {$p['version']}\n * @joomla      $jv\n */\n\ndefined('_JEXEC') or die;\n\n";
}

function extTag($p) {
    return isJ3($p) ? '<extension type="%s" %sversion="3.0" method="upgrade">' : '<extension type="%s" %smethod="upgrade">';
}

function pluginEvents($g) {
    $m = [
        'content'=>['onContentPrepare','onContentBeforeSave','onContentAfterSave','onContentAfterDelete'],
        'system'=>['onAfterInitialise','onAfterRoute','onBeforeRender','onAfterRender'],
        'user'=>['onUserAfterSave','onUserAfterDelete','onUserLogin','onUserLogout'],
        'authentication'=>['onUserAuthenticate'],
        'editors'=>['onInit','onDisplay','onGetContent','onSetContent'],
        'editors-xtd'=>['onDisplay'],
        'finder'=>['onFinderAfterSave','onFinderAfterDelete','onFinderChangeState'],
        'installer'=>['onInstallerBeforeInstallation','onInstallerAfterInstaller'],
        'extension'=>['onExtensionAfterInstall','onExtensionAfterUninstall','onExtensionAfterUpdate'],
        'actionlog'=>['onContentAfterSave','onContentAfterDelete'],
        'quickicon'=>['onGetIcons'],
        'task'=>['onExecuteTask'],
    ];
    return $m[$g] ?? $m['content'];
}

function zipAndFinish($dir, $label) {
    $z = $dir.'.zip'; createZip($dir,$z); delDir($dir);
    $_SESSION['success'] = "$label : <a href='$z' download class='download-link'>📦 Télécharger $z</a>";
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

function createZip($s,$d) {
    $z = new ZipArchive(); $z->open($d,ZipArchive::CREATE|ZipArchive::OVERWRITE);
    $s = realpath($s);
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($s),RecursiveIteratorIterator::LEAVES_ONLY);
    foreach ($it as $f) if (!$f->isDir()) $z->addFile($f->getRealPath(), substr($f->getRealPath(), strlen($s)+1));
    $z->close();
}

function delDir($d) {
    if (!file_exists($d)) return; if (!is_dir($d)){unlink($d);return;}
    foreach (scandir($d) as $i){if($i==='.'||$i==='..')continue;delDir("$d/$i");}
    rmdir($d);
}

/* ═══════════════════ COMPOSANT ═══════════════════ */

function generateComponent($p) {
    $d = "com_{$p['alias']}";
    switch ($p['structureType']) {
        case 'simple':  generateSimpleComp($p,$d); break;
        case 'basic':   generateBasicComp($p,$d); break;
        default:        isJ3($p) ? generateAdvancedCompJ3($p,$d) : generateAdvancedCompModern($p,$d); break;
    }
    $t=['simple'=>'Simple','basic'=>'Basic','advanced'=>'Avancé'];
    $jl = jVer($p);
    zipAndFinish($d,"Composant {$t[$p['structureType']]} <strong>$d</strong> (Joomla $jl) généré");
}

/* ─── COMPOSANT SIMPLE ─── */
function generateSimpleComp($p,$d) {
    $a=$p['alias']; $m=meta($p);
    mkdirs(["$d","$d/admin","$d/admin/sql/updates/mysql","$d/site"]);
    idx("$d/admin/index.html"); idx("$d/admin/sql/index.html");
    idx("$d/admin/sql/updates/index.html"); idx("$d/admin/sql/updates/mysql/index.html");
    idx("$d/site/index.html");

    $extAttr = isJ3($p) ? 'version="3.0" ' : '';
    $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<extension type=\"component\" {$extAttr}method=\"upgrade\">\n";
    $xml.= "    <name>{$p['name']}</name>\n    <author>{$p['author']}</author>\n    <creationDate>{$m['date']}</creationDate>\n";
    $xml.= "    <copyright>Copyright (C) {$m['year']} {$p['author']}</copyright>\n    <license>{$p['license']}</license>\n";
    $xml.= "    <authorEmail>{$p['authorEmail']}</authorEmail>\n    <authorUrl>{$p['authorUrl']}</authorUrl>\n";
    $xml.= "    <version>{$p['version']}</version>\n    <description>{$p['description']}</description>\n\n";
    $xml.= "    <install><sql><file driver=\"mysql\" charset=\"utf8\">sql/updates/mysql/{$p['version']}.sql</file></sql></install>\n";
    $xml.= "    <update><schemas><schemapath type=\"mysql\">sql/updates/mysql</schemapath></schemas></update>\n\n";
    $xml.= "    <files folder=\"site\">\n        <filename>$a.php</filename>\n        <filename>index.html</filename>\n    </files>\n\n";
    $xml.= "    <administration>\n        <menu>COM_{$m['upper']}</menu>\n";
    $xml.= "        <files folder=\"admin\">\n            <filename>$a.php</filename>\n            <filename>index.html</filename>\n            <folder>sql</folder>\n        </files>\n";
    $xml.= "    </administration>\n</extension>\n";
    file_put_contents("$d/$a.xml", $xml);

    // Admin PHP
    $c = fileHeader($p,'Joomla.Administrator',"com_$a");
    if (isJ3($p)) {
        $c.= "echo '<h1>' . JText::_('COM_{$m['upper']}') . '</h1>';\n";
        $c.= "echo '<p>Bienvenue dans l\\'administration de {$p['name']}</p>';\n";
    } else {
        $c.= "use Joomla\\CMS\\Language\\Text;\n\n";
        $c.= "echo '<h1>' . Text::_('COM_{$m['upper']}') . '</h1>';\n";
        $c.= "echo '<p>Bienvenue dans l\\'administration de {$p['name']}</p>';\n";
    }
    file_put_contents("$d/admin/$a.php", $c);

    // Site PHP
    $c = fileHeader($p,'Joomla.Site',"com_$a");
    $c.= "echo '<h1>{$p['name']}</h1>';\necho '<p>Bienvenue!</p>';\n";
    file_put_contents("$d/site/$a.php", $c);

    file_put_contents("$d/admin/sql/updates/mysql/{$p['version']}.sql","-- {$p['name']} v{$p['version']}\nSELECT 1;");
}

/* ─── COMPOSANT BASIC ─── */
function generateBasicComp($p,$d) {
    $a=$p['alias']; $m=meta($p);
    mkdirs(["$d","$d/admin","$d/admin/sql/updates/mysql","$d/site","$d/admin/language/en-GB","$d/site/language/en-GB"]);
    idx("$d/admin/index.html"); idx("$d/admin/sql/index.html");
    idx("$d/admin/sql/updates/index.html"); idx("$d/admin/sql/updates/mysql/index.html");
    idx("$d/site/index.html");

    $extAttr = isJ3($p) ? 'version="3.0" ' : '';
    $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<extension type=\"component\" {$extAttr}method=\"upgrade\">\n";
    $xml.= "    <name>{$p['name']}</name>\n    <author>{$p['author']}</author>\n    <creationDate>{$m['date']}</creationDate>\n";
    $xml.= "    <copyright>Copyright (C) {$m['year']} {$p['author']}</copyright>\n    <license>{$p['license']}</license>\n";
    $xml.= "    <authorEmail>{$p['authorEmail']}</authorEmail>\n    <authorUrl>{$p['authorUrl']}</authorUrl>\n";
    $xml.= "    <version>{$p['version']}</version>\n    <description>COM_{$m['upper']}_DESCRIPTION</description>\n\n";
    $xml.= "    <install><sql><file driver=\"mysql\" charset=\"utf8\">sql/updates/mysql/{$p['version']}.sql</file></sql></install>\n";
    $xml.= "    <update><schemas><schemapath type=\"mysql\">sql/updates/mysql</schemapath></schemas></update>\n\n";
    $xml.= "    <files folder=\"site\">\n        <filename>$a.php</filename>\n        <filename>index.html</filename>\n    </files>\n";
    $xml.= "    <languages folder=\"site/language\"><language tag=\"en-GB\">en-GB/com_$a.ini</language></languages>\n\n";
    $xml.= "    <administration>\n        <menu>COM_{$m['upper']}</menu>\n";
    $xml.= "        <files folder=\"admin\">\n            <filename>$a.php</filename>\n            <filename>index.html</filename>\n            <folder>sql</folder>\n        </files>\n";
    $xml.= "        <languages folder=\"admin/language\">\n            <language tag=\"en-GB\">en-GB/com_$a.ini</language>\n";
    $xml.= "            <language tag=\"en-GB\">en-GB/com_$a.sys.ini</language>\n        </languages>\n";
    $xml.= "    </administration>\n</extension>\n";
    file_put_contents("$d/$a.xml", $xml);

    $F = fct($p); $T = txt($p);

    // Admin
    $c = fileHeader($p,'Joomla.Administrator',"com_$a");
    if (isJ3($p)) {
        $c.= "\$app = JFactory::getApplication();\necho '<h1>' . JText::_('COM_{$m['upper']}') . '</h1>';\n";
        $c.= "echo '<div class=\"alert alert-info\">' . JText::_('COM_{$m['upper']}_WELCOME') . '</div>';\n";
    } else {
        $c.= "use Joomla\\CMS\\Factory;\nuse Joomla\\CMS\\Language\\Text;\n\n";
        $c.= "\$app = Factory::getApplication();\necho '<h1>' . Text::_('COM_{$m['upper']}') . '</h1>';\n";
        $c.= "echo '<div class=\"alert alert-info\">' . Text::_('COM_{$m['upper']}_WELCOME') . '</div>';\n";
    }
    file_put_contents("$d/admin/$a.php", $c);

    // Site
    $c = fileHeader($p,'Joomla.Site',"com_$a");
    if (!isJ3($p)) $c.= "use Joomla\\CMS\\Language\\Text;\n\n";
    $c.= "?>\n<div class=\"com-$a\">\n    <h1><?php echo {$T}::_('COM_{$m['upper']}'); ?></h1>\n    <p>Bienvenue!</p>\n</div>\n";
    file_put_contents("$d/site/$a.php", $c);

    file_put_contents("$d/admin/sql/updates/mysql/{$p['version']}.sql","-- {$p['name']} v{$p['version']}\nSELECT 1;");

    $ini = "COM_{$m['upper']}=\"{$p['name']}\"\nCOM_{$m['upper']}_DESCRIPTION=\"{$p['description']}\"\nCOM_{$m['upper']}_WELCOME=\"Bienvenue dans {$p['name']}\"\n";
    file_put_contents("$d/admin/language/en-GB/com_$a.ini", $ini);
    file_put_contents("$d/admin/language/en-GB/com_$a.sys.ini","COM_{$m['upper']}=\"{$p['name']}\"\nCOM_{$m['upper']}_DESCRIPTION=\"{$p['description']}\"\n");
    file_put_contents("$d/site/language/en-GB/com_$a.ini","COM_{$m['upper']}=\"{$p['name']}\"\n");
}

/* ─── COMPOSANT AVANCÉ J3 (Legacy MVC) ─── */
function generateAdvancedCompJ3($p,$d) {
    $a=$p['alias']; $m=meta($p); $uc=$m['uc'];

    mkdirs([
        "$d","$d/admin","$d/admin/controllers","$d/admin/models","$d/admin/views/items/tmpl",
        "$d/admin/tables","$d/admin/helpers","$d/admin/sql/updates/mysql","$d/admin/language/en-GB",
        "$d/site","$d/site/controllers","$d/site/models","$d/site/views/$a/tmpl","$d/site/language/en-GB",
    ]);

    // Manifest
    $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<extension type=\"component\" version=\"3.0\" method=\"upgrade\">\n";
    $xml.= "    <name>com_$a</name>\n    <author>{$p['author']}</author>\n    <creationDate>{$m['date']}</creationDate>\n";
    $xml.= "    <copyright>Copyright (C) {$m['year']} {$p['author']}</copyright>\n    <license>{$p['license']}</license>\n";
    $xml.= "    <authorEmail>{$p['authorEmail']}</authorEmail>\n    <authorUrl>{$p['authorUrl']}</authorUrl>\n";
    $xml.= "    <version>{$p['version']}</version>\n    <description>COM_{$m['upper']}_DESCRIPTION</description>\n\n";
    $xml.= "    <install><sql><file driver=\"mysql\" charset=\"utf8\">sql/install.mysql.sql</file></sql></install>\n";
    $xml.= "    <uninstall><sql><file driver=\"mysql\" charset=\"utf8\">sql/uninstall.mysql.sql</file></sql></uninstall>\n";
    $xml.= "    <update><schemas><schemapath type=\"mysql\">sql/updates/mysql</schemapath></schemas></update>\n\n";
    $xml.= "    <files folder=\"site\">\n        <filename>$a.php</filename>\n        <filename>controller.php</filename>\n";
    $xml.= "        <folder>controllers</folder>\n        <folder>models</folder>\n        <folder>views</folder>\n    </files>\n";
    $xml.= "    <languages folder=\"site/language\"><language tag=\"en-GB\">en-GB/com_$a.ini</language></languages>\n\n";
    $xml.= "    <administration>\n        <menu>COM_{$m['upper']}</menu>\n";
    $xml.= "        <files folder=\"admin\">\n            <filename>$a.php</filename>\n            <filename>controller.php</filename>\n";
    $xml.= "            <folder>controllers</folder>\n            <folder>helpers</folder>\n            <folder>models</folder>\n";
    $xml.= "            <folder>sql</folder>\n            <folder>tables</folder>\n            <folder>views</folder>\n        </files>\n";
    $xml.= "        <languages folder=\"admin/language\">\n            <language tag=\"en-GB\">en-GB/com_$a.ini</language>\n";
    $xml.= "            <language tag=\"en-GB\">en-GB/com_$a.sys.ini</language>\n        </languages>\n";
    $xml.= "    </administration>\n</extension>\n";
    file_put_contents("$d/$a.xml", $xml);

    // ── Admin entry ──
    $c = "<?php\ndefined('_JEXEC') or die;\n\n";
    $c.= "\$controller = JControllerLegacy::getInstance('{$uc}');\n";
    $c.= "\$controller->execute(JFactory::getApplication()->input->get('task'));\n";
    $c.= "\$controller->redirect();\n";
    file_put_contents("$d/admin/$a.php", $c);

    // ── Admin controller.php ──
    $c = "<?php\ndefined('_JEXEC') or die;\n\nclass {$uc}Controller extends JControllerLegacy\n{\n";
    $c.= "    protected \$default_view = 'items';\n}\n";
    file_put_contents("$d/admin/controller.php", $c);

    // ── Admin model ──
    $c = "<?php\ndefined('_JEXEC') or die;\n\njimport('joomla.application.component.modellist');\n\n";
    $c.= "class {$uc}ModelItems extends JModelList\n{\n";
    $c.= "    protected function getListQuery()\n    {\n";
    $c.= "        \$db = \$this->getDbo();\n        \$query = \$db->getQuery(true);\n";
    $c.= "        \$query->select('*')->from(\$db->quoteName('#__$a'));\n        return \$query;\n    }\n}\n";
    file_put_contents("$d/admin/models/items.php", $c);

    // ── Admin view ──
    $c = "<?php\ndefined('_JEXEC') or die;\n\njimport('joomla.application.component.view');\n\n";
    $c.= "class {$uc}ViewItems extends JViewLegacy\n{\n    protected \$items;\n\n";
    $c.= "    public function display(\$tpl = null)\n    {\n";
    $c.= "        \$this->items = \$this->get('Items');\n        \$this->addToolbar();\n";
    $c.= "        parent::display(\$tpl);\n    }\n\n";
    $c.= "    protected function addToolbar()\n    {\n";
    $c.= "        JToolbarHelper::title(JText::_('COM_{$m['upper']}'), 'generic');\n";
    $c.= "        JToolbarHelper::preferences('com_$a');\n    }\n}\n";
    file_put_contents("$d/admin/views/items/view.html.php", $c);

    // ── Admin template ──
    $c = "<?php\ndefined('_JEXEC') or die;\n?>\n<div class=\"com-$a-admin\">\n";
    $c.= "    <h2><?php echo JText::_('COM_{$m['upper']}'); ?></h2>\n";
    $c.= "    <?php if (!empty(\$this->items)) : ?>\n    <table class=\"table table-striped\">\n";
    $c.= "        <thead><tr><th>ID</th><th>Title</th><th>State</th></tr></thead><tbody>\n";
    $c.= "        <?php foreach (\$this->items as \$item) : ?>\n";
    $c.= "            <tr><td><?php echo \$item->id; ?></td><td><?php echo htmlspecialchars(\$item->title); ?></td><td><?php echo \$item->state; ?></td></tr>\n";
    $c.= "        <?php endforeach; ?></tbody></table>\n";
    $c.= "    <?php else : ?><div class=\"alert alert-info\"><?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></div><?php endif; ?>\n</div>\n";
    file_put_contents("$d/admin/views/items/tmpl/default.php", $c);

    // ── Admin table ──
    $c = "<?php\ndefined('_JEXEC') or die;\n\nclass {$uc}Table{$uc} extends JTable\n{\n";
    $c.= "    public function __construct(&\$db)\n    {\n        parent::__construct('#__$a', 'id', \$db);\n    }\n}\n";
    file_put_contents("$d/admin/tables/$a.php", $c);

    // ── Admin helper ──
    $c = "<?php\ndefined('_JEXEC') or die;\n\nclass {$uc}Helper\n{\n";
    $c.= "    public static function addSubmenu(\$vName)\n    {\n";
    $c.= "        JHtmlSidebar::addEntry(JText::_('COM_{$m['upper']}_ITEMS'), 'index.php?option=com_$a&view=items', \$vName == 'items');\n";
    $c.= "    }\n}\n";
    file_put_contents("$d/admin/helpers/$a.php", $c);

    // ── Site entry ──
    $c = "<?php\ndefined('_JEXEC') or die;\n\n\$controller = JControllerLegacy::getInstance('{$uc}');\n";
    $c.= "\$controller->execute(JFactory::getApplication()->input->get('task'));\n\$controller->redirect();\n";
    file_put_contents("$d/site/$a.php", $c);

    // ── Site controller ──
    $c = "<?php\ndefined('_JEXEC') or die;\n\nclass {$uc}Controller extends JControllerLegacy\n{\n";
    $c.= "    protected \$default_view = '$a';\n}\n";
    file_put_contents("$d/site/controller.php", $c);

    // ── Site view ──
    $c = "<?php\ndefined('_JEXEC') or die;\n\njimport('joomla.application.component.view');\n\n";
    $c.= "class {$uc}View{$uc} extends JViewLegacy\n{\n    protected \$message;\n\n";
    $c.= "    public function display(\$tpl = null)\n    {\n";
    $c.= "        \$this->message = '{$p['name']}';\n        parent::display(\$tpl);\n    }\n}\n";
    file_put_contents("$d/site/views/$a/view.html.php", $c);

    // ── Site template ──
    $c = "<?php\ndefined('_JEXEC') or die;\n?>\n<div class=\"com-$a\">\n    <h1><?php echo htmlspecialchars(\$this->message); ?></h1>\n</div>\n";
    file_put_contents("$d/site/views/$a/tmpl/default.php", $c);

    // ── SQL ──
    $sql = "CREATE TABLE IF NOT EXISTS `#__{$a}` (\n    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,\n    `title` VARCHAR(255) NOT NULL DEFAULT '',\n";
    $sql.= "    `alias` VARCHAR(400) NOT NULL DEFAULT '',\n    `state` TINYINT(1) NOT NULL DEFAULT 0,\n    `ordering` INT NOT NULL DEFAULT 0,\n";
    $sql.= "    `created` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',\n    `created_by` INT UNSIGNED NOT NULL DEFAULT 0,\n";
    $sql.= "    PRIMARY KEY (`id`),\n    KEY `idx_state` (`state`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n";
    file_put_contents("$d/admin/sql/install.mysql.sql", $sql);
    file_put_contents("$d/admin/sql/uninstall.mysql.sql","DROP TABLE IF EXISTS `#__{$a}`;\n");
    file_put_contents("$d/admin/sql/updates/mysql/{$p['version']}.sql","-- {$p['name']} {$p['version']}\n");

    // ── Language ──
    $ini = "COM_{$m['upper']}=\"{$p['name']}\"\nCOM_{$m['upper']}_DESCRIPTION=\"{$p['description']}\"\nCOM_{$m['upper']}_ITEMS=\"Items\"\n";
    file_put_contents("$d/admin/language/en-GB/com_$a.ini", $ini);
    file_put_contents("$d/admin/language/en-GB/com_$a.sys.ini","COM_{$m['upper']}=\"{$p['name']}\"\nCOM_{$m['upper']}_DESCRIPTION=\"{$p['description']}\"\n");
    file_put_contents("$d/site/language/en-GB/com_$a.ini","COM_{$m['upper']}=\"{$p['name']}\"\n");
}

/* ─── COMPOSANT AVANCÉ J4/5/6 (Modern MVC) ─── */
function generateAdvancedCompModern($p,$d) {
    $a=$p['alias']; $m=meta($p); $v=$p['vendor']; $uc=$m['uc'];
    $ns=$v.'\\Component\\'.$uc;
    $nsEsc='\\\\'.$v.'\\\\Component\\\\'.$uc;
    $retType = isJ5Plus($p) ? ': static' : '';

    mkdirs([
        "$d","$d/admin/services","$d/admin/src/Extension","$d/admin/src/Controller",
        "$d/admin/src/View/Items","$d/admin/src/Model","$d/admin/tmpl/items",
        "$d/admin/sql/updates/mysql","$d/admin/language/en-GB","$d/admin/forms",
        "$d/site/src/Controller","$d/site/src/View/Default","$d/site/tmpl/default",
        "$d/site/language/en-GB","$d/media/css","$d/media/js",
    ]);

    // Manifest
    $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<extension type=\"component\" method=\"upgrade\">\n";
    $xml.= "    <name>com_$a</name>\n    <author>{$p['author']}</author>\n    <creationDate>{$m['date']}</creationDate>\n";
    $xml.= "    <copyright>Copyright (C) {$m['year']} {$p['author']}</copyright>\n    <license>{$p['license']}</license>\n";
    $xml.= "    <authorEmail>{$p['authorEmail']}</authorEmail>\n    <authorUrl>{$p['authorUrl']}</authorUrl>\n";
    $xml.= "    <version>{$p['version']}</version>\n    <description>COM_{$m['upper']}_DESCRIPTION</description>\n";
    $xml.= "    <namespace path=\"src\">$ns</namespace>\n";
    $xml.= "    <scriptfile>script.php</scriptfile>\n";
    $xml.= "    <install><sql><file driver=\"mysql\" charset=\"utf8\">sql/install.mysql.sql</file></sql></install>\n";
    $xml.= "    <uninstall><sql><file driver=\"mysql\" charset=\"utf8\">sql/uninstall.mysql.sql</file></sql></uninstall>\n";
    $xml.= "    <update><schemas><schemapath type=\"mysql\">sql/updates/mysql</schemapath></schemas></update>\n";
    $xml.= "    <media destination=\"com_$a\" folder=\"media\"><folder>css</folder><folder>js</folder></media>\n";
    $xml.= "    <files folder=\"site\"><folder>src</folder><folder>tmpl</folder></files>\n";
    $xml.= "    <languages folder=\"site/language\"><language tag=\"en-GB\">en-GB/com_$a.ini</language></languages>\n";
    $xml.= "    <administration>\n        <menu>COM_{$m['upper']}</menu>\n";
    $xml.= "        <submenu><menu link=\"option=com_$a\">COM_{$m['upper']}_ITEMS</menu></submenu>\n";
    $xml.= "        <files folder=\"admin\"><folder>forms</folder><folder>services</folder><folder>src</folder><folder>tmpl</folder><folder>sql</folder></files>\n";
    $xml.= "        <languages folder=\"admin/language\">\n            <language tag=\"en-GB\">en-GB/com_$a.ini</language>\n";
    $xml.= "            <language tag=\"en-GB\">en-GB/com_$a.sys.ini</language>\n        </languages>\n";
    $xml.= "    </administration>\n</extension>\n";
    file_put_contents("$d/$a.xml", $xml);

    // script.php
    $phpMin = phpMin($p);
    $c = "<?php\ndefined('_JEXEC') or die;\n\nuse Joomla\\CMS\\Installer\\InstallerAdapter;\nuse Joomla\\CMS\\Log\\Log;\n\n";
    $c.= "class Com_{$uc}InstallerScript\n{\n";
    $c.= "    public function preflight(string \$route, InstallerAdapter \$adapter): bool\n    {\n";
    $c.= "        if (version_compare(PHP_VERSION, '$phpMin', '<')) {\n            Log::add('PHP $phpMin+ requis', Log::WARNING, 'jerror');\n            return false;\n        }\n        return true;\n    }\n";
    $c.= "    public function install(InstallerAdapter \$adapter): bool { return true; }\n";
    $c.= "    public function update(InstallerAdapter \$adapter): bool { return true; }\n";
    $c.= "    public function uninstall(InstallerAdapter \$adapter): bool { return true; }\n";
    $c.= "    public function postflight(string \$route, InstallerAdapter \$adapter): bool { return true; }\n}\n";
    file_put_contents("$d/script.php", $c);

    // services/provider.php
    $c = "<?php\ndefined('_JEXEC') or die;\n\n";
    $c.= "use Joomla\\CMS\\Dispatcher\\ComponentDispatcherFactoryInterface;\nuse Joomla\\CMS\\Extension\\ComponentInterface;\n";
    $c.= "use Joomla\\CMS\\Extension\\Service\\Provider\\ComponentDispatcherFactory;\nuse Joomla\\CMS\\Extension\\Service\\Provider\\MVCFactory;\n";
    $c.= "use Joomla\\CMS\\MVC\\Factory\\MVCFactoryInterface;\nuse Joomla\\DI\\Container;\nuse Joomla\\DI\\ServiceProviderInterface;\n";
    $c.= "use $ns\\Administrator\\Extension\\{$uc}Component;\n\n";
    $c.= "return new class implements ServiceProviderInterface {\n    public function register(Container \$container): void\n    {\n";
    $c.= "        \$container->registerServiceProvider(new MVCFactory('$nsEsc'));\n";
    $c.= "        \$container->registerServiceProvider(new ComponentDispatcherFactory('$nsEsc'));\n";
    $c.= "        \$container->set(ComponentInterface::class, function (Container \$container) {\n";
    $c.= "            \$component = new {$uc}Component(\$container->get(ComponentDispatcherFactoryInterface::class));\n";
    $c.= "            \$component->setMVCFactory(\$container->get(MVCFactoryInterface::class));\n";
    $c.= "            return \$component;\n        });\n    }\n};\n";
    file_put_contents("$d/admin/services/provider.php", $c);

    // Extension
    $c = "<?php\nnamespace $ns\\Administrator\\Extension;\n\ndefined('_JEXEC') or die;\n\n";
    $c.= "use Joomla\\CMS\\Extension\\BootableExtensionInterface;\nuse Joomla\\CMS\\Extension\\MVCComponent;\nuse Psr\\Container\\ContainerInterface;\n\n";
    $c.= "class {$uc}Component extends MVCComponent implements BootableExtensionInterface\n{\n    public function boot(ContainerInterface \$container): void {}\n}\n";
    file_put_contents("$d/admin/src/Extension/{$uc}Component.php", $c);

    // Admin Controller
    $c = "<?php\nnamespace $ns\\Administrator\\Controller;\n\ndefined('_JEXEC') or die;\n\nuse Joomla\\CMS\\MVC\\Controller\\BaseController;\n\n";
    $c.= "class DisplayController extends BaseController\n{\n    protected \$default_view = 'items';\n\n";
    $c.= "    public function display(\$cachable = false, \$urlparams = [])$retType\n    { return parent::display(\$cachable, \$urlparams); }\n}\n";
    file_put_contents("$d/admin/src/Controller/DisplayController.php", $c);

    // Admin Model
    $c = "<?php\nnamespace $ns\\Administrator\\Model;\n\ndefined('_JEXEC') or die;\n\nuse Joomla\\CMS\\MVC\\Model\\ListModel;\n";
    $c.= "use Joomla\\Database\\QueryInterface;\n\n";
    $c.= "class ItemsModel extends ListModel\n{\n    protected function getListQuery(): QueryInterface\n    {\n";
    $c.= "        \$db = \$this->getDatabase();\n        return \$db->getQuery(true)->select('*')->from(\$db->quoteName('#__$a'));\n    }\n}\n";
    file_put_contents("$d/admin/src/Model/ItemsModel.php", $c);

    // Admin View
    $c = "<?php\nnamespace $ns\\Administrator\\View\\Items;\n\ndefined('_JEXEC') or die;\n\n";
    $c.= "use Joomla\\CMS\\MVC\\View\\HtmlView as BaseHtmlView;\nuse Joomla\\CMS\\Toolbar\\ToolbarHelper;\nuse Joomla\\CMS\\Language\\Text;\n\n";
    $c.= "class HtmlView extends BaseHtmlView\n{\n    protected \$items;\n\n";
    $c.= "    public function display(\$tpl = null): void\n    {\n        \$this->items = \$this->get('Items');\n        \$this->addToolbar();\n        parent::display(\$tpl);\n    }\n\n";
    $c.= "    protected function addToolbar(): void\n    {\n        ToolbarHelper::title(Text::_('COM_{$m['upper']}'), 'generic');\n        ToolbarHelper::preferences('com_$a');\n    }\n}\n";
    file_put_contents("$d/admin/src/View/Items/HtmlView.php", $c);

    // Admin tmpl
    $c = "<?php\ndefined('_JEXEC') or die;\nuse Joomla\\CMS\\Language\\Text;\n?>\n<div class=\"com-$a-admin\">\n";
    $c.= "    <h2><?php echo Text::_('COM_{$m['upper']}'); ?></h2>\n";
    $c.= "    <?php if (!empty(\$this->items)) : ?>\n    <table class=\"table table-striped\"><thead><tr><th>ID</th><th>Title</th><th>State</th></tr></thead><tbody>\n";
    $c.= "    <?php foreach (\$this->items as \$item) : ?><tr><td><?php echo \$item->id; ?></td><td><?php echo htmlspecialchars(\$item->title ?? ''); ?></td><td><?php echo \$item->state; ?></td></tr>\n";
    $c.= "    <?php endforeach; ?></tbody></table>\n    <?php else : ?><div class=\"alert alert-info\"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></div><?php endif; ?>\n</div>\n";
    file_put_contents("$d/admin/tmpl/items/default.php", $c);

    // Site Controller
    $c = "<?php\nnamespace $ns\\Site\\Controller;\n\ndefined('_JEXEC') or die;\n\nuse Joomla\\CMS\\MVC\\Controller\\BaseController;\n\n";
    $c.= "class DisplayController extends BaseController\n{\n    protected \$default_view = 'default';\n\n";
    $c.= "    public function display(\$cachable = false, \$urlparams = [])$retType\n    { return parent::display(\$cachable, \$urlparams); }\n}\n";
    file_put_contents("$d/site/src/Controller/DisplayController.php", $c);

    // Site View
    $c = "<?php\nnamespace $ns\\Site\\View\\Default;\n\ndefined('_JEXEC') or die;\n\nuse Joomla\\CMS\\MVC\\View\\HtmlView as BaseHtmlView;\n\n";
    $c.= "class HtmlView extends BaseHtmlView\n{\n    protected string \$message = '';\n\n";
    $c.= "    public function display(\$tpl = null): void\n    {\n        \$this->message = '{$p['name']}';\n        parent::display(\$tpl);\n    }\n}\n";
    file_put_contents("$d/site/src/View/Default/HtmlView.php", $c);

    // Site tmpl
    $c = "<?php\ndefined('_JEXEC') or die;\n?>\n<div class=\"com-$a\"><h1><?php echo htmlspecialchars(\$this->message); ?></h1></div>\n";
    file_put_contents("$d/site/tmpl/default/default.php", $c);

    // SQL
    $sql = "CREATE TABLE IF NOT EXISTS `#__{$a}` (\n    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,\n    `title` VARCHAR(255) NOT NULL DEFAULT '',\n";
    $sql.= "    `alias` VARCHAR(400) NOT NULL DEFAULT '',\n    `state` TINYINT(1) NOT NULL DEFAULT 0,\n    `ordering` INT NOT NULL DEFAULT 0,\n";
    $sql.= "    `created` DATETIME NOT NULL,\n    `modified` DATETIME NOT NULL,\n    `created_by` INT UNSIGNED NOT NULL DEFAULT 0,\n";
    $sql.= "    PRIMARY KEY (`id`), KEY `idx_state` (`state`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n";
    file_put_contents("$d/admin/sql/install.mysql.sql",$sql);
    file_put_contents("$d/admin/sql/uninstall.mysql.sql","DROP TABLE IF EXISTS `#__{$a}`;\n");
    file_put_contents("$d/admin/sql/updates/mysql/{$p['version']}.sql","-- {$p['name']} {$p['version']}\n");

    // Forms, Access, Config
    file_put_contents("$d/admin/forms/filter_items.xml","<?xml version=\"1.0\"?>\n<form><fields name=\"filter\"><field name=\"search\" type=\"text\" label=\"JSEARCH_FILTER\" hint=\"JSEARCH_FILTER\"/></fields></form>\n");
    $acc = "<?xml version=\"1.0\"?>\n<access component=\"com_$a\"><section name=\"component\">\n";
    foreach (['core.admin'=>'JACTION_ADMIN','core.manage'=>'JACTION_MANAGE','core.create'=>'JACTION_CREATE','core.delete'=>'JACTION_DELETE','core.edit'=>'JACTION_EDIT','core.edit.state'=>'JACTION_EDITSTATE'] as $k=>$v)
        $acc.= "    <action name=\"$k\" title=\"$v\"/>\n";
    $acc.= "</section></access>\n";
    file_put_contents("$d/admin/access.xml",$acc);
    file_put_contents("$d/admin/config.xml","<?xml version=\"1.0\"?>\n<config><fieldset name=\"component\" label=\"COM_{$m['upper']}_CONFIG\">\n    <field name=\"show_title\" type=\"radio\" label=\"Show Title\" layout=\"joomla.form.field.radio.switcher\" default=\"1\"><option value=\"0\">JNO</option><option value=\"1\">JYES</option></field>\n</fieldset></config>\n");

    // Language
    $ini = "COM_{$m['upper']}=\"{$p['name']}\"\nCOM_{$m['upper']}_DESCRIPTION=\"{$p['description']}\"\nCOM_{$m['upper']}_ITEMS=\"Items\"\nCOM_{$m['upper']}_CONFIG=\"Configuration\"\n";
    file_put_contents("$d/admin/language/en-GB/com_$a.ini",$ini);
    file_put_contents("$d/admin/language/en-GB/com_$a.sys.ini","COM_{$m['upper']}=\"{$p['name']}\"\nCOM_{$m['upper']}_DESCRIPTION=\"{$p['description']}\"\n");
    file_put_contents("$d/site/language/en-GB/com_$a.ini","COM_{$m['upper']}=\"{$p['name']}\"\n");

    // Media
    file_put_contents("$d/media/css/style.css",".com-$a{padding:1rem}\n");
    file_put_contents("$d/media/js/script.js","document.addEventListener('DOMContentLoaded',()=>{console.log('com_$a')});\n");
    $wa = "{\"name\":\"com_$a\",\"version\":\"{$p['version']}\",\"assets\":[{\"name\":\"com_$a.style\",\"type\":\"style\",\"uri\":\"com_$a/css/style.css\"}]}\n";
    file_put_contents("$d/media/joomla.asset.json",$wa);
}

/* ═══════════════════ MODULE ═══════════════════ */

function generateModule($p) {
    $d = "mod_{$p['alias']}";
    switch ($p['structureType']) {
        case 'simple':  generateSimpleMod($p,$d); break;
        case 'basic':   generateBasicMod($p,$d); break;
        default:        isJ3($p) ? generateAdvancedModJ3($p,$d) : generateAdvancedModModern($p,$d); break;
    }
    $t=['simple'=>'Simple','basic'=>'Basic','advanced'=>'Avancé'];
    zipAndFinish($d,"Module {$t[$p['structureType']]} <strong>$d</strong> (Joomla ".jVer($p).") généré");
}

function generateSimpleMod($p,$d) {
    $a=$p['alias']; $m=meta($p);
    mkdirs(["$d","$d/tmpl"]); idx("$d/index.html"); idx("$d/tmpl/index.html");

    $extAttr = isJ3($p) ? 'version="3.0" ' : '';
    $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<extension type=\"module\" client=\"{$p['moduleClient']}\" {$extAttr}method=\"upgrade\">\n";
    $xml.= "    <name>mod_$a</name>\n    <author>{$p['author']}</author>\n    <creationDate>{$m['date']}</creationDate>\n";
    $xml.= "    <version>{$p['version']}</version>\n    <description>{$p['description']}</description>\n";
    $xml.= "    <files>\n        <filename module=\"mod_$a\">mod_$a.php</filename>\n        <filename>index.html</filename>\n        <folder>tmpl</folder>\n    </files>\n";
    $xml.= "    <config><fields name=\"params\"><fieldset name=\"basic\">\n";
    $xml.= "        <field name=\"text\" type=\"text\" label=\"Texte\" default=\"{$p['name']}\"/>\n";
    $xml.= "    </fieldset></fields></config>\n</extension>\n";
    file_put_contents("$d/mod_$a.xml",$xml);

    $hlp = isJ3($p) ? 'JModuleHelper' : 'ModuleHelper';
    $c = "<?php\ndefined('_JEXEC') or die;\n\n";
    if (!isJ3($p)) $c.= "use Joomla\\CMS\\Helper\\ModuleHelper;\n\n";
    $c.= "\$text = \$params->get('text', '{$p['name']}');\nrequire {$hlp}::getLayoutPath('mod_$a', \$params->get('layout', 'default'));\n";
    file_put_contents("$d/mod_$a.php",$c);

    $c = "<?php\ndefined('_JEXEC') or die;\n?>\n<div class=\"mod-$a\"><p><?php echo htmlspecialchars(\$text); ?></p></div>\n";
    file_put_contents("$d/tmpl/default.php",$c);
}

function generateBasicMod($p,$d) {
    $a=$p['alias']; $m=meta($p);
    mkdirs(["$d","$d/tmpl","$d/language/en-GB"]); idx("$d/index.html"); idx("$d/tmpl/index.html");

    $extAttr = isJ3($p) ? 'version="3.0" ' : '';
    $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<extension type=\"module\" client=\"{$p['moduleClient']}\" {$extAttr}method=\"upgrade\">\n";
    $xml.= "    <name>mod_$a</name>\n    <author>{$p['author']}</author>\n    <creationDate>{$m['date']}</creationDate>\n";
    $xml.= "    <version>{$p['version']}</version>\n    <description>MOD_{$m['upper']}_DESCRIPTION</description>\n";
    $xml.= "    <files>\n        <filename module=\"mod_$a\">mod_$a.php</filename>\n        <filename>helper.php</filename>\n        <filename>index.html</filename>\n        <folder>tmpl</folder>\n    </files>\n";
    $xml.= "    <languages folder=\"language\"><language tag=\"en-GB\">en-GB/mod_$a.ini</language><language tag=\"en-GB\">en-GB/mod_$a.sys.ini</language></languages>\n";
    $xml.= "    <config><fields name=\"params\"><fieldset name=\"basic\">\n";
    $xml.= "        <field name=\"display_text\" type=\"text\" label=\"MOD_{$m['upper']}_TEXT\" default=\"{$p['name']}\"/>\n";
    $xml.= "    </fieldset></fields></config>\n</extension>\n";
    file_put_contents("$d/mod_$a.xml",$xml);

    $hlp = isJ3($p) ? 'JModuleHelper' : 'ModuleHelper';
    $fct = isJ3($p) ? 'JFactory' : 'Factory';
    $c = "<?php\ndefined('_JEXEC') or die;\n\n";
    if (!isJ3($p)) $c.= "use Joomla\\CMS\\Helper\\ModuleHelper;\n\n";
    $c.= "require_once __DIR__ . '/helper.php';\n\$helper = new Mod{$m['uc']}Helper(\$params);\n\$displayText = \$helper->getMessage();\n";
    $c.= "require {$hlp}::getLayoutPath('mod_$a', \$params->get('layout', 'default'));\n";
    file_put_contents("$d/mod_$a.php",$c);

    $c = "<?php\ndefined('_JEXEC') or die;\n\n";
    if (!isJ3($p)) $c.= "use Joomla\\CMS\\Factory;\n\n";
    $c.= "class Mod{$m['uc']}Helper\n{\n    protected \$params;\n    public function __construct(\$params) { \$this->params = \$params; }\n";
    $c.= "    public function getMessage() { return \$this->params->get('display_text', '{$p['name']}'); }\n";
    $c.= "    public function getDate() { return $fct::getDate()->format('d/m/Y H:i'); }\n}\n";
    file_put_contents("$d/helper.php",$c);

    $c = "<?php\ndefined('_JEXEC') or die;\n?>\n<div class=\"mod-$a\">\n    <p><?php echo htmlspecialchars(\$displayText); ?></p>\n    <small><?php echo \$helper->getDate(); ?></small>\n</div>\n";
    file_put_contents("$d/tmpl/default.php",$c);

    file_put_contents("$d/language/en-GB/mod_$a.ini","MOD_{$m['upper']}_TEXT=\"Texte à afficher\"\n");
    file_put_contents("$d/language/en-GB/mod_$a.sys.ini","MOD_{$m['upper']}=\"{$p['name']}\"\nMOD_{$m['upper']}_DESCRIPTION=\"{$p['description']}\"\n");
}

function generateAdvancedModJ3($p,$d) {
    $a=$p['alias']; $m=meta($p);
    mkdirs(["$d","$d/tmpl","$d/css","$d/language/en-GB"]); idx("$d/index.html");

    $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<extension type=\"module\" client=\"{$p['moduleClient']}\" version=\"3.0\" method=\"upgrade\">\n";
    $xml.= "    <name>mod_$a</name>\n    <author>{$p['author']}</author>\n    <creationDate>{$m['date']}</creationDate>\n";
    $xml.= "    <version>{$p['version']}</version>\n    <description>MOD_{$m['upper']}_DESCRIPTION</description>\n";
    $xml.= "    <files>\n        <filename module=\"mod_$a\">mod_$a.php</filename>\n        <filename>helper.php</filename>\n";
    $xml.= "        <folder>tmpl</folder>\n        <folder>css</folder>\n    </files>\n";
    $xml.= "    <languages folder=\"language\"><language tag=\"en-GB\">en-GB/mod_$a.ini</language><language tag=\"en-GB\">en-GB/mod_$a.sys.ini</language></languages>\n";
    $xml.= "    <config><fields name=\"params\"><fieldset name=\"basic\">\n";
    $xml.= "        <field name=\"display_text\" type=\"text\" label=\"MOD_{$m['upper']}_TEXT\" default=\"{$p['name']}\"/>\n";
    $xml.= "        <field name=\"items_count\" type=\"text\" label=\"MOD_{$m['upper']}_COUNT\" default=\"5\"/>\n";
    $xml.= "    </fieldset><fieldset name=\"advanced\">\n        <field name=\"layout\" type=\"modulelayout\" label=\"JFIELD_ALT_LAYOUT_LABEL\"/>\n";
    $xml.= "        <field name=\"moduleclass_sfx\" type=\"textarea\" label=\"COM_MODULES_FIELD_MODULECLASS_SFX_LABEL\" rows=\"3\"/>\n";
    $xml.= "    </fieldset></fields></config>\n</extension>\n";
    file_put_contents("$d/mod_$a.xml",$xml);

    $c = "<?php\ndefined('_JEXEC') or die;\n\nrequire_once __DIR__ . '/helper.php';\n\n";
    $c.= "\$items = Mod{$m['uc']}Helper::getItems(\$params);\n\$message = Mod{$m['uc']}Helper::getMessage(\$params);\n\n";
    $c.= "\$document = JFactory::getDocument();\n\$document->addStyleSheet(JURI::base(true) . '/modules/mod_$a/css/style.css');\n\n";
    $c.= "require JModuleHelper::getLayoutPath('mod_$a', \$params->get('layout', 'default'));\n";
    file_put_contents("$d/mod_$a.php",$c);

    $c = "<?php\ndefined('_JEXEC') or die;\n\nclass Mod{$m['uc']}Helper\n{\n";
    $c.= "    public static function getMessage(\$params)\n    {\n        return \$params->get('display_text', '{$p['name']}');\n    }\n\n";
    $c.= "    public static function getItems(\$params)\n    {\n        \$count = (int) \$params->get('items_count', 5);\n";
    $c.= "        \$db = JFactory::getDbo();\n        // TODO: requête réelle\n";
    $c.= "        \$items = array();\n        for (\$i = 1; \$i <= \$count; \$i++) {\n            \$obj = new stdClass();\n            \$obj->id = \$i;\n            \$obj->title = 'Item ' . \$i;\n            \$items[] = \$obj;\n        }\n        return \$items;\n    }\n}\n";
    file_put_contents("$d/helper.php",$c);

    $c = "<?php\ndefined('_JEXEC') or die;\n?>\n<div class=\"mod-$a <?php echo \$params->get('moduleclass_sfx',''); ?>\">\n";
    $c.= "    <p><strong><?php echo htmlspecialchars(\$message); ?></strong></p>\n";
    $c.= "    <?php if (!empty(\$items)) : ?><ul>\n    <?php foreach (\$items as \$item) : ?><li><?php echo htmlspecialchars(\$item->title); ?></li><?php endforeach; ?>\n    </ul><?php endif; ?>\n</div>\n";
    file_put_contents("$d/tmpl/default.php",$c);

    file_put_contents("$d/css/style.css",".mod-$a { padding: .5rem; }\n");
    file_put_contents("$d/language/en-GB/mod_$a.ini","MOD_{$m['upper']}_TEXT=\"Texte\"\nMOD_{$m['upper']}_COUNT=\"Nombre d'éléments\"\n");
    file_put_contents("$d/language/en-GB/mod_$a.sys.ini","MOD_{$m['upper']}=\"{$p['name']}\"\nMOD_{$m['upper']}_DESCRIPTION=\"{$p['description']}\"\n");
}

function generateAdvancedModModern($p,$d) {
    $a=$p['alias']; $m=meta($p); $v=$p['vendor']; $uc=$m['uc'];
    $ns=$v.'\\Module\\'.$uc;
    $nsEsc='\\\\'.$v.'\\\\Module\\\\'.$uc;
    $cl = ($p['moduleClient']==='administrator') ? 'Administrator' : 'Site';

    mkdirs(["$d","$d/services","$d/src/Dispatcher","$d/src/Helper","$d/tmpl","$d/language/en-GB"]);

    $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<extension type=\"module\" client=\"{$p['moduleClient']}\" method=\"upgrade\">\n";
    $xml.= "    <name>mod_$a</name>\n    <author>{$p['author']}</author>\n    <creationDate>{$m['date']}</creationDate>\n";
    $xml.= "    <version>{$p['version']}</version>\n    <description>MOD_{$m['upper']}_DESCRIPTION</description>\n";
    $xml.= "    <namespace path=\"src\">$ns</namespace>\n";
    $xml.= "    <files>\n        <folder module=\"mod_$a\">services</folder>\n        <folder>src</folder>\n        <folder>tmpl</folder>\n    </files>\n";
    $xml.= "    <languages folder=\"language\"><language tag=\"en-GB\">en-GB/mod_$a.ini</language><language tag=\"en-GB\">en-GB/mod_$a.sys.ini</language></languages>\n";
    $xml.= "    <config><fields name=\"params\"><fieldset name=\"basic\">\n";
    $xml.= "        <field name=\"display_text\" type=\"text\" label=\"MOD_{$m['upper']}_TEXT\" default=\"{$p['name']}\"/>\n";
    $xml.= "        <field name=\"items_count\" type=\"number\" label=\"MOD_{$m['upper']}_COUNT\" default=\"5\" min=\"1\" max=\"100\"/>\n";
    $xml.= "    </fieldset></fields></config>\n</extension>\n";
    file_put_contents("$d/mod_$a.xml",$xml);

    // services/provider.php
    $c = "<?php\ndefined('_JEXEC') or die;\n\nuse Joomla\\CMS\\Extension\\Service\\Provider\\HelperFactory;\nuse Joomla\\CMS\\Extension\\Service\\Provider\\Module;\n";
    $c.= "use Joomla\\CMS\\Extension\\Service\\Provider\\ModuleDispatcherFactory;\nuse Joomla\\DI\\Container;\nuse Joomla\\DI\\ServiceProviderInterface;\n\n";
    $c.= "return new class implements ServiceProviderInterface {\n    public function register(Container \$container): void\n    {\n";
    $c.= "        \$container->registerServiceProvider(new ModuleDispatcherFactory('$nsEsc'));\n";
    $c.= "        \$container->registerServiceProvider(new HelperFactory('$nsEsc\\\\$cl\\\\Helper'));\n";
    $c.= "        \$container->registerServiceProvider(new Module());\n    }\n};\n";
    file_put_contents("$d/services/provider.php",$c);

    // Dispatcher
    $c = "<?php\nnamespace $ns\\$cl\\Dispatcher;\n\ndefined('_JEXEC') or die;\n\n";
    $c.= "use Joomla\\CMS\\Dispatcher\\AbstractModuleDispatcher;\nuse Joomla\\CMS\\Helper\\HelperFactoryAwareInterface;\nuse Joomla\\CMS\\Helper\\HelperFactoryAwareTrait;\n\n";
    $c.= "class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface\n{\n    use HelperFactoryAwareTrait;\n\n";
    $c.= "    protected function getLayoutData(): array\n    {\n        \$data = parent::getLayoutData();\n";
    $c.= "        \$helper = \$this->getHelperFactory()->getHelper('{$uc}Helper');\n";
    $c.= "        \$data['message'] = \$helper->getMessage(\$data['params']);\n        \$data['items'] = \$helper->getItems(\$data['params']);\n        return \$data;\n    }\n}\n";
    file_put_contents("$d/src/Dispatcher/Dispatcher.php",$c);

    // Helper
    $c = "<?php\nnamespace $ns\\$cl\\Helper;\n\ndefined('_JEXEC') or die;\n\nuse Joomla\\Registry\\Registry;\n\n";
    $c.= "class {$uc}Helper\n{\n";
    $c.= "    public function getMessage(Registry \$params): string { return \$params->get('display_text', '{$p['name']}'); }\n\n";
    $c.= "    public function getItems(Registry \$params): array\n    {\n        \$count = (int) \$params->get('items_count', 5);\n";
    $c.= "        return array_map(fn(\$i) => (object)['id'=>\$i,'title'=>'Item '.\$i], range(1, \$count));\n    }\n}\n";
    file_put_contents("$d/src/Helper/{$uc}Helper.php",$c);

    // tmpl
    $c = "<?php\ndefined('_JEXEC') or die;\n\$message=\$message??'';\$items=\$items??[];\n?>\n";
    $c.= "<div class=\"mod-$a\">\n    <p><strong><?php echo htmlspecialchars(\$message); ?></strong></p>\n";
    $c.= "    <?php if (\$items) : ?><ul><?php foreach (\$items as \$i) : ?><li><?php echo htmlspecialchars(\$i->title); ?></li><?php endforeach; ?></ul><?php endif; ?>\n</div>\n";
    file_put_contents("$d/tmpl/default.php",$c);

    file_put_contents("$d/language/en-GB/mod_$a.ini","MOD_{$m['upper']}_TEXT=\"Texte\"\nMOD_{$m['upper']}_COUNT=\"Nombre\"\n");
    file_put_contents("$d/language/en-GB/mod_$a.sys.ini","MOD_{$m['upper']}=\"{$p['name']}\"\nMOD_{$m['upper']}_DESCRIPTION=\"{$p['description']}\"\n");
}

/* ═══════════════════ PLUGIN ═══════════════════ */

function generatePlugin($p) {
    $d = "plg_{$p['pluginGroup']}_{$p['alias']}";
    switch ($p['structureType']) {
        case 'simple':  generateSimplePlg($p,$d); break;
        case 'basic':   generateBasicPlg($p,$d); break;
        default:        isJ3($p) ? generateAdvancedPlgJ3($p,$d) : generateAdvancedPlgModern($p,$d); break;
    }
    $t=['simple'=>'Simple','basic'=>'Basic','advanced'=>'Avancé'];
    zipAndFinish($d,"Plugin {$t[$p['structureType']]} <strong>$d</strong> ({$p['pluginGroup']}, Joomla ".jVer($p).") généré");
}

function generateSimplePlg($p,$d) {
    $a=$p['alias']; $g=$p['pluginGroup']; $m=meta($p);
    $ucG=ucfirst($g); $ucA=$m['uc']; $ev=pluginEvents($g);
    mkdirs([$d]); idx("$d/index.html");

    $extAttr = isJ3($p) ? 'version="3.0" ' : '';
    $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<extension type=\"plugin\" group=\"$g\" {$extAttr}method=\"upgrade\">\n";
    $xml.= "    <name>plg_{$g}_$a</name>\n    <author>{$p['author']}</author>\n    <creationDate>{$m['date']}</creationDate>\n";
    $xml.= "    <version>{$p['version']}</version>\n    <description>{$p['description']}</description>\n";
    $xml.= "    <files>\n        <filename plugin=\"$a\">$a.php</filename>\n        <filename>index.html</filename>\n    </files>\n</extension>\n";
    file_put_contents("$d/$a.xml",$xml);

    $base = isJ3($p) ? 'JPlugin' : 'CMSPlugin';
    $c = "<?php\ndefined('_JEXEC') or die;\n\n";
    if (!isJ3($p)) $c.= "use Joomla\\CMS\\Plugin\\CMSPlugin;\n\n";
    $c.= "class Plg{$ucG}{$ucA} extends $base\n{\n";
    $c.= "    public function {$ev[0]}()\n    {\n        // TODO\n        return true;\n    }\n}\n";
    file_put_contents("$d/$a.php",$c);
}

function generateBasicPlg($p,$d) {
    $a=$p['alias']; $g=$p['pluginGroup']; $m=meta($p);
    $ucG=ucfirst($g); $ucA=$m['uc']; $events=pluginEvents($g);
    mkdirs(["$d","$d/language/en-GB"]); idx("$d/index.html");

    $extAttr = isJ3($p) ? 'version="3.0" ' : '';
    $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<extension type=\"plugin\" group=\"$g\" {$extAttr}method=\"upgrade\">\n";
    $xml.= "    <name>plg_{$g}_$a</name>\n    <author>{$p['author']}</author>\n    <creationDate>{$m['date']}</creationDate>\n";
    $xml.= "    <version>{$p['version']}</version>\n    <description>PLG_{$m['upper']}_DESCRIPTION</description>\n";
    $xml.= "    <files>\n        <filename plugin=\"$a\">$a.php</filename>\n        <filename>index.html</filename>\n    </files>\n";
    $xml.= "    <languages folder=\"language\"><language tag=\"en-GB\">en-GB/plg_{$g}_$a.ini</language><language tag=\"en-GB\">en-GB/plg_{$g}_$a.sys.ini</language></languages>\n";
    $xml.= "    <config><fields name=\"params\"><fieldset name=\"basic\">\n";
    $xml.= "        <field name=\"enabled_feature\" type=\"radio\" label=\"PLG_{$m['upper']}_ENABLE\" layout=\"joomla.form.field.radio.switcher\" default=\"1\"><option value=\"0\">JNO</option><option value=\"1\">JYES</option></field>\n";
    $xml.= "    </fieldset></fields></config>\n</extension>\n";
    file_put_contents("$d/$a.xml",$xml);

    $base = isJ3($p) ? 'JPlugin' : 'CMSPlugin';
    $c = "<?php\ndefined('_JEXEC') or die;\n\n";
    if (!isJ3($p)) $c.= "use Joomla\\CMS\\Plugin\\CMSPlugin;\n\n";
    $c.= "class Plg{$ucG}{$ucA} extends $base\n{\n    protected \$autoloadLanguage = true;\n\n";
    foreach ($events as $ev) {
        $c.= "    public function $ev()\n    {\n        if (!\$this->params->get('enabled_feature', 1)) return true;\n        // TODO: $ev\n        return true;\n    }\n\n";
    }
    $c = rtrim($c)."\n}\n";
    file_put_contents("$d/$a.php",$c);

    file_put_contents("$d/language/en-GB/plg_{$g}_$a.ini","PLG_{$m['upper']}_ENABLE=\"Activer\"\n");
    file_put_contents("$d/language/en-GB/plg_{$g}_$a.sys.ini","PLG_".strtoupper($g.'_'.$a)."=\"{$p['name']}\"\nPLG_{$m['upper']}_DESCRIPTION=\"{$p['description']}\"\n");
}

function generateAdvancedPlgJ3($p,$d) {
    // J3 avancé = même que basic mais avec plus de structure
    $a=$p['alias']; $g=$p['pluginGroup']; $m=meta($p);
    $ucG=ucfirst($g); $ucA=$m['uc']; $events=pluginEvents($g);
    mkdirs(["$d","$d/language/en-GB","$d/fields"]); idx("$d/index.html");

    $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<extension type=\"plugin\" group=\"$g\" version=\"3.0\" method=\"upgrade\">\n";
    $xml.= "    <name>plg_{$g}_$a</name>\n    <author>{$p['author']}</author>\n    <creationDate>{$m['date']}</creationDate>\n";
    $xml.= "    <version>{$p['version']}</version>\n    <description>PLG_{$m['upper']}_DESCRIPTION</description>\n";
    $xml.= "    <files>\n        <filename plugin=\"$a\">$a.php</filename>\n        <filename>index.html</filename>\n        <folder>fields</folder>\n    </files>\n";
    $xml.= "    <languages folder=\"language\"><language tag=\"en-GB\">en-GB/plg_{$g}_$a.ini</language><language tag=\"en-GB\">en-GB/plg_{$g}_$a.sys.ini</language></languages>\n";
    $xml.= "    <config><fields name=\"params\"><fieldset name=\"basic\">\n";
    $xml.= "        <field name=\"enabled_feature\" type=\"radio\" label=\"PLG_{$m['upper']}_ENABLE\" class=\"btn-group\" default=\"1\"><option value=\"0\">JNO</option><option value=\"1\">JYES</option></field>\n";
    $xml.= "    </fieldset></fields></config>\n</extension>\n";
    file_put_contents("$d/$a.xml",$xml);

    $c = "<?php\ndefined('_JEXEC') or die;\n\njimport('joomla.plugin.plugin');\n\n";
    $c.= "class Plg{$ucG}{$ucA} extends JPlugin\n{\n    protected \$autoloadLanguage = true;\n    protected \$app;\n    protected \$db;\n\n";
    foreach ($events as $ev) {
        $c.= "    public function $ev()\n    {\n        if (!\$this->params->get('enabled_feature', 1)) return true;\n        // TODO: $ev\n        return true;\n    }\n\n";
    }
    $c = rtrim($c)."\n}\n";
    file_put_contents("$d/$a.php",$c);

    file_put_contents("$d/language/en-GB/plg_{$g}_$a.ini","PLG_{$m['upper']}_ENABLE=\"Activer\"\n");
    file_put_contents("$d/language/en-GB/plg_{$g}_$a.sys.ini","PLG_".strtoupper($g.'_'.$a)."=\"{$p['name']}\"\nPLG_{$m['upper']}_DESCRIPTION=\"{$p['description']}\"\n");
}

function generateAdvancedPlgModern($p,$d) {
    $a=$p['alias']; $g=$p['pluginGroup']; $m=meta($p); $v=$p['vendor']; $uc=$m['uc'];
    $ucG=ucfirst($g);
    $ns=$v.'\\Plugin\\'.$ucG.'\\'.$uc;
    $events=pluginEvents($g);
    mkdirs(["$d","$d/services","$d/src/Extension","$d/language/en-GB"]);

    $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<extension type=\"plugin\" group=\"$g\" method=\"upgrade\">\n";
    $xml.= "    <name>plg_{$g}_$a</name>\n    <author>{$p['author']}</author>\n    <creationDate>{$m['date']}</creationDate>\n";
    $xml.= "    <version>{$p['version']}</version>\n    <description>PLG_{$m['upper']}_DESCRIPTION</description>\n";
    $xml.= "    <namespace path=\"src\">$ns</namespace>\n";
    $xml.= "    <files>\n        <folder plugin=\"$a\">services</folder>\n        <folder>src</folder>\n    </files>\n";
    $xml.= "    <languages folder=\"language\"><language tag=\"en-GB\">en-GB/plg_{$g}_$a.ini</language><language tag=\"en-GB\">en-GB/plg_{$g}_$a.sys.ini</language></languages>\n";
    $xml.= "    <config><fields name=\"params\"><fieldset name=\"basic\">\n";
    $xml.= "        <field name=\"enabled_feature\" type=\"radio\" label=\"PLG_{$m['upper']}_ENABLE\" layout=\"joomla.form.field.radio.switcher\" default=\"1\"><option value=\"0\">JNO</option><option value=\"1\">JYES</option></field>\n";
    $xml.= "    </fieldset></fields></config>\n</extension>\n";
    file_put_contents("$d/$a.xml",$xml);

    // services/provider.php
    $c = "<?php\ndefined('_JEXEC') or die;\n\nuse Joomla\\CMS\\Extension\\PluginInterface;\nuse Joomla\\CMS\\Factory;\nuse Joomla\\CMS\\Plugin\\PluginHelper;\n";
    $c.= "use Joomla\\DI\\Container;\nuse Joomla\\DI\\ServiceProviderInterface;\nuse Joomla\\Event\\DispatcherInterface;\nuse $ns\\Extension\\$uc;\n\n";
    $c.= "return new class implements ServiceProviderInterface {\n    public function register(Container \$container): void\n    {\n";
    $c.= "        \$container->set(PluginInterface::class, function (Container \$container) {\n";
    $c.= "            \$dispatcher = \$container->get(DispatcherInterface::class);\n";
    $c.= "            \$plugin = new $uc(\$dispatcher, (array) PluginHelper::getPlugin('$g', '$a'));\n";
    $c.= "            \$plugin->setApplication(Factory::getApplication());\n            return \$plugin;\n        });\n    }\n};\n";
    file_put_contents("$d/services/provider.php",$c);

    // Extension
    $c = "<?php\nnamespace $ns\\Extension;\n\ndefined('_JEXEC') or die;\n\nuse Joomla\\CMS\\Plugin\\CMSPlugin;\nuse Joomla\\Event\\SubscriberInterface;\n\n";
    $c.= "final class $uc extends CMSPlugin implements SubscriberInterface\n{\n";
    $c.= "    public static function getSubscribedEvents(): array\n    {\n        return [\n";
    foreach ($events as $ev) $c.= "            '$ev' => 'handle".ucfirst($ev)."',\n";
    $c.= "        ];\n    }\n\n";
    foreach ($events as $ev) {
        $c.= "    public function handle".ucfirst($ev)."(\$event): void\n    {\n";
        $c.= "        if (!\$this->params->get('enabled_feature', 1)) return;\n        // TODO: $ev\n    }\n\n";
    }
    $c = rtrim($c)."\n}\n";
    file_put_contents("$d/src/Extension/$uc.php",$c);

    file_put_contents("$d/language/en-GB/plg_{$g}_$a.ini","PLG_{$m['upper']}_ENABLE=\"Activer\"\n");
    file_put_contents("$d/language/en-GB/plg_{$g}_$a.sys.ini","PLG_".strtoupper($g.'_'.$a)."=\"{$p['name']}\"\nPLG_{$m['upper']}_DESCRIPTION=\"{$p['description']}\"\n");
}

/* ═══════════════════ FIN PHP ═══════════════════ */
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Générateur d'Extensions Joomla</title>
<style>
:root{--primary:#1e3c72;--primary-light:#2a5298;--accent:#00b4d8;--success:#28a745;--danger:#dc3545;--warning:#ffc107;--border:#e0e0e0;--text:#333;--muted:#888;--radius:12px}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:linear-gradient(160deg,var(--primary),var(--primary-light),#1a1a2e);min-height:100vh;padding:20px}
.container{max-width:960px;margin:0 auto;background:#fff;border-radius:20px;box-shadow:0 25px 80px rgba(0,0,0,.35);overflow:hidden}
.hero{background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;padding:40px 40px 30px;text-align:center;position:relative;overflow:hidden}
.hero h1{font-size:2.2em;margin-bottom:8px;position:relative;z-index:1}
.hero p{opacity:.85;font-size:1.1em;position:relative;z-index:1}
.hbadge{display:inline-block;background:rgba(255,255,255,.2);backdrop-filter:blur(10px);padding:4px 14px;border-radius:20px;font-size:.8em;margin-top:10px;position:relative;z-index:1}
.body{padding:40px}
.alert{padding:18px 22px;margin-bottom:25px;border-radius:var(--radius);font-size:.95em}
.alert-success{background:linear-gradient(135deg,#d4edda,#c3e6cb);color:#155724;border-left:5px solid var(--success)}
.alert-error{background:linear-gradient(135deg,#f8d7da,#f5c6cb);color:#721c24;border-left:5px solid var(--danger)}
.alert ul{margin:8px 0 0 18px}
.download-link{display:inline-block;margin-top:10px;padding:12px 24px;background:var(--success);color:#fff!important;text-decoration:none;border-radius:8px;font-weight:700;transition:all .2s}
.download-link:hover{background:#218838;transform:translateY(-1px)}

/* Cards */
.cards-3{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px}
.cards-4{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px}
.card-r{position:relative}
.card-r input{position:absolute;opacity:0;pointer-events:none}
.card-r label{display:flex;flex-direction:column;align-items:center;padding:22px 12px;border:3px solid var(--border);border-radius:var(--radius);cursor:pointer;transition:all .3s;background:#fff;text-align:center}
.card-r label:hover{border-color:var(--accent);box-shadow:0 4px 15px rgba(0,180,216,.12)}
.card-r input:checked+label{border-color:var(--primary);background:linear-gradient(135deg,#e8f0fe,#dce8fc);transform:scale(1.02);box-shadow:0 6px 20px rgba(30,60,114,.12)}
.card-r .icon{font-size:2.4em;margin-bottom:8px;transition:transform .3s}
.card-r input:checked+label .icon{transform:scale(1.1)}
.card-r .title{font-weight:700;font-size:1.05em;color:var(--text)}
.card-r .desc{font-size:.75em;color:var(--muted);margin-top:3px;line-height:1.3}
.card-r .file-count{display:inline-block;background:var(--primary);color:#fff;padding:1px 8px;border-radius:10px;font-size:.7em;margin-top:4px}

/* Joomla version colors */
.jv-3 input:checked+label{border-color:#F7931E!important;background:linear-gradient(135deg,#fff5e6,#ffe8cc)!important}
.jv-4 input:checked+label{border-color:#1C3D5C!important;background:linear-gradient(135deg,#e6edf5,#d0dce8)!important}
.jv-5 input:checked+label{border-color:#2a5298!important;background:linear-gradient(135deg,#e8f0fe,#dce8fc)!important}
.jv-6 input:checked+label{border-color:#7C3AED!important;background:linear-gradient(135deg,#f0e8ff,#e4d8fc)!important}

.section{margin-bottom:28px}
.section-title{display:flex;align-items:center;gap:8px;font-size:1.05em;font-weight:700;color:var(--primary);margin-bottom:14px;padding-bottom:8px;border-bottom:2px solid var(--border)}
.section-title .emoji{font-size:1.2em}

.cond-fields{display:none;animation:fadeIn .3s ease}
.cond-fields.active{display:block}
@keyframes fadeIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
.cond-fields .inner{background:#f8f9fa;border:1px solid var(--border);border-radius:var(--radius);padding:18px;margin-bottom:18px}

.preview{background:#1a1a2e;color:#d4d4d4;padding:16px;border-radius:var(--radius);font-family:'JetBrains Mono',Consolas,Monaco,monospace;font-size:.8em;line-height:1.7;max-height:300px;overflow-y:auto;margin-top:10px;display:none;animation:fadeIn .3s;border:1px solid rgba(255,255,255,.1)}
.preview.active{display:block}
.preview .folder{color:#569cd6}.preview .file{color:#9cdcfe}.preview .special{color:#dcdcaa}.preview .dim{color:#555}.preview .legacy{color:#F7931E}

.simple-notice{background:linear-gradient(135deg,#fff3cd,#ffeeba);border:1px solid var(--warning);border-radius:var(--radius);padding:12px 16px;margin-bottom:18px;font-size:.88em;color:#856404;display:none;align-items:center;gap:10px}
.simple-notice.active{display:flex}

.version-notice{border-radius:var(--radius);padding:10px 16px;margin-bottom:18px;font-size:.85em;display:none;align-items:center;gap:8px}
.version-notice.active{display:flex}
.vn-3{background:#fff5e6;color:#8a5a00;border:1px solid #F7931E}
.vn-4{background:#e6edf5;color:#1C3D5C;border:1px solid #1C3D5C}
.vn-5{background:#e8f0fe;color:#1e3c72;border:1px solid #2a5298}
.vn-6{background:#f0e8ff;color:#5B21B6;border:1px solid #7C3AED}

.ns-badge{display:inline-block;background:var(--primary);color:#fff;padding:3px 10px;border-radius:6px;font-family:monospace;font-size:.82em;margin-top:4px;word-break:break-all}

.form-group{margin-bottom:16px}
.form-group>label{display:block;margin-bottom:4px;font-weight:600;color:var(--text);font-size:.92em}
.required{color:var(--danger)}
input[type=text],input[type=email],input[type=url],input[type=number],select,textarea{width:100%;padding:10px 13px;border:2px solid var(--border);border-radius:8px;font-size:.92em;transition:all .25s;font-family:inherit}
input:focus,textarea:focus,select:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(42,82,152,.1)}
textarea{resize:vertical;min-height:65px}
select{cursor:pointer;appearance:none;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23888' d='M6 8L1 3h10z'/%3E%3C/svg%3E") no-repeat right 12px center #fff}
.form-help{font-size:.76em;color:var(--muted);margin-top:3px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.btn{width:100%;padding:15px;border:none;border-radius:var(--radius);font-size:1.1em;font-weight:700;cursor:pointer;transition:all .3s;color:#fff;background:linear-gradient(135deg,var(--primary),var(--primary-light))}
.btn:hover{transform:translateY(-2px);box-shadow:0 12px 35px rgba(30,60,114,.4)}
.footer{text-align:center;padding:22px 40px;background:#f8f9fa;color:var(--muted);font-size:.85em;border-top:1px solid var(--border)}
.footer .jbadge{display:inline-block;background:var(--primary);color:#fff;padding:2px 10px;border-radius:20px;font-size:.8em;margin-top:5px}
@media(max-width:768px){.cards-3,.cards-4{grid-template-columns:1fr 1fr}.row,.row-3{grid-template-columns:1fr}.body{padding:22px}.hero{padding:30px 22px 25px}.hero h1{font-size:1.6em}}
@media(max-width:480px){.cards-3,.cards-4{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="container">
    <div class="hero">
        <h1>🚀 Générateur d'Extensions Joomla</h1>
        <p>Composants · Modules · Plugins</p>
        <span class="hbadge">Joomla 3.x · 4.x · 5.x · 6.x</span>
    </div>
    <div class="body">
        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <?php if (!empty($errors)): ?><div class="alert alert-error"><strong>⚠️ Erreurs :</strong><ul><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div><?php endif; ?>
        
        <form method="POST" id="mainForm">
            <!-- ═══ Version Joomla ═══ -->
            <div class="section">
                <div class="section-title"><span class="emoji">🏷️</span> Version Joomla</div>
                <div class="cards-4">
                    <div class="card-r jv-3"><input type="radio" id="jv3" name="joomla_version" value="3" <?= ($_POST['joomla_version']??'')==='3'?'checked':'' ?>><label for="jv3"><span class="icon" style="color:#F7931E">③</span><span class="title">Joomla 3</span><span class="desc">Legacy MVC<br>JFactory, JPlugin</span></label></div>
                    <div class="card-r jv-4"><input type="radio" id="jv4" name="joomla_version" value="4" <?= ($_POST['joomla_version']??'')==='4'?'checked':'' ?>><label for="jv4"><span class="icon" style="color:#1C3D5C">④</span><span class="title">Joomla 4</span><span class="desc">Transition<br>Namespaces + MVC</span></label></div>
                    <div class="card-r jv-5"><input type="radio" id="jv5" name="joomla_version" value="5" <?= ($_POST['joomla_version']??'5')==='5'?'checked':'' ?>><label for="jv5"><span class="icon" style="color:#2a5298">⑤</span><span class="title">Joomla 5</span><span class="desc">Modern<br>PHP 8.1+ PSR-4</span></label></div>
                    <div class="card-r jv-6"><input type="radio" id="jv6" name="joomla_version" value="6" <?= ($_POST['joomla_version']??'')==='6'?'checked':'' ?>><label for="jv6"><span class="icon" style="color:#7C3AED">⑥</span><span class="title">Joomla 6</span><span class="desc">Future<br>PHP 8.2+</span></label></div>
                </div>
                <div class="version-notice vn-3" id="vn3">⚠️ <strong>Joomla 3</strong> — Classes legacy : JFactory, JPlugin, JModelList, JViewLegacy…</div>
                <div class="version-notice vn-4" id="vn4">ℹ️ <strong>Joomla 4</strong> — Transition : namespaces Joomla\CMS\*, services/provider.php</div>
                <div class="version-notice vn-5" id="vn5">✅ <strong>Joomla 5</strong> — Modern : PHP 8.1+, return types, SubscriberInterface</div>
                <div class="version-notice vn-6" id="vn6">🔮 <strong>Joomla 6</strong> — Futur : PHP 8.2+, même architecture que J5</div>
            </div>

            <!-- ═══ Type ═══ -->
            <div class="section">
                <div class="section-title"><span class="emoji">📦</span> Type d'extension</div>
                <div class="cards-3">
                    <div class="card-r"><input type="radio" id="t-comp" name="extension_type" value="component" <?= ($_POST['extension_type']??'component')==='component'?'checked':'' ?>><label for="t-comp"><span class="icon">⚙️</span><span class="title">Composant</span><span class="desc">Admin + Site</span></label></div>
                    <div class="card-r"><input type="radio" id="t-mod" name="extension_type" value="module" <?= ($_POST['extension_type']??'')==='module'?'checked':'' ?>><label for="t-mod"><span class="icon">🧩</span><span class="title">Module</span><span class="desc">Bloc positionnable</span></label></div>
                    <div class="card-r"><input type="radio" id="t-plg" name="extension_type" value="plugin" <?= ($_POST['extension_type']??'')==='plugin'?'checked':'' ?>><label for="t-plg"><span class="icon">🔌</span><span class="title">Plugin</span><span class="desc">Hook événements</span></label></div>
                </div>
            </div>

            <!-- ═══ Structure ═══ -->
            <div class="section">
                <div class="section-title"><span class="emoji">🏗️</span> Structure</div>
                <div class="cards-3">
                    <div class="card-r"><input type="radio" id="s-simple" name="structure_type" value="simple" <?= ($_POST['structure_type']??'')==='simple'?'checked':'' ?>><label for="s-simple"><div class="icon">📎</div><div class="title">Simple</div><div class="desc">Minimal<br>admin/ + site/</div><span class="file-count" id="fc-s"></span></label></div>
                    <div class="card-r"><input type="radio" id="s-basic" name="structure_type" value="basic" <?= ($_POST['structure_type']??'')==='basic'?'checked':'' ?>><label for="s-basic"><div class="icon">📄</div><div class="title">Basic</div><div class="desc">+ Language<br>+ Helper</div><span class="file-count" id="fc-b"></span></label></div>
                    <div class="card-r"><input type="radio" id="s-adv" name="structure_type" value="advanced" <?= ($_POST['structure_type']??'advanced')==='advanced'?'checked':'' ?>><label for="s-adv"><div class="icon">🏛️</div><div class="title">Avancé</div><div class="desc" id="adv-desc">PSR-4, Services</div><span class="file-count" id="fc-a"></span></label></div>
                </div>
                <div class="simple-notice" id="simple-notice"><span>📎</span><div><strong>Mode Simple</strong> — Structure minimale type <em>Hello World</em></div></div>
                <div id="ns-info" class="cond-fields">
                    <div class="inner">
                        <div class="form-group" style="margin-bottom:6px">
                            <label for="vendor_name">Vendor / Namespace</label>
                            <input type="text" id="vendor_name" name="vendor_name" placeholder="MyCompany" value="<?= htmlspecialchars($_POST['vendor_name']??'MyCompany') ?>">
                            <div class="form-help">Namespace : <span class="ns-badge" id="ns-preview"></span></div>
                        </div>
                    </div>
                </div>
                <div id="structure-preview-container"></div>
            </div>

            <!-- ═══ Options Module ═══ -->
            <div class="cond-fields" id="module-fields"><div class="inner">
                <div class="section-title"><span class="emoji">🧩</span> Options du module</div>
                <div class="row">
                    <div class="form-group"><label>Emplacement</label><select name="module_client"><option value="site" <?= ($_POST['module_client']??'')==='site'?'selected':'' ?>>Site</option><option value="administrator" <?= ($_POST['module_client']??'')==='administrator'?'selected':'' ?>>Admin</option></select></div>
                    <div class="form-group"><label>Position</label><input type="text" name="module_position" value="<?= htmlspecialchars($_POST['module_position']??'sidebar-right') ?>"></div>
                </div>
            </div></div>

            <!-- ═══ Options Plugin ═══ -->
            <div class="cond-fields" id="plugin-fields"><div class="inner">
                <div class="section-title"><span class="emoji">🔌</span> Options du plugin</div>
                <div class="form-group">
                    <label>Groupe</label>
                    <select name="plugin_group" id="plugin_group">
                        <?php foreach(['content'=>'Content','system'=>'System','user'=>'User','authentication'=>'Authentication','editors'=>'Editors','editors-xtd'=>'Editors XTD','finder'=>'Smart Search','installer'=>'Installer','extension'=>'Extension','actionlog'=>'Action Log','quickicon'=>'Quick Icon','task'=>'Task Scheduler'] as $k=>$v): ?>
                        <option value="<?=$k?>" <?= ($_POST['plugin_group']??'')===$k?'selected':'' ?>><?=$v?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-help">Événements : <span id="events-preview" style="color:var(--primary);font-weight:600"></span></div>
                </div>
            </div></div>

            <!-- ═══ Identité ═══ -->
            <div class="section">
                <div class="section-title"><span class="emoji">✏️</span> Identité</div>
                <div class="row">
                    <div class="form-group"><label id="lbl-name">Nom <span class="required">*</span></label><input type="text" id="extension_name" name="extension_name" placeholder="Mon Extension" required value="<?= htmlspecialchars($_POST['extension_name']??'') ?>"></div>
                    <div class="form-group"><label>Alias <span class="required">*</span></label><input type="text" id="extension_alias" name="extension_alias" placeholder="monextension" required pattern="[a-z][a-z0-9_]*" value="<?= htmlspecialchars($_POST['extension_alias']??'') ?>"><div class="form-help" id="alias-help"></div></div>
                </div>
                <div class="form-group"><label>Description</label><textarea name="description" placeholder="Description…"><?= htmlspecialchars($_POST['description']??'') ?></textarea></div>
            </div>

            <!-- ═══ Auteur ═══ -->
            <div class="section">
                <div class="section-title"><span class="emoji">👤</span> Auteur</div>
                <div class="row-3">
                    <div class="form-group"><label>Nom</label><input type="text" name="author" value="<?= htmlspecialchars($_POST['author']??'') ?>"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="author_email" value="<?= htmlspecialchars($_POST['author_email']??'') ?>"></div>
                    <div class="form-group"><label>Site web</label><input type="url" name="author_url" value="<?= htmlspecialchars($_POST['author_url']??'') ?>"></div>
                </div>
                <div class="row">
                    <div class="form-group"><label>Version</label><input type="text" name="version" value="<?= htmlspecialchars($_POST['version']??'1.0.0') ?>"></div>
                    <div class="form-group"><label>Licence</label><input type="text" name="license" value="<?= htmlspecialchars($_POST['license']??'GNU/GPL') ?>"></div>
                </div>
            </div>

            <button type="submit" class="btn" id="submit-btn">✨ Générer</button>
        </form>
    </div>
    <div class="footer"><p>Générateur d'Extensions Joomla — v6.0</p><span class="jbadge">Multi-version</span></div>
</div>

<script>
const $=s=>document.querySelector(s),$$=s=>document.querySelectorAll(s);
const evMap=<?= json_encode(['content'=>['onContentPrepare','onContentBeforeSave','onContentAfterSave','onContentAfterDelete'],'system'=>['onAfterInitialise','onAfterRoute','onBeforeRender','onAfterRender'],'user'=>['onUserAfterSave','onUserAfterDelete','onUserLogin','onUserLogout'],'authentication'=>['onUserAuthenticate'],'editors'=>['onInit','onDisplay','onGetContent','onSetContent'],'editors-xtd'=>['onDisplay'],'finder'=>['onFinderAfterSave','onFinderAfterDelete','onFinderChangeState'],'installer'=>['onInstallerBeforeInstallation','onInstallerAfterInstaller'],'extension'=>['onExtensionAfterInstall','onExtensionAfterUninstall','onExtensionAfterUpdate'],'actionlog'=>['onContentAfterSave','onContentAfterDelete'],'quickicon'=>['onGetIcons'],'task'=>['onExecuteTask']]) ?>;

function updateUI(){
    const type=document.querySelector('input[name="extension_type"]:checked')?.value||'component';
    const struct=document.querySelector('input[name="structure_type"]:checked')?.value||'advanced';
    const jv=document.querySelector('input[name="joomla_version"]:checked')?.value||'5';
    const alias=$('#extension_alias').value||'alias';
    const vendor=($('#vendor_name')?.value||'MyCompany').replace(/[^a-zA-Z0-9]/g,'');
    const group=$('#plugin_group').value;
    const isJ3=jv==='3', isAdv=struct==='advanced', isSimple=struct==='simple';

    // Panels
    $('#module-fields').classList.toggle('active',type==='module');
    $('#plugin-fields').classList.toggle('active',type==='plugin');
    $('#ns-info').classList.toggle('active',isAdv&&!isJ3);
    $('#simple-notice').classList.toggle('active',isSimple);
    ['3','4','5','6'].forEach(v=>$('#vn'+v).classList.toggle('active',jv===v));

    // Advanced desc
    $('#adv-desc').innerHTML=isJ3?'Legacy MVC<br>controller.php, models/':'PSR-4, Services<br>Dispatcher, MVC';

    // File counts
    const fc={
        component:{simple:{3:'~8',4:'~8',5:'~8',6:'~8'},basic:{3:'~12',4:'~12',5:'~12',6:'~12'},advanced:{3:'~16',4:'~22',5:'~22',6:'~22'}},
        module:{simple:{3:'~5',4:'~5',5:'~5',6:'~5'},basic:{3:'~8',4:'~8',5:'~8',6:'~8'},advanced:{3:'~8',4:'~10',5:'~10',6:'~10'}},
        plugin:{simple:{3:'~3',4:'~3',5:'~3',6:'~3'},basic:{3:'~5',4:'~5',5:'~5',6:'~5'},advanced:{3:'~6',4:'~7',5:'~7',6:'~7'}}
    };
    const t=fc[type]||fc.component;
    $('#fc-s').textContent=(t.simple[jv]||'~5')+' fichiers';
    $('#fc-b').textContent=(t.basic[jv]||'~8')+' fichiers';
    $('#fc-a').textContent=(t.advanced[jv]||'~10')+' fichiers';

    // Labels
    const labels={component:'Composant',module:'Module',plugin:'Plugin'};
    $('#lbl-name').innerHTML=`Nom du ${labels[type]} <span class="required">*</span>`;
    const pre={component:`com_<b>${alias}</b>`,module:`mod_<b>${alias}</b>`,plugin:`plg_<b>${group}</b>_<b>${alias}</b>`};
    $('#alias-help').innerHTML='→ '+pre[type];
    $('#submit-btn').textContent=`✨ Générer le ${labels[type].toLowerCase()} (Joomla ${jv})`;

    // Namespace
    const uc=alias.charAt(0).toUpperCase()+alias.slice(1);
    const ucG=group.charAt(0).toUpperCase()+group.slice(1);
    const nsMap={component:`${vendor}\\Component\\${uc}`,module:`${vendor}\\Module\\${uc}`,plugin:`${vendor}\\Plugin\\${ucG}\\${uc}`};
    if($('#ns-preview'))$('#ns-preview').textContent=nsMap[type];

    // Events
    if(type==='plugin')$('#events-preview').textContent=(evMap[group]||[]).join(', ');

    // Tree
    const F=n=>`<span class="file">${n}</span>`,D=n=>`<span class="folder">${n}/</span>`,S=n=>`<span class="special">${n}</span>`,DIM=n=>`<span class="dim">${n}</span>`,L=n=>`<span class="legacy">${n}</span>`;
    let tree='';

    if(type==='component'){
        if(isSimple){
            tree=`📁 com_${alias}/\n├── ${F(alias+'.xml')}\n├── 📁 ${D('admin')}\n│   ├── ${F(alias+'.php')}\n│   ├── ${DIM('index.html')}\n│   └── 📁 ${D('sql')}\n│       ├── ${DIM('index.html')}\n│       └── 📁 ${D('updates')}\n│           ├── ${DIM('index.html')}\n│           └── 📁 ${D('mysql')}\n│               ├── ${F('x.x.x.sql')}\n│               └── ${DIM('index.html')}\n└── 📁 ${D('site')}\n    ├── ${F(alias+'.php')}\n    └── ${DIM('index.html')}`;
        }else if(struct==='basic'){
            tree=`📁 com_${alias}/\n├── ${F(alias+'.xml')}\n├── 📁 ${D('admin')}\n│   ├── ${F(alias+'.php')}  ${isJ3?L('JFactory'):S('Factory')}\n│   ├── ${DIM('index.html')}\n│   ├── 📁 ${D('sql/updates/mysql')}\n│   └── 📁 ${D('language/en-GB')}\n└── 📁 ${D('site')}\n    ├── ${F(alias+'.php')}\n    ├── ${DIM('index.html')}\n    └── 📁 ${D('language/en-GB')}`;
        }else if(isJ3){
            tree=`📁 com_${alias}/  ${L('Joomla 3 Legacy MVC')}\n├── ${F(alias+'.xml')}\n├── 📁 ${D('admin')}\n│   ├── ${F(alias+'.php')}  ${L('→ JControllerLegacy')}\n│   ├── ${F('controller.php')}  ${L('JControllerLegacy')}\n│   ├── 📁 ${D('controllers')}\n│   ├── 📁 ${D('models')} → ${F('items.php')}  ${L('JModelList')}\n│   ├── 📁 ${D('views')}\n│   │   └── 📁 ${D('items')}\n│   │       ├── ${F('view.html.php')}  ${L('JViewLegacy')}\n│   │       └── 📁 ${D('tmpl')} → ${F('default.php')}\n│   ├── 📁 ${D('tables')} → ${F(alias+'.php')}  ${L('JTable')}\n│   ├── 📁 ${D('helpers')}\n│   ├── 📁 ${D('sql')}  ${S('install + uninstall + updates')}\n│   └── 📁 ${D('language/en-GB')}\n└── 📁 ${D('site')}\n    ├── ${F(alias+'.php')}  ${L('→ JControllerLegacy')}\n    ├── ${F('controller.php')}\n    ├── 📁 ${D('views/'+alias)} → ${F('view.html.php')}\n    └── 📁 ${D('language/en-GB')}`;
        }else{
            tree=`📁 com_${alias}/  ${S('Joomla '+jv+' Modern MVC')}\n├── ${F(alias+'.xml')}  +  ${S('script.php')}\n├── 📁 ${D('admin')}\n│   ├── 📁 ${D('services')} → ${F('provider.php')}\n│   ├── 📁 ${D('src')}\n│   │   ├── ${D('Extension')}  ${D('Controller')}\n│   │   ├── ${D('Model')}  ${D('View/Items')}\n│   ├── 📁 ${D('tmpl')}  ${D('forms')}\n│   ├── 📁 ${D('sql')}  ${S('access.xml')}  ${S('config.xml')}\n│   └── 📁 ${D('language/en-GB')}\n├── 📁 ${D('site')}\n│   ├── 📁 ${D('src')} (Controller + View)\n│   ├── 📁 ${D('tmpl/default')}\n│   └── 📁 ${D('language/en-GB')}\n└── 📁 ${D('media')} → ${F('joomla.asset.json')} + css/ + js/`;
        }
    }else if(type==='module'){
        if(isSimple){
            tree=`📁 mod_${alias}/\n├── ${F('mod_'+alias+'.xml')}\n├── ${F('mod_'+alias+'.php')}  ${isJ3?L('JModuleHelper'):S('ModuleHelper')}\n├── ${DIM('index.html')}\n└── 📁 ${D('tmpl')} → ${F('default.php')}`;
        }else if(struct==='basic'){
            tree=`📁 mod_${alias}/\n├── ${F('mod_'+alias+'.xml')}\n├── ${F('mod_'+alias+'.php')}\n├── ${F('helper.php')}  ${isJ3?L('static methods'):S('instance')}\n├── ${DIM('index.html')}\n├── 📁 ${D('tmpl')} → ${F('default.php')}\n└── 📁 ${D('language/en-GB')}`;
        }else if(isJ3){
            tree=`📁 mod_${alias}/  ${L('Joomla 3')}\n├── ${F('mod_'+alias+'.xml')}\n├── ${F('mod_'+alias+'.php')}  ${L('JModuleHelper')}\n├── ${F('helper.php')}  ${L('static methods')}\n├── ${DIM('index.html')}\n├── 📁 ${D('tmpl')} → ${F('default.php')}\n├── 📁 ${D('css')} → ${F('style.css')}\n└── 📁 ${D('language/en-GB')}`;
        }else{
            tree=`📁 mod_${alias}/  ${S('Joomla '+jv+' Dispatcher')}\n├── ${F('mod_'+alias+'.xml')}\n├── 📁 ${D('services')} → ${S('provider.php')}\n├── 📁 ${D('src')}\n│   ├── ${D('Dispatcher')} → ${F('Dispatcher.php')}\n│   └── ${D('Helper')} → ${F(uc+'Helper.php')}\n├── 📁 ${D('tmpl')} → ${F('default.php')}\n└── 📁 ${D('language/en-GB')}`;
        }
    }else{
        const ec=(evMap[group]||[]).length;
        if(isSimple){
            tree=`📁 plg_${group}_${alias}/\n├── ${F(alias+'.xml')}\n├── ${F(alias+'.php')}  ${isJ3?L('JPlugin'):S('CMSPlugin')}  ${S('('+ec+' evt)')}\n└── ${DIM('index.html')}`;
        }else if(struct==='basic'){
            tree=`📁 plg_${group}_${alias}/\n├── ${F(alias+'.xml')}\n├── ${F(alias+'.php')}  ${isJ3?L('JPlugin · '+ec+' evt'):S('CMSPlugin · '+ec+' evt')}\n├── ${DIM('index.html')}\n└── 📁 ${D('language/en-GB')}`;
        }else if(isJ3){
            tree=`📁 plg_${group}_${alias}/  ${L('Joomla 3')}\n├── ${F(alias+'.xml')}\n├── ${F(alias+'.php')}  ${L('JPlugin · '+ec+' événements')}\n├── ${DIM('index.html')}\n├── 📁 ${D('fields')}\n└── 📁 ${D('language/en-GB')}`;
        }else{
            tree=`📁 plg_${group}_${alias}/  ${S('Joomla '+jv+' SubscriberInterface')}\n├── ${F(alias+'.xml')}\n├── 📁 ${D('services')} → ${S('provider.php')}\n├── 📁 ${D('src')}\n│   └── ${D('Extension')} → ${F(uc+'.php')}\n└── 📁 ${D('language/en-GB')}`;
        }
    }
    $('#structure-preview-container').innerHTML=`<div class="preview active">${tree}</div>`;
}

$$('input[name="extension_type"]').forEach(r=>r.addEventListener('change',updateUI));
$$('input[name="structure_type"]').forEach(r=>r.addEventListener('change',updateUI));
$$('input[name="joomla_version"]').forEach(r=>r.addEventListener('change',updateUI));
$('#plugin_group').addEventListener('change',updateUI);
if($('#vendor_name'))$('#vendor_name').addEventListener('input',updateUI);
$('#extension_alias').addEventListener('input',function(){this.dataset.modified='1';updateUI();});
$('#extension_name').addEventListener('input',function(){
    const a=this.value.toLowerCase().replace(/[^a-z0-9]/g,'').substring(0,20);
    const af=$('#extension_alias');if(!af.dataset.modified)af.value=a;updateUI();
});
updateUI();
</script>
</body>
</html>
