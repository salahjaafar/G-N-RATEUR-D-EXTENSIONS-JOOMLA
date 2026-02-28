<?php
/**
 * =============================================================
 * GÉNÉRATEUR D'EXTENSIONS JOOMLA 5 — Version 1
 * Composants • Modules • Plugins
 * Simple (minimal) • Basic • Avancé (PSR-4)
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

    $errors = [];
    if (empty($name))  $errors[] = "Le nom est requis";
    if (empty($alias)) $errors[] = "L'alias est requis";
    if (!preg_match('/^[a-z][a-z0-9_]*$/', $alias)) $errors[] = "Alias : lettres minuscules, chiffres, underscores (commence par une lettre)";
    if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) $errors[] = "Version au format x.y.z";

    if (empty($errors)) {
        $params = compact('name','alias','vendor','author','authorEmail','authorUrl','version','description','license','structureType','pluginGroup','moduleClient');
        switch ($extensionType) {
            case 'module':  generateModule($params); break;
            case 'plugin':  generatePlugin($params); break;
            default:        generateComponent($params); break;
        }
    }
}

/* ──────────────── UTILITAIRES ──────────────── */

function idx($p) { file_put_contents($p, '<html><body bgcolor="#FFFFFF"></body></html>'); }
function mkdirs(array $dirs) { foreach ($dirs as $d) if (!is_dir($d)) mkdir($d, 0755, true); }

function zipAndFinish($dir, $label) {
    $zip = $dir . '.zip';
    createZip($dir, $zip);
    delDir($dir);
    $_SESSION['success'] = "$label : <a href='$zip' download class='download-link'>📦 Télécharger $zip</a>";
    header('Location: ' . $_SERVER['PHP_SELF']); exit;
}

function createZip($src, $dst) {
    $z = new ZipArchive();
    $z->open($dst, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $src = realpath($src);
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src), RecursiveIteratorIterator::LEAVES_ONLY);
    foreach ($it as $f) { if (!$f->isDir()) $z->addFile($f->getRealPath(), substr($f->getRealPath(), strlen($src)+1)); }
    $z->close();
}

function delDir($d) {
    if (!file_exists($d)) return;
    if (!is_dir($d)) { unlink($d); return; }
    foreach (scandir($d) as $i) { if ($i==='.'||$i==='..') continue; delDir("$d/$i"); }
    rmdir($d);
}

function pluginEvents($group) {
    $map = [
        'content'        => ['onContentPrepare','onContentBeforeSave','onContentAfterSave','onContentAfterDelete'],
        'system'         => ['onAfterInitialise','onAfterRoute','onBeforeRender','onAfterRender'],
        'user'           => ['onUserAfterSave','onUserAfterDelete','onUserLogin','onUserLogout'],
        'authentication' => ['onUserAuthenticate'],
        'editors'        => ['onInit','onDisplay','onGetContent','onSetContent'],
        'editors-xtd'    => ['onDisplay'],
        'finder'         => ['onFinderAfterSave','onFinderAfterDelete','onFinderChangeState'],
        'installer'      => ['onInstallerBeforeInstallation','onInstallerAfterInstaller'],
        'extension'      => ['onExtensionAfterInstall','onExtensionAfterUninstall','onExtensionAfterUpdate'],
        'actionlog'      => ['onContentAfterSave','onContentAfterDelete'],
        'quickicon'      => ['onGetIcons'],
        'task'           => ['onExecuteTask'],
    ];
    return $map[$group] ?? $map['content'];
}

function meta($p) {
    return ['date'=>date('F Y'),'year'=>date('Y'),'uc'=>ucfirst($p['alias']),'upper'=>strtoupper($p['alias'])];
}

/* ================================================================
   COMPOSANT
   ================================================================ */
function generateComponent($p) {
    $d = "com_{$p['alias']}";
    switch ($p['structureType']) {
        case 'simple':   generateSimpleComp($p, $d); break;
        case 'basic':    generateBasicComp($p, $d); break;
        default:         generateAdvancedComp($p, $d); break;
    }
    $t = ['simple'=>'Simple','basic'=>'Basic','advanced'=>'Avancé'];
    zipAndFinish($d, "Composant {$t[$p['structureType']]} <strong>$d</strong> généré");
}

/* ────────── COMPOSANT SIMPLE ────────── */
function generateSimpleComp($p, $d) {
    $a = $p['alias']; $m = meta($p);

    mkdirs(["$d","$d/admin","$d/admin/sql","$d/admin/sql/updates","$d/admin/sql/updates/mysql","$d/site"]);

    // index.html partout
    idx("$d/admin/index.html");
    idx("$d/admin/sql/index.html");
    idx("$d/admin/sql/updates/index.html");
    idx("$d/admin/sql/updates/mysql/index.html");
    idx("$d/site/index.html");

    // manifest XML
    $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension type="component" method="upgrade">
    <name>{$p['name']}</name>
    <author>{$p['author']}</author>
    <creationDate>{$m['date']}</creationDate>
    <copyright>Copyright (C) {$m['year']} {$p['author']}</copyright>
    <license>{$p['license']}</license>
    <authorEmail>{$p['authorEmail']}</authorEmail>
    <authorUrl>{$p['authorUrl']}</authorUrl>
    <version>{$p['version']}</version>
    <description>{$p['description']}</description>

    <install>
        <sql>
            <file driver="mysql" charset="utf8">sql/updates/mysql/{$p['version']}.sql</file>
        </sql>
    </install>

    <update>
        <schemas>
            <schemapath type="mysql">sql/updates/mysql</schemapath>
        </schemas>
    </update>

    <files folder="site">
        <filename>$a.php</filename>
        <filename>index.html</filename>
    </files>

    <administration>
        <menu>COM_{$m['upper']}</menu>
        <files folder="admin">
            <filename>$a.php</filename>
            <filename>index.html</filename>
            <folder>sql</folder>
        </files>
    </administration>
</extension>
XML;
    file_put_contents("$d/$a.xml", $xml);

    // admin/alias.php
    $php = <<<PHP
<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_$a
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

echo '<h1>{$p['name']}</h1>';
echo '<p>Bienvenue dans l\\'administration de {$p['name']}</p>';
PHP;
    file_put_contents("$d/admin/$a.php", $php);

    // site/alias.php
    $php = <<<PHP
<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_$a
 */
defined('_JEXEC') or die;

echo '<h1>{$p['name']}</h1>';
echo '<p>Bienvenue sur {$p['name']}!</p>';
PHP;
    file_put_contents("$d/site/$a.php", $php);

    // SQL
    file_put_contents("$d/admin/sql/updates/mysql/{$p['version']}.sql", "-- {$p['name']} v{$p['version']}\nSELECT 1;");
}

/* ────────── COMPOSANT BASIC ────────── */
function generateBasicComp($p, $d) {
    $a = $p['alias']; $m = meta($p);
    mkdirs(["$d","$d/admin","$d/admin/sql/updates/mysql","$d/site","$d/admin/language/en-GB","$d/site/language/en-GB"]);

    idx("$d/admin/index.html");
    idx("$d/admin/sql/index.html");
    idx("$d/admin/sql/updates/index.html");
    idx("$d/admin/sql/updates/mysql/index.html");
    idx("$d/site/index.html");

    $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension type="component" method="upgrade">
    <name>{$p['name']}</name>
    <author>{$p['author']}</author>
    <creationDate>{$m['date']}</creationDate>
    <copyright>Copyright (C) {$m['year']} {$p['author']}</copyright>
    <license>{$p['license']}</license>
    <authorEmail>{$p['authorEmail']}</authorEmail>
    <authorUrl>{$p['authorUrl']}</authorUrl>
    <version>{$p['version']}</version>
    <description>COM_{$m['upper']}_DESCRIPTION</description>
    <install><sql><file driver="mysql" charset="utf8">sql/updates/mysql/{$p['version']}.sql</file></sql></install>
    <update><schemas><schemapath type="mysql">sql/updates/mysql</schemapath></schemas></update>
    <files folder="site">
        <filename>$a.php</filename>
        <filename>index.html</filename>
    </files>
    <languages folder="site/language">
        <language tag="en-GB">en-GB/com_$a.ini</language>
    </languages>
    <administration>
        <menu>COM_{$m['upper']}</menu>
        <files folder="admin">
            <filename>$a.php</filename>
            <filename>index.html</filename>
            <folder>sql</folder>
        </files>
        <languages folder="admin/language">
            <language tag="en-GB">en-GB/com_$a.ini</language>
            <language tag="en-GB">en-GB/com_$a.sys.ini</language>
        </languages>
    </administration>
</extension>
XML;
    file_put_contents("$d/$a.xml", $xml);

    $c = "<?php\ndefined('_JEXEC') or die;\nuse Joomla\\CMS\\Factory;\nuse Joomla\\CMS\\Language\\Text;\n\n";
    $c.= "\$app = Factory::getApplication();\necho '<h1>' . Text::_('COM_{$m['upper']}') . '</h1>';\n";
    $c.= "echo '<div class=\"alert alert-info\"><p>' . Text::_('COM_{$m['upper']}_WELCOME') . '</p></div>';\n";
    file_put_contents("$d/admin/$a.php", $c);

    $c = "<?php\ndefined('_JEXEC') or die;\nuse Joomla\\CMS\\Factory;\nuse Joomla\\CMS\\Language\\Text;\n?>\n";
    $c.= "<div class=\"com-$a\">\n    <h1><?php echo Text::_('COM_{$m['upper']}'); ?></h1>\n    <p>Bienvenue!</p>\n</div>\n";
    file_put_contents("$d/site/$a.php", $c);

    file_put_contents("$d/admin/sql/updates/mysql/{$p['version']}.sql", "-- {$p['name']} v{$p['version']}\nSELECT 1;");

    $ini = "COM_{$m['upper']}=\"{$p['name']}\"\nCOM_{$m['upper']}_DESCRIPTION=\"{$p['description']}\"\nCOM_{$m['upper']}_WELCOME=\"Bienvenue dans {$p['name']}\"\n";
    file_put_contents("$d/admin/language/en-GB/com_$a.ini", $ini);
    file_put_contents("$d/admin/language/en-GB/com_$a.sys.ini", "COM_{$m['upper']}=\"{$p['name']}\"\nCOM_{$m['upper']}_DESCRIPTION=\"{$p['description']}\"\n");
    file_put_contents("$d/site/language/en-GB/com_$a.ini", "COM_{$m['upper']}=\"{$p['name']}\"\n");
}

/* ────────── COMPOSANT AVANCÉ ────────── */
function generateAdvancedComp($p, $d) {
    $a = $p['alias']; $m = meta($p); $v = $p['vendor']; $uc = $m['uc'];
    $ns = $v.'\\Component\\'.$uc;
    $nsEsc = '\\\\' . $v . '\\\\Component\\\\' . $uc;

    mkdirs([
        "$d","$d/admin/services","$d/admin/src/Extension","$d/admin/src/Controller",
        "$d/admin/src/View/Items","$d/admin/src/Model","$d/admin/tmpl/items",
        "$d/admin/sql/updates/mysql","$d/admin/language/en-GB","$d/admin/forms",
        "$d/site/src/Controller","$d/site/src/View/Default","$d/site/tmpl/default",
        "$d/site/language/en-GB","$d/media/css","$d/media/js",
    ]);

    // Manifest
    $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension type="component" method="upgrade">
    <name>com_$a</name>
    <author>{$p['author']}</author>
    <creationDate>{$m['date']}</creationDate>
    <copyright>Copyright (C) {$m['year']} {$p['author']}</copyright>
    <license>{$p['license']}</license>
    <authorEmail>{$p['authorEmail']}</authorEmail>
    <authorUrl>{$p['authorUrl']}</authorUrl>
    <version>{$p['version']}</version>
    <description>COM_{$m['upper']}_DESCRIPTION</description>
    <namespace path="src">$ns</namespace>
    <scriptfile>script.php</scriptfile>
    <install><sql><file driver="mysql" charset="utf8">sql/install.mysql.sql</file></sql></install>
    <uninstall><sql><file driver="mysql" charset="utf8">sql/uninstall.mysql.sql</file></sql></uninstall>
    <update><schemas><schemapath type="mysql">sql/updates/mysql</schemapath></schemas></update>
    <media destination="com_$a" folder="media"><folder>css</folder><folder>js</folder></media>
    <files folder="site"><folder>src</folder><folder>tmpl</folder></files>
    <languages folder="site/language"><language tag="en-GB">en-GB/com_$a.ini</language></languages>
    <administration>
        <menu>COM_{$m['upper']}</menu>
        <submenu><menu link="option=com_$a">COM_{$m['upper']}_ITEMS</menu></submenu>
        <files folder="admin">
            <folder>forms</folder><folder>services</folder><folder>src</folder><folder>tmpl</folder><folder>sql</folder>
        </files>
        <languages folder="admin/language">
            <language tag="en-GB">en-GB/com_$a.ini</language>
            <language tag="en-GB">en-GB/com_$a.sys.ini</language>
        </languages>
    </administration>
</extension>
XML;
    file_put_contents("$d/$a.xml", $xml);

    // script.php
    $c = "<?php\ndefined('_JEXEC') or die;\n\nuse Joomla\\CMS\\Installer\\InstallerAdapter;\nuse Joomla\\CMS\\Log\\Log;\n\n";
    $c.= "class Com_{$uc}InstallerScript\n{\n";
    $c.= "    public function install(InstallerAdapter \$adapter): bool { return true; }\n";
    $c.= "    public function uninstall(InstallerAdapter \$adapter): bool { return true; }\n";
    $c.= "    public function update(InstallerAdapter \$adapter): bool { return true; }\n\n";
    $c.= "    public function preflight(string \$route, InstallerAdapter \$adapter): bool\n    {\n";
    $c.= "        if (version_compare(PHP_VERSION, '8.1', '<')) {\n            Log::add('PHP 8.1+ requis', Log::WARNING, 'jerror');\n            return false;\n        }\n        return true;\n    }\n\n";
    $c.= "    public function postflight(string \$route, InstallerAdapter \$adapter): bool { return true; }\n}\n";
    file_put_contents("$d/script.php", $c);

    // services/provider.php
    $c = "<?php\ndefined('_JEXEC') or die;\n\nuse Joomla\\CMS\\Dispatcher\\ComponentDispatcherFactoryInterface;\nuse Joomla\\CMS\\Extension\\ComponentInterface;\n";
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
    $c.= "    public function display(\$cachable = false, \$urlparams = []): static\n    { return parent::display(\$cachable, \$urlparams); }\n}\n";
    file_put_contents("$d/admin/src/Controller/DisplayController.php", $c);

    // Admin Model
    $c = "<?php\nnamespace $ns\\Administrator\\Model;\n\ndefined('_JEXEC') or die;\n\nuse Joomla\\CMS\\MVC\\Model\\ListModel;\nuse Joomla\\Database\\QueryInterface;\n\n";
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

    // Admin Template
    $c = "<?php\ndefined('_JEXEC') or die;\nuse Joomla\\CMS\\Language\\Text;\n?>\n<div class=\"com-{$a}-admin\">\n";
    $c.= "    <h2><?php echo Text::_('COM_{$m['upper']}'); ?></h2>\n";
    $c.= "    <?php if (!empty(\$this->items)) : ?>\n    <table class=\"table table-striped\">\n        <thead><tr><th>ID</th><th>Title</th><th>State</th></tr></thead>\n        <tbody>\n";
    $c.= "        <?php foreach (\$this->items as \$item) : ?>\n            <tr><td><?php echo \$item->id; ?></td><td><?php echo htmlspecialchars(\$item->title ?? ''); ?></td><td><?php echo \$item->state; ?></td></tr>\n";
    $c.= "        <?php endforeach; ?>\n        </tbody>\n    </table>\n";
    $c.= "    <?php else : ?><div class=\"alert alert-info\"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></div><?php endif; ?>\n</div>\n";
    file_put_contents("$d/admin/tmpl/items/default.php", $c);

    // Site Controller
    $c = "<?php\nnamespace $ns\\Site\\Controller;\n\ndefined('_JEXEC') or die;\n\nuse Joomla\\CMS\\MVC\\Controller\\BaseController;\n\n";
    $c.= "class DisplayController extends BaseController\n{\n    protected \$default_view = 'default';\n\n";
    $c.= "    public function display(\$cachable = false, \$urlparams = []): static\n    { return parent::display(\$cachable, \$urlparams); }\n}\n";
    file_put_contents("$d/site/src/Controller/DisplayController.php", $c);

    // Site View
    $c = "<?php\nnamespace $ns\\Site\\View\\Default;\n\ndefined('_JEXEC') or die;\n\nuse Joomla\\CMS\\MVC\\View\\HtmlView as BaseHtmlView;\n\n";
    $c.= "class HtmlView extends BaseHtmlView\n{\n    protected string \$message = '';\n\n";
    $c.= "    public function display(\$tpl = null): void\n    {\n        \$this->message = '{$p['name']}';\n        parent::display(\$tpl);\n    }\n}\n";
    file_put_contents("$d/site/src/View/Default/HtmlView.php", $c);

    // Site Template
    $c = "<?php\ndefined('_JEXEC') or die;\nuse Joomla\\CMS\\Factory;\n\$wa = Factory::getApplication()->getDocument()->getWebAssetManager();\n";
    $c.= "\$wa->useStyle('com_$a.style');\n?>\n<div class=\"com-$a\">\n    <h1><?php echo htmlspecialchars(\$this->message); ?></h1>\n</div>\n";
    file_put_contents("$d/site/tmpl/default/default.php", $c);

    // SQL
    $sql = "CREATE TABLE IF NOT EXISTS `#__{$a}` (\n    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,\n    `title` VARCHAR(255) NOT NULL DEFAULT '',\n";
    $sql.= "    `alias` VARCHAR(400) NOT NULL DEFAULT '',\n    `state` TINYINT(1) NOT NULL DEFAULT 0,\n    `ordering` INT NOT NULL DEFAULT 0,\n";
    $sql.= "    `created` DATETIME NOT NULL,\n    `modified` DATETIME NOT NULL,\n    `created_by` INT UNSIGNED NOT NULL DEFAULT 0,\n";
    $sql.= "    PRIMARY KEY (`id`),\n    KEY `idx_state` (`state`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n";
    file_put_contents("$d/admin/sql/install.mysql.sql", $sql);
    file_put_contents("$d/admin/sql/uninstall.mysql.sql", "DROP TABLE IF EXISTS `#__{$a}`;\n");
    file_put_contents("$d/admin/sql/updates/mysql/{$p['version']}.sql", "-- {$p['name']} {$p['version']}\n");

    // Forms
    file_put_contents("$d/admin/forms/filter_items.xml", "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<form>\n    <fields name=\"filter\">\n        <field name=\"search\" type=\"text\" label=\"JSEARCH_FILTER\" hint=\"JSEARCH_FILTER\" />\n    </fields>\n</form>\n");

    // Access
    $acc = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<access component=\"com_$a\">\n    <section name=\"component\">\n";
    $acc.= "        <action name=\"core.admin\" title=\"JACTION_ADMIN\" />\n        <action name=\"core.options\" title=\"JACTION_OPTIONS\" />\n";
    $acc.= "        <action name=\"core.manage\" title=\"JACTION_MANAGE\" />\n        <action name=\"core.create\" title=\"JACTION_CREATE\" />\n";
    $acc.= "        <action name=\"core.delete\" title=\"JACTION_DELETE\" />\n        <action name=\"core.edit\" title=\"JACTION_EDIT\" />\n";
    $acc.= "        <action name=\"core.edit.state\" title=\"JACTION_EDITSTATE\" />\n    </section>\n</access>\n";
    file_put_contents("$d/admin/access.xml", $acc);

    // Config
    $cfg = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<config>\n    <fieldset name=\"component\" label=\"COM_{$m['upper']}_CONFIG\">\n";
    $cfg.= "        <field name=\"show_title\" type=\"radio\" label=\"Show Title\" layout=\"joomla.form.field.radio.switcher\" default=\"1\">\n";
    $cfg.= "            <option value=\"0\">JNO</option>\n            <option value=\"1\">JYES</option>\n        </field>\n    </fieldset>\n</config>\n";
    file_put_contents("$d/admin/config.xml", $cfg);

    // Languages
    $ini = "COM_{$m['upper']}=\"{$p['name']}\"\nCOM_{$m['upper']}_DESCRIPTION=\"{$p['description']}\"\n";
    $ini.= "COM_{$m['upper']}_WELCOME=\"Bienvenue dans {$p['name']}\"\nCOM_{$m['upper']}_ITEMS=\"Items\"\nCOM_{$m['upper']}_CONFIG=\"Configuration\"\n";
    file_put_contents("$d/admin/language/en-GB/com_$a.ini", $ini);
    file_put_contents("$d/admin/language/en-GB/com_$a.sys.ini", "COM_{$m['upper']}=\"{$p['name']}\"\nCOM_{$m['upper']}_DESCRIPTION=\"{$p['description']}\"\n");
    file_put_contents("$d/site/language/en-GB/com_$a.ini", "COM_{$m['upper']}=\"{$p['name']}\"\n");

    // Media
    file_put_contents("$d/media/css/style.css", ".com-$a { padding: 1rem; }\n.com-$a h1 { margin-bottom: 1rem; }\n");
    file_put_contents("$d/media/js/script.js",  "document.addEventListener('DOMContentLoaded', () => { console.log('com_$a loaded'); });\n");
    $wa = "{\n    \"\$schema\": \"https://developer.joomla.org/schemas/json-schema/web_assets.json\",\n    \"name\": \"com_$a\",\n    \"version\": \"{$p['version']}\",\n";
    $wa.= "    \"assets\": [{ \"name\": \"com_$a.style\", \"type\": \"style\", \"uri\": \"com_$a/css/style.css\" }]\n}\n";
    file_put_contents("$d/media/joomla.asset.json", $wa);
}

/* ================================================================
   MODULE
   ================================================================ */
function generateModule($p) {
    $d = "mod_{$p['alias']}";
    switch ($p['structureType']) {
        case 'simple':   generateSimpleMod($p, $d); break;
        case 'basic':    generateBasicMod($p, $d); break;
        default:         generateAdvancedMod($p, $d); break;
    }
    $t = ['simple'=>'Simple','basic'=>'Basic','advanced'=>'Avancé'];
    zipAndFinish($d, "Module {$t[$p['structureType']]} <strong>$d</strong> généré");
}

/* ────────── MODULE SIMPLE ────────── */
function generateSimpleMod($p, $d) {
    $a = $p['alias']; $m = meta($p);
    mkdirs(["$d","$d/tmpl"]);

    idx("$d/index.html");
    idx("$d/tmpl/index.html");

    $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension type="module" client="{$p['moduleClient']}" method="upgrade">
    <name>mod_$a</name>
    <author>{$p['author']}</author>
    <creationDate>{$m['date']}</creationDate>
    <copyright>Copyright (C) {$m['year']} {$p['author']}</copyright>
    <license>{$p['license']}</license>
    <authorEmail>{$p['authorEmail']}</authorEmail>
    <authorUrl>{$p['authorUrl']}</authorUrl>
    <version>{$p['version']}</version>
    <description>{$p['description']}</description>
    <files>
        <filename module="mod_$a">mod_$a.php</filename>
        <filename>index.html</filename>
        <folder>tmpl</folder>
    </files>
    <config>
        <fields name="params">
            <fieldset name="basic">
                <field name="text" type="text" label="Texte" default="{$p['name']}" />
            </fieldset>
        </fields>
    </config>
</extension>
XML;
    file_put_contents("$d/mod_$a.xml", $xml);

    $c = "<?php\ndefined('_JEXEC') or die;\n\nuse Joomla\\CMS\\Helper\\ModuleHelper;\n\n";
    $c.= "\$text = \$params->get('text', '{$p['name']}');\n\n";
    $c.= "require ModuleHelper::getLayoutPath('mod_$a', \$params->get('layout', 'default'));\n";
    file_put_contents("$d/mod_$a.php", $c);

    $c = "<?php\ndefined('_JEXEC') or die;\n?>\n<div class=\"mod-$a\">\n    <p><?php echo htmlspecialchars(\$text); ?></p>\n</div>\n";
    file_put_contents("$d/tmpl/default.php", $c);
}

/* ────────── MODULE BASIC ────────── */
function generateBasicMod($p, $d) {
    $a = $p['alias']; $m = meta($p);
    mkdirs(["$d","$d/tmpl","$d/language/en-GB"]);

    idx("$d/index.html");
    idx("$d/tmpl/index.html");

    $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension type="module" client="{$p['moduleClient']}" method="upgrade">
    <name>mod_$a</name>
    <author>{$p['author']}</author>
    <creationDate>{$m['date']}</creationDate>
    <copyright>Copyright (C) {$m['year']} {$p['author']}</copyright>
    <license>{$p['license']}</license>
    <authorEmail>{$p['authorEmail']}</authorEmail>
    <authorUrl>{$p['authorUrl']}</authorUrl>
    <version>{$p['version']}</version>
    <description>MOD_{$m['upper']}_DESCRIPTION</description>
    <files>
        <filename module="mod_$a">mod_$a.php</filename>
        <filename>helper.php</filename>
        <filename>index.html</filename>
        <folder>tmpl</folder>
    </files>
    <languages folder="language">
        <language tag="en-GB">en-GB/mod_$a.ini</language>
        <language tag="en-GB">en-GB/mod_$a.sys.ini</language>
    </languages>
    <config>
        <fields name="params">
            <fieldset name="basic">
                <field name="display_text" type="text" label="MOD_{$m['upper']}_DISPLAY_TEXT" default="{$p['name']}" />
                <field name="show_date" type="radio" label="MOD_{$m['upper']}_SHOW_DATE" layout="joomla.form.field.radio.switcher" default="1">
                    <option value="0">JNO</option><option value="1">JYES</option>
                </field>
            </fieldset>
            <fieldset name="advanced">
                <field name="layout" type="modulelayout" label="JFIELD_ALT_LAYOUT_LABEL" />
                <field name="moduleclass_sfx" type="textarea" label="COM_MODULES_FIELD_MODULECLASS_SFX_LABEL" rows="3" />
            </fieldset>
        </fields>
    </config>
</extension>
XML;
    file_put_contents("$d/mod_$a.xml", $xml);

    $c = "<?php\ndefined('_JEXEC') or die;\n\nuse Joomla\\CMS\\Helper\\ModuleHelper;\n\nrequire_once __DIR__ . '/helper.php';\n\n";
    $c.= "\$helper = new Mod{$m['uc']}Helper(\$params);\n\$displayText = \$helper->getMessage();\n\$showDate = \$params->get('show_date', 1);\n\n";
    $c.= "require ModuleHelper::getLayoutPath('mod_$a', \$params->get('layout', 'default'));\n";
    file_put_contents("$d/mod_$a.php", $c);

    $c = "<?php\ndefined('_JEXEC') or die;\n\nuse Joomla\\CMS\\Factory;\n\nclass Mod{$m['uc']}Helper\n{\n";
    $c.= "    protected \$params;\n    public function __construct(\$params) { \$this->params = \$params; }\n\n";
    $c.= "    public function getMessage(): string { return \$this->params->get('display_text', '{$p['name']}'); }\n";
    $c.= "    public function getDate(): string { return Factory::getDate()->format('d/m/Y H:i'); }\n}\n";
    file_put_contents("$d/helper.php", $c);

    $c = "<?php\ndefined('_JEXEC') or die;\n?>\n<div class=\"mod-$a <?php echo \$params->get('moduleclass_sfx', ''); ?>\">\n";
    $c.= "    <p><?php echo htmlspecialchars(\$displayText); ?></p>\n";
    $c.= "    <?php if (\$showDate) : ?><small><?php echo \$helper->getDate(); ?></small><?php endif; ?>\n</div>\n";
    file_put_contents("$d/tmpl/default.php", $c);

    file_put_contents("$d/language/en-GB/mod_$a.ini", "MOD_{$m['upper']}_DISPLAY_TEXT=\"Texte à afficher\"\nMOD_{$m['upper']}_SHOW_DATE=\"Afficher la date\"\n");
    file_put_contents("$d/language/en-GB/mod_$a.sys.ini","MOD_{$m['upper']}=\"{$p['name']}\"\nMOD_{$m['upper']}_DESCRIPTION=\"{$p['description']}\"\n");
}

/* ────────── MODULE AVANCÉ ────────── */
function generateAdvancedMod($p, $d) {
    $a = $p['alias']; $m = meta($p); $v = $p['vendor']; $uc = $m['uc'];
    $ns = $v.'\\Module\\'.$uc;
    $clientNs = ($p['moduleClient'] === 'administrator') ? 'Administrator' : 'Site';
    $nsEsc = '\\\\' . $v . '\\\\Module\\\\' . $uc;

    mkdirs(["$d","$d/services","$d/src/Dispatcher","$d/src/Helper","$d/tmpl","$d/language/en-GB"]);

    $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension type="module" client="{$p['moduleClient']}" method="upgrade">
    <name>mod_$a</name>
    <author>{$p['author']}</author>
    <creationDate>{$m['date']}</creationDate>
    <copyright>Copyright (C) {$m['year']} {$p['author']}</copyright>
    <license>{$p['license']}</license>
    <authorEmail>{$p['authorEmail']}</authorEmail>
    <authorUrl>{$p['authorUrl']}</authorUrl>
    <version>{$p['version']}</version>
    <description>MOD_{$m['upper']}_DESCRIPTION</description>
    <namespace path="src">$ns</namespace>
    <files>
        <folder module="mod_$a">services</folder>
        <folder>src</folder>
        <folder>tmpl</folder>
    </files>
    <languages folder="language">
        <language tag="en-GB">en-GB/mod_$a.ini</language>
        <language tag="en-GB">en-GB/mod_$a.sys.ini</language>
    </languages>
    <config>
        <fields name="params">
            <fieldset name="basic">
                <field name="display_text" type="text" label="MOD_{$m['upper']}_DISPLAY_TEXT" default="{$p['name']}" />
                <field name="items_count" type="number" label="MOD_{$m['upper']}_ITEMS_COUNT" default="5" min="1" max="100" />
            </fieldset>
            <fieldset name="advanced">
                <field name="layout" type="modulelayout" label="JFIELD_ALT_LAYOUT_LABEL" />
                <field name="moduleclass_sfx" type="textarea" label="COM_MODULES_FIELD_MODULECLASS_SFX_LABEL" rows="3" />
            </fieldset>
        </fields>
    </config>
</extension>
XML;
    file_put_contents("$d/mod_$a.xml", $xml);

    $c = "<?php\ndefined('_JEXEC') or die;\n\nuse Joomla\\CMS\\Extension\\Service\\Provider\\HelperFactory;\nuse Joomla\\CMS\\Extension\\Service\\Provider\\Module;\n";
    $c.= "use Joomla\\CMS\\Extension\\Service\\Provider\\ModuleDispatcherFactory;\nuse Joomla\\DI\\Container;\nuse Joomla\\DI\\ServiceProviderInterface;\n\n";
    $c.= "return new class implements ServiceProviderInterface {\n    public function register(Container \$container): void\n    {\n";
    $c.= "        \$container->registerServiceProvider(new ModuleDispatcherFactory('$nsEsc'));\n";
    $c.= "        \$container->registerServiceProvider(new HelperFactory('$nsEsc\\\\$clientNs\\\\Helper'));\n";
    $c.= "        \$container->registerServiceProvider(new Module());\n    }\n};\n";
    file_put_contents("$d/services/provider.php", $c);

    $c = "<?php\nnamespace $ns\\$clientNs\\Dispatcher;\n\ndefined('_JEXEC') or die;\n\n";
    $c.= "use Joomla\\CMS\\Dispatcher\\AbstractModuleDispatcher;\nuse Joomla\\CMS\\Helper\\HelperFactoryAwareInterface;\nuse Joomla\\CMS\\Helper\\HelperFactoryAwareTrait;\n\n";
    $c.= "class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface\n{\n    use HelperFactoryAwareTrait;\n\n";
    $c.= "    protected function getLayoutData(): array\n    {\n        \$data = parent::getLayoutData();\n";
    $c.= "        \$helper = \$this->getHelperFactory()->getHelper('{$uc}Helper');\n";
    $c.= "        \$data['message'] = \$helper->getMessage(\$data['params']);\n        \$data['items'] = \$helper->getItems(\$data['params']);\n        return \$data;\n    }\n}\n";
    file_put_contents("$d/src/Dispatcher/Dispatcher.php", $c);

    $c = "<?php\nnamespace $ns\\$clientNs\\Helper;\n\ndefined('_JEXEC') or die;\n\nuse Joomla\\Registry\\Registry;\n\n";
    $c.= "class {$uc}Helper\n{\n";
    $c.= "    public function getMessage(Registry \$params): string { return \$params->get('display_text', '{$p['name']}'); }\n\n";
    $c.= "    public function getItems(Registry \$params): array\n    {\n        \$count = (int) \$params->get('items_count', 5);\n";
    $c.= "        return array_map(fn(\$i) => (object)['id' => \$i, 'title' => 'Item ' . \$i], range(1, \$count));\n    }\n}\n";
    file_put_contents("$d/src/Helper/{$uc}Helper.php", $c);

    $c = "<?php\ndefined('_JEXEC') or die;\n\$message = \$message ?? '';\n\$items = \$items ?? [];\n\$params = \$params ?? new \\Joomla\\Registry\\Registry();\n?>\n";
    $c.= "<div class=\"mod-$a <?php echo \$params->get('moduleclass_sfx', ''); ?>\">\n    <p><strong><?php echo htmlspecialchars(\$message); ?></strong></p>\n";
    $c.= "    <?php if (!empty(\$items)) : ?>\n    <ul><?php foreach (\$items as \$item) : ?><li><?php echo htmlspecialchars(\$item->title); ?></li><?php endforeach; ?></ul>\n    <?php endif; ?>\n</div>\n";
    file_put_contents("$d/tmpl/default.php", $c);

    file_put_contents("$d/language/en-GB/mod_$a.ini", "MOD_{$m['upper']}_DISPLAY_TEXT=\"Texte à afficher\"\nMOD_{$m['upper']}_ITEMS_COUNT=\"Nombre d'éléments\"\n");
    file_put_contents("$d/language/en-GB/mod_$a.sys.ini","MOD_{$m['upper']}=\"{$p['name']}\"\nMOD_{$m['upper']}_DESCRIPTION=\"{$p['description']}\"\n");
}

/* ================================================================
   PLUGIN
   ================================================================ */
function generatePlugin($p) {
    $d = "plg_{$p['pluginGroup']}_{$p['alias']}";
    switch ($p['structureType']) {
        case 'simple':   generateSimplePlg($p, $d); break;
        case 'basic':    generateBasicPlg($p, $d); break;
        default:         generateAdvancedPlg($p, $d); break;
    }
    $t = ['simple'=>'Simple','basic'=>'Basic','advanced'=>'Avancé'];
    zipAndFinish($d, "Plugin {$t[$p['structureType']]} <strong>$d</strong> ({$p['pluginGroup']}) généré");
}

/* ────────── PLUGIN SIMPLE ────────── */
function generateSimplePlg($p, $d) {
    $a = $p['alias']; $g = $p['pluginGroup']; $m = meta($p);
    $ucG = ucfirst($g); $ucA = $m['uc'];
    $events = pluginEvents($g);
    mkdirs([$d]);

    idx("$d/index.html");

    $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension type="plugin" group="$g" method="upgrade">
    <name>plg_{$g}_$a</name>
    <author>{$p['author']}</author>
    <creationDate>{$m['date']}</creationDate>
    <copyright>Copyright (C) {$m['year']} {$p['author']}</copyright>
    <license>{$p['license']}</license>
    <authorEmail>{$p['authorEmail']}</authorEmail>
    <authorUrl>{$p['authorUrl']}</authorUrl>
    <version>{$p['version']}</version>
    <description>{$p['description']}</description>
    <files>
        <filename plugin="$a">$a.php</filename>
        <filename>index.html</filename>
    </files>
</extension>
XML;
    file_put_contents("$d/$a.xml", $xml);

    $ev = $events[0] ?? 'onContentPrepare';
    $c = "<?php\n/**\n * @package     Joomla.Plugin\n * @subpackage  plg_{$g}_$a\n */\ndefined('_JEXEC') or die;\n\n";
    $c.= "use Joomla\\CMS\\Plugin\\CMSPlugin;\n\nclass Plg{$ucG}{$ucA} extends CMSPlugin\n{\n";
    $c.= "    public function $ev()\n    {\n        // TODO: votre code ici\n\n        return true;\n    }\n}\n";
    file_put_contents("$d/$a.php", $c);
}

/* ────────── PLUGIN BASIC ────────── */
function generateBasicPlg($p, $d) {
    $a = $p['alias']; $g = $p['pluginGroup']; $m = meta($p);
    $events = pluginEvents($g);
    $ucG = ucfirst($g); $ucA = $m['uc'];
    mkdirs(["$d","$d/language/en-GB"]);

    idx("$d/index.html");

    $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension type="plugin" group="$g" method="upgrade">
    <name>plg_{$g}_$a</name>
    <author>{$p['author']}</author>
    <creationDate>{$m['date']}</creationDate>
    <copyright>Copyright (C) {$m['year']} {$p['author']}</copyright>
    <license>{$p['license']}</license>
    <authorEmail>{$p['authorEmail']}</authorEmail>
    <authorUrl>{$p['authorUrl']}</authorUrl>
    <version>{$p['version']}</version>
    <description>PLG_{$m['upper']}_DESCRIPTION</description>
    <files>
        <filename plugin="$a">$a.php</filename>
        <filename>index.html</filename>
    </files>
    <languages folder="language">
        <language tag="en-GB">en-GB/plg_{$g}_$a.ini</language>
        <language tag="en-GB">en-GB/plg_{$g}_$a.sys.ini</language>
    </languages>
    <config>
        <fields name="params">
            <fieldset name="basic">
                <field name="enabled_feature" type="radio" label="PLG_{$m['upper']}_ENABLE" layout="joomla.form.field.radio.switcher" default="1">
                    <option value="0">JNO</option><option value="1">JYES</option>
                </field>
            </fieldset>
        </fields>
    </config>
</extension>
XML;
    file_put_contents("$d/$a.xml", $xml);

    $c = "<?php\ndefined('_JEXEC') or die;\n\nuse Joomla\\CMS\\Plugin\\CMSPlugin;\n\nclass Plg{$ucG}{$ucA} extends CMSPlugin\n{\n    protected \$autoloadLanguage = true;\n\n";
    foreach ($events as $ev) {
        $c.= "    public function $ev()\n    {\n        if (!\$this->params->get('enabled_feature', 1)) return true;\n\n        // TODO: $ev\n\n        return true;\n    }\n\n";
    }
    $c = rtrim($c) . "\n}\n";
    file_put_contents("$d/$a.php", $c);

    file_put_contents("$d/language/en-GB/plg_{$g}_$a.ini", "PLG_{$m['upper']}_ENABLE=\"Activer la fonctionnalité\"\n");
    file_put_contents("$d/language/en-GB/plg_{$g}_$a.sys.ini","PLG_".strtoupper($g.'_'.$a)."=\"{$p['name']}\"\nPLG_{$m['upper']}_DESCRIPTION=\"{$p['description']}\"\n");
}

/* ────────── PLUGIN AVANCÉ ────────── */
function generateAdvancedPlg($p, $d) {
    $a = $p['alias']; $g = $p['pluginGroup']; $m = meta($p); $v = $p['vendor']; $uc = $m['uc'];
    $ucG = ucfirst($g);
    $ns = $v.'\\Plugin\\'.$ucG.'\\'.$uc;
    $events = pluginEvents($g);
    mkdirs(["$d","$d/services","$d/src/Extension","$d/language/en-GB"]);

    $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<extension type="plugin" group="$g" method="upgrade">
    <name>plg_{$g}_$a</name>
    <author>{$p['author']}</author>
    <creationDate>{$m['date']}</creationDate>
    <copyright>Copyright (C) {$m['year']} {$p['author']}</copyright>
    <license>{$p['license']}</license>
    <authorEmail>{$p['authorEmail']}</authorEmail>
    <authorUrl>{$p['authorUrl']}</authorUrl>
    <version>{$p['version']}</version>
    <description>PLG_{$m['upper']}_DESCRIPTION</description>
    <namespace path="src">$ns</namespace>
    <files>
        <folder plugin="$a">services</folder>
        <folder>src</folder>
    </files>
    <languages folder="language">
        <language tag="en-GB">en-GB/plg_{$g}_$a.ini</language>
        <language tag="en-GB">en-GB/plg_{$g}_$a.sys.ini</language>
    </languages>
    <config>
        <fields name="params">
            <fieldset name="basic">
                <field name="enabled_feature" type="radio" label="PLG_{$m['upper']}_ENABLE" layout="joomla.form.field.radio.switcher" default="1">
                    <option value="0">JNO</option><option value="1">JYES</option>
                </field>
            </fieldset>
        </fields>
    </config>
</extension>
XML;
    file_put_contents("$d/$a.xml", $xml);

    $c = "<?php\ndefined('_JEXEC') or die;\n\nuse Joomla\\CMS\\Extension\\PluginInterface;\nuse Joomla\\CMS\\Factory;\nuse Joomla\\CMS\\Plugin\\PluginHelper;\n";
    $c.= "use Joomla\\DI\\Container;\nuse Joomla\\DI\\ServiceProviderInterface;\nuse Joomla\\Event\\DispatcherInterface;\nuse $ns\\Extension\\$uc;\n\n";
    $c.= "return new class implements ServiceProviderInterface {\n    public function register(Container \$container): void\n    {\n";
    $c.= "        \$container->set(PluginInterface::class, function (Container \$container) {\n";
    $c.= "            \$dispatcher = \$container->get(DispatcherInterface::class);\n";
    $c.= "            \$plugin = new $uc(\$dispatcher, (array) PluginHelper::getPlugin('$g', '$a'));\n";
    $c.= "            \$plugin->setApplication(Factory::getApplication());\n            return \$plugin;\n        });\n    }\n};\n";
    file_put_contents("$d/services/provider.php", $c);

    $c = "<?php\nnamespace $ns\\Extension;\n\ndefined('_JEXEC') or die;\n\nuse Joomla\\CMS\\Plugin\\CMSPlugin;\nuse Joomla\\Event\\SubscriberInterface;\n\n";
    $c.= "final class $uc extends CMSPlugin implements SubscriberInterface\n{\n    public static function getSubscribedEvents(): array\n    {\n        return [\n";
    foreach ($events as $ev) { $c.= "            '$ev' => 'handle".ucfirst($ev)."',\n"; }
    $c.= "        ];\n    }\n\n";
    foreach ($events as $ev) {
        $c.= "    public function handle".ucfirst($ev)."(\$event): void\n    {\n";
        $c.= "        if (!\$this->params->get('enabled_feature', 1)) return;\n\n        // TODO: $ev\n    }\n\n";
    }
    $c = rtrim($c) . "\n}\n";
    file_put_contents("$d/src/Extension/$uc.php", $c);

    file_put_contents("$d/language/en-GB/plg_{$g}_$a.ini", "PLG_{$m['upper']}_ENABLE=\"Activer la fonctionnalité\"\n");
    file_put_contents("$d/language/en-GB/plg_{$g}_$a.sys.ini","PLG_".strtoupper($g.'_'.$a)."=\"{$p['name']}\"\nPLG_{$m['upper']}_DESCRIPTION=\"{$p['description']}\"\n");
}

$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Générateur d'Extensions Joomla 5</title>
<style>
:root{--primary:#1e3c72;--primary-light:#2a5298;--accent:#00b4d8;--success:#28a745;--danger:#dc3545;--warning:#ffc107;--border:#e0e0e0;--text:#333;--muted:#888;--radius:12px}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:linear-gradient(160deg,var(--primary),var(--primary-light),#1a1a2e);min-height:100vh;padding:20px}
.container{max-width:960px;margin:0 auto;background:#fff;border-radius:20px;box-shadow:0 25px 80px rgba(0,0,0,.35);overflow:hidden}
.hero{background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;padding:40px 40px 30px;text-align:center;position:relative;overflow:hidden}
.hero::after{content:'';position:absolute;top:-50%;right:-50%;width:200%;height:200%;background:radial-gradient(circle,rgba(255,255,255,.05) 0%,transparent 70%);animation:heroFloat 6s ease-in-out infinite}
@keyframes heroFloat{0%,100%{transform:translate(0,0)}50%{transform:translate(-30px,20px)}}
.hero h1{font-size:2.4em;margin-bottom:8px;position:relative;z-index:1}
.hero p{opacity:.85;font-size:1.15em;position:relative;z-index:1}
.hbadge{display:inline-block;background:rgba(255,255,255,.2);backdrop-filter:blur(10px);padding:4px 14px;border-radius:20px;font-size:.8em;margin-top:10px;position:relative;z-index:1}
.body{padding:40px}
.alert{padding:18px 22px;margin-bottom:25px;border-radius:var(--radius);font-size:.95em}
.alert-success{background:linear-gradient(135deg,#d4edda,#c3e6cb);color:#155724;border-left:5px solid var(--success)}
.alert-error{background:linear-gradient(135deg,#f8d7da,#f5c6cb);color:#721c24;border-left:5px solid var(--danger)}
.alert ul{margin:8px 0 0 18px}
.download-link{display:inline-block;margin-top:10px;padding:12px 24px;background:var(--success);color:#fff!important;text-decoration:none;border-radius:8px;font-weight:700;transition:all .2s}
.download-link:hover{background:#218838;transform:translateY(-1px)}
.ext-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:30px}
.ext-card{position:relative}
.ext-card input{position:absolute;opacity:0;pointer-events:none}
.ext-card label{display:flex;flex-direction:column;align-items:center;padding:28px 16px;border:3px solid var(--border);border-radius:var(--radius);cursor:pointer;transition:all .3s;background:#fff;text-align:center}
.ext-card label:hover{border-color:var(--accent);box-shadow:0 5px 20px rgba(0,180,216,.15)}
.ext-card input:checked+label{border-color:var(--primary);background:linear-gradient(135deg,#e8f0fe,#dce8fc);transform:scale(1.03);box-shadow:0 8px 25px rgba(30,60,114,.15)}
.ext-card .icon{font-size:2.8em;margin-bottom:10px;transition:transform .3s}
.ext-card input:checked+label .icon{transform:scale(1.15)}
.ext-card .title{font-weight:700;font-size:1.15em;color:var(--text)}
.ext-card .desc{font-size:.8em;color:var(--muted);margin-top:4px}
.section{margin-bottom:28px}
.section-title{display:flex;align-items:center;gap:8px;font-size:1.1em;font-weight:700;color:var(--primary);margin-bottom:16px;padding-bottom:8px;border-bottom:2px solid var(--border)}
.section-title .emoji{font-size:1.3em}
.struct-toggle{display:flex;gap:14px;margin-bottom:20px}
.struct-opt{flex:1;position:relative}
.struct-opt input{position:absolute;opacity:0;pointer-events:none}
.struct-opt label{display:block;padding:16px 10px;border:2px solid var(--border);border-radius:var(--radius);cursor:pointer;transition:all .3s;text-align:center}
.struct-opt label:hover{border-color:var(--accent)}
.struct-opt input:checked+label{border-color:var(--primary);background:linear-gradient(135deg,#e8f0fe,#dce8fc)}
.struct-opt .icon{font-size:1.6em;margin-bottom:4px}
.struct-opt .title{font-weight:700;color:var(--text);font-size:.95em}
.struct-opt .desc{font-size:.72em;color:var(--muted);margin-top:2px;line-height:1.3}
.file-count{display:inline-block;background:var(--primary);color:#fff;padding:1px 8px;border-radius:10px;font-size:.7em;margin-top:4px}
.cond-fields{display:none;animation:fadeIn .3s ease}
.cond-fields.active{display:block}
@keyframes fadeIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
.cond-fields .inner{background:#f8f9fa;border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:20px}
.preview{background:#1a1a2e;color:#d4d4d4;padding:18px;border-radius:var(--radius);font-family:'JetBrains Mono',Consolas,Monaco,monospace;font-size:.82em;line-height:1.7;max-height:300px;overflow-y:auto;margin-top:12px;display:none;animation:fadeIn .3s;border:1px solid rgba(255,255,255,.1)}
.preview.active{display:block}
.preview .folder{color:#569cd6}.preview .file{color:#9cdcfe}.preview .special{color:#dcdcaa}.preview .dim{color:#666}
.ns-badge{display:inline-block;background:var(--primary);color:#fff;padding:4px 12px;border-radius:6px;font-family:monospace;font-size:.85em;margin-top:6px;word-break:break-all}
.simple-notice{background:linear-gradient(135deg,#fff3cd,#ffeeba);border:1px solid var(--warning);border-radius:var(--radius);padding:14px 18px;margin-bottom:20px;font-size:.9em;color:#856404;display:none;align-items:center;gap:10px}
.simple-notice.active{display:flex}
.simple-notice .sn-icon{font-size:1.5em}
.form-group{margin-bottom:18px}
.form-group>label{display:block;margin-bottom:5px;font-weight:600;color:var(--text);font-size:.95em}
.required{color:var(--danger)}
input[type=text],input[type=email],input[type=url],input[type=number],select,textarea{width:100%;padding:11px 14px;border:2px solid var(--border);border-radius:8px;font-size:.95em;transition:all .25s;font-family:inherit}
input:focus,textarea:focus,select:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(42,82,152,.1)}
textarea{resize:vertical;min-height:70px}
select{cursor:pointer;appearance:none;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23888' d='M6 8L1 3h10z'/%3E%3C/svg%3E") no-repeat right 12px center #fff}
.form-help{font-size:.78em;color:var(--muted);margin-top:4px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px}
.btn{width:100%;padding:16px;border:none;border-radius:var(--radius);font-size:1.15em;font-weight:700;cursor:pointer;transition:all .3s;color:#fff;background:linear-gradient(135deg,var(--primary),var(--primary-light))}
.btn:hover{transform:translateY(-2px);box-shadow:0 12px 35px rgba(30,60,114,.4)}
.btn:active{transform:translateY(0)}
.footer{text-align:center;padding:25px 40px;background:#f8f9fa;color:var(--muted);font-size:.9em;border-top:1px solid var(--border)}
.footer .jbadge{display:inline-block;background:var(--primary);color:#fff;padding:3px 12px;border-radius:20px;font-size:.8em;margin-top:6px}
@media(max-width:768px){.ext-cards{grid-template-columns:1fr}.row,.row-3{grid-template-columns:1fr}.struct-toggle{flex-direction:column}.body{padding:25px}.hero{padding:30px 25px 25px}.hero h1{font-size:1.7em}}
</style>
</head>
<body>
<div class="container">
    <div class="hero">
        <h1>🚀 Générateur d'Extensions Joomla 5</h1>
        <p>Composants · Modules · Plugins</p>
        <span class="hbadge">Joomla 5.x · PHP 8.1+ · PSR-4</span>
    </div>
    
    <div class="body">
        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <?php if (!empty($errors)): ?><div class="alert alert-error"><strong>⚠️ Erreurs :</strong><ul><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div><?php endif; ?>
        
        <form method="POST" id="mainForm">
            <!-- Type -->
            <div class="section">
                <div class="section-title"><span class="emoji">📦</span> Type d'extension</div>
                <div class="ext-cards">
                    <div class="ext-card"><input type="radio" id="t-comp" name="extension_type" value="component" <?= ($_POST['extension_type']??'component')==='component'?'checked':'' ?>><label for="t-comp"><span class="icon">⚙️</span><span class="title">Composant</span><span class="desc">MVC complet, Admin + Site</span></label></div>
                    <div class="ext-card"><input type="radio" id="t-mod" name="extension_type" value="module" <?= ($_POST['extension_type']??'')==='module'?'checked':'' ?>><label for="t-mod"><span class="icon">🧩</span><span class="title">Module</span><span class="desc">Bloc positionnable</span></label></div>
                    <div class="ext-card"><input type="radio" id="t-plg" name="extension_type" value="plugin" <?= ($_POST['extension_type']??'')==='plugin'?'checked':'' ?>><label for="t-plg"><span class="icon">🔌</span><span class="title">Plugin</span><span class="desc">Hook événements</span></label></div>
                </div>
            </div>
            
            <!-- Structure -->
            <div class="section">
                <div class="section-title"><span class="emoji">🏗️</span> Structure</div>
                <div class="struct-toggle">
                    <div class="struct-opt"><input type="radio" id="s-simple" name="structure_type" value="simple" <?= ($_POST['structure_type']??'')==='simple'?'checked':'' ?>><label for="s-simple"><div class="icon">📎</div><div class="title">Simple</div><div class="desc">Minimal<br>admin/ + site/ + sql/</div><span class="file-count" id="fc-simple"></span></label></div>
                    <div class="struct-opt"><input type="radio" id="s-basic" name="structure_type" value="basic" <?= ($_POST['structure_type']??'')==='basic'?'checked':'' ?>><label for="s-basic"><div class="icon">📄</div><div class="title">Basic</div><div class="desc">Sans namespaces<br>+ Language files</div><span class="file-count" id="fc-basic"></span></label></div>
                    <div class="struct-opt"><input type="radio" id="s-adv" name="structure_type" value="advanced" <?= ($_POST['structure_type']??'advanced')==='advanced'?'checked':'' ?>><label for="s-adv"><div class="icon">🏛️</div><div class="title">Avancé</div><div class="desc">PSR-4, Services<br>Dispatcher, MVC</div><span class="file-count" id="fc-adv"></span></label></div>
                </div>
                
                <div class="simple-notice" id="simple-notice">
                    <span class="sn-icon">📎</span>
                    <div><strong>Mode Simple</strong> — Structure minimale de type <em>Hello World</em> : <code>admin/</code>, <code>site/</code>, <code>sql/</code> + fichiers <code>index.html</code> de sécurité. Idéal pour apprendre ou prototyper.</div>
                </div>
                
                <div id="ns-info" class="cond-fields">
                    <div class="inner">
                        <div class="form-group" style="margin-bottom:8px">
                            <label for="vendor_name">Vendor / Namespace racine</label>
                            <input type="text" id="vendor_name" name="vendor_name" placeholder="MyCompany" value="<?= htmlspecialchars($_POST['vendor_name']??'MyCompany') ?>">
                            <div class="form-help">Namespace PSR-4 : <span class="ns-badge" id="ns-preview">MyCompany\Component\…</span></div>
                        </div>
                    </div>
                </div>
                
                <div id="structure-preview-container"></div>
            </div>
            
            <!-- Options Module -->
            <div class="cond-fields" id="module-fields">
                <div class="inner">
                    <div class="section-title"><span class="emoji">🧩</span> Options du module</div>
                    <div class="row">
                        <div class="form-group"><label>Emplacement</label><select name="module_client"><option value="site" <?= ($_POST['module_client']??'')==='site'?'selected':'' ?>>Site (Frontend)</option><option value="administrator" <?= ($_POST['module_client']??'')==='administrator'?'selected':'' ?>>Admin (Backend)</option></select></div>
                        <div class="form-group"><label>Position par défaut</label><input type="text" name="module_position" value="<?= htmlspecialchars($_POST['module_position']??'sidebar-right') ?>"></div>
                    </div>
                </div>
            </div>
            
            <!-- Options Plugin -->
            <div class="cond-fields" id="plugin-fields">
                <div class="inner">
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
                </div>
            </div>
            
            <!-- Identité -->
            <div class="section">
                <div class="section-title"><span class="emoji">✏️</span> Identité</div>
                <div class="row">
                    <div class="form-group"><label id="lbl-name">Nom <span class="required">*</span></label><input type="text" id="extension_name" name="extension_name" placeholder="Mon Extension" required value="<?= htmlspecialchars($_POST['extension_name']??'') ?>"></div>
                    <div class="form-group"><label>Alias <span class="required">*</span></label><input type="text" id="extension_alias" name="extension_alias" placeholder="monextension" required pattern="[a-z][a-z0-9_]*" value="<?= htmlspecialchars($_POST['extension_alias']??'') ?>"><div class="form-help" id="alias-help">→ com_<strong>alias</strong></div></div>
                </div>
                <div class="form-group"><label>Description</label><textarea name="description" placeholder="Description…"><?= htmlspecialchars($_POST['description']??'') ?></textarea></div>
            </div>
            
            <!-- Auteur -->
            <div class="section">
                <div class="section-title"><span class="emoji">👤</span> Auteur</div>
                <div class="row-3">
                    <div class="form-group"><label>Nom</label><input type="text" name="author" placeholder="Votre nom" value="<?= htmlspecialchars($_POST['author']??'') ?>"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="author_email" placeholder="email@exemple.com" value="<?= htmlspecialchars($_POST['author_email']??'') ?>"></div>
                    <div class="form-group"><label>Site web</label><input type="url" name="author_url" placeholder="https://…" value="<?= htmlspecialchars($_POST['author_url']??'') ?>"></div>
                </div>
                <div class="row">
                    <div class="form-group"><label>Version</label><input type="text" name="version" placeholder="1.0.0" value="<?= htmlspecialchars($_POST['version']??'1.0.0') ?>"></div>
                    <div class="form-group"><label>Licence</label><input type="text" name="license" value="<?= htmlspecialchars($_POST['license']??'GNU/GPL') ?>"></div>
                </div>
            </div>
            
            <button type="submit" class="btn" id="submit-btn">✨ Générer le composant</button>
        </form>
    </div>
    
    <div class="footer"><p>Générateur d'Extensions Joomla 5 — v5.1</p><span class="jbadge">Joomla 5.x</span></div>
</div>

<script>
const $ = s => document.querySelector(s);
const $$ = s => document.querySelectorAll(s);

const pluginEventsMap = <?= json_encode([
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
    'task'=>['onExecuteTask']
]) ?>;

const fileCounts = {
    component: { simple: '~8 fichiers', basic: '~12 fichiers', advanced: '~22 fichiers' },
    module:    { simple: '~5 fichiers', basic: '~8 fichiers',  advanced: '~10 fichiers' },
    plugin:    { simple: '~3 fichiers', basic: '~5 fichiers',  advanced: '~7 fichiers' },
};

function updateUI() {
    const type   = document.querySelector('input[name="extension_type"]:checked')?.value || 'component';
    const struct = document.querySelector('input[name="structure_type"]:checked')?.value || 'advanced';
    const alias  = $('#extension_alias').value || 'alias';
    const vendor = ($('#vendor_name')?.value || 'MyCompany').replace(/[^a-zA-Z0-9]/g, '');
    const group  = $('#plugin_group').value;
    const isSimple = struct === 'simple';
    const isAdv    = struct === 'advanced';

    $('#module-fields').classList.toggle('active', type === 'module');
    $('#plugin-fields').classList.toggle('active', type === 'plugin');
    $('#ns-info').classList.toggle('active', isAdv);
    $('#simple-notice').classList.toggle('active', isSimple);

    const fc = fileCounts[type] || fileCounts.component;
    $('#fc-simple').textContent = fc.simple;
    $('#fc-basic').textContent = fc.basic;
    $('#fc-adv').textContent = fc.advanced;

    const labels = {component:'Composant',module:'Module',plugin:'Plugin'};
    $('#lbl-name').innerHTML = `Nom du ${labels[type]} <span class="required">*</span>`;

    const prefixes = {
        component: `com_<strong>${alias}</strong>`,
        module: `mod_<strong>${alias}</strong>`,
        plugin: `plg_<strong>${group}</strong>_<strong>${alias}</strong>`
    };
    $('#alias-help').innerHTML = '→ ' + prefixes[type];
    $('#submit-btn').textContent = `✨ Générer le ${labels[type].toLowerCase()}`;

    const uc = alias.charAt(0).toUpperCase() + alias.slice(1);
    const ucG = group.charAt(0).toUpperCase() + group.slice(1);
    const nsMap = {
        component: `${vendor}\\Component\\${uc}`,
        module: `${vendor}\\Module\\${uc}`,
        plugin: `${vendor}\\Plugin\\${ucG}\\${uc}`
    };
    if ($('#ns-preview')) $('#ns-preview').textContent = nsMap[type];

    if (type === 'plugin') {
        $('#events-preview').textContent = (pluginEventsMap[group] || []).join(', ');
    }

    // ── Tree ──
    const F = n => `<span class="file">${n}</span>`;
    const D = n => `<span class="folder">${n}/</span>`;
    const S = n => `<span class="special">${n}</span>`;
    const DIM = n => `<span class="dim">${n}</span>`;
    let tree = '';

    if (type === 'component') {
        if (isSimple) {
            tree = `📁 com_${alias}/
├── ${F(alias+'.xml')}
├── 📁 ${D('admin')}
│   ├── ${F(alias+'.php')}
│   ├── ${DIM('index.html')}
│   └── 📁 ${D('sql')}
│       ├── ${DIM('index.html')}
│       └── 📁 ${D('updates')}
│           ├── ${DIM('index.html')}
│           └── 📁 ${D('mysql')}
│               ├── ${F('x.x.x.sql')}
│               └── ${DIM('index.html')}
└── 📁 ${D('site')}
    ├── ${F(alias+'.php')}
    └── ${DIM('index.html')}`;
        } else if (struct === 'basic') {
            tree = `📁 com_${alias}/
├── ${F(alias+'.xml')}
├── 📁 ${D('admin')}
│   ├── ${F(alias+'.php')}
│   ├── ${DIM('index.html')}
│   ├── 📁 ${D('sql/updates/mysql')}
│   └── 📁 ${D('language/en-GB')}
│       ├── ${F('com_'+alias+'.ini')}
│       └── ${F('com_'+alias+'.sys.ini')}
└── 📁 ${D('site')}
    ├── ${F(alias+'.php')}
    ├── ${DIM('index.html')}
    └── 📁 ${D('language/en-GB')}`;
        } else {
            tree = `📁 com_${alias}/
├── ${F(alias+'.xml')}  ${S('manifest')}
├── ${S('script.php')}
├── 📁 ${D('admin')}
│   ├── 📁 ${D('services')} → ${F('provider.php')}
│   ├── 📁 ${D('src')}
│   │   ├── ${D('Extension')}  ${D('Controller')}
│   │   ├── ${D('Model')}  ${D('View/Items')}
│   ├── 📁 ${D('tmpl/items')}  ${D('forms')}
│   ├── 📁 ${D('sql')}  ${S('access.xml')}  ${S('config.xml')}
│   └── 📁 ${D('language/en-GB')}
├── 📁 ${D('site')}
│   ├── 📁 ${D('src')} (Controller + View)
│   ├── 📁 ${D('tmpl/default')}
│   └── 📁 ${D('language/en-GB')}
└── 📁 ${D('media')} → ${F('joomla.asset.json')} + css/ + js/`;
        }
    } else if (type === 'module') {
        if (isSimple) {
            tree = `📁 mod_${alias}/
├── ${F('mod_'+alias+'.xml')}
├── ${F('mod_'+alias+'.php')}
├── ${DIM('index.html')}
└── 📁 ${D('tmpl')}
    ├── ${F('default.php')}
    └── ${DIM('index.html')}`;
        } else if (struct === 'basic') {
            tree = `📁 mod_${alias}/
├── ${F('mod_'+alias+'.xml')}
├── ${F('mod_'+alias+'.php')}
├── ${F('helper.php')}
├── ${DIM('index.html')}
├── 📁 ${D('tmpl')} → ${F('default.php')}
└── 📁 ${D('language/en-GB')}`;
        } else {
            tree = `📁 mod_${alias}/
├── ${F('mod_'+alias+'.xml')}  ${S('manifest')}
├── 📁 ${D('services')} → ${S('provider.php')}
├── 📁 ${D('src')}
│   ├── ${D('Dispatcher')} → ${F('Dispatcher.php')}
│   └── ${D('Helper')} → ${F(uc+'Helper.php')}
├── 📁 ${D('tmpl')} → ${F('default.php')}
└── 📁 ${D('language/en-GB')}`;
        }
    } else {
        const evCount = (pluginEventsMap[group]||[]).length;
        if (isSimple) {
            tree = `📁 plg_${group}_${alias}/
├── ${F(alias+'.xml')}
├── ${F(alias+'.php')}  ${S('('+evCount+' événement'+(evCount>1?'s':'')+')')}
└── ${DIM('index.html')}`;
        } else if (struct === 'basic') {
            tree = `📁 plg_${group}_${alias}/
├── ${F(alias+'.xml')}
├── ${F(alias+'.php')}  ${S(evCount+' événements')}
├── ${DIM('index.html')}
└── 📁 ${D('language/en-GB')}`;
        } else {
            tree = `📁 plg_${group}_${alias}/
├── ${F(alias+'.xml')}  ${S('manifest')}
├── 📁 ${D('services')} → ${S('provider.php')}
├── 📁 ${D('src')}
│   └── ${D('Extension')} → ${F(uc+'.php')}  ${S('SubscriberInterface')}
└── 📁 ${D('language/en-GB')}`;
        }
    }

    $('#structure-preview-container').innerHTML = `<div class="preview active">${tree}</div>`;
}

$$('input[name="extension_type"]').forEach(r => r.addEventListener('change', updateUI));
$$('input[name="structure_type"]').forEach(r => r.addEventListener('change', updateUI));
$('#plugin_group').addEventListener('change', updateUI);
if ($('#vendor_name')) $('#vendor_name').addEventListener('input', updateUI);
$('#extension_alias').addEventListener('input', function(){ this.dataset.modified='1'; updateUI(); });
$('#extension_name').addEventListener('input', function(){
    const alias = this.value.toLowerCase().replace(/[^a-z0-9]/g,'').substring(0,20);
    const af = $('#extension_alias');
    if (!af.dataset.modified) af.value = alias;
    updateUI();
});

updateUI();
</script>
</body>
</html>
