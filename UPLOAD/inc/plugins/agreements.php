<?php

/**
 * Disallow direct access to this file for security reasons
 */
if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.");
}

/**
 * Define our hooks
 */
$plugins->add_hook('admin_config_menu', 'agreements_admin_config_menu');
$plugins->add_hook('admin_config_action_handler', 'agreements_admin_config_action_handler');
$plugins->add_hook('forumdisplay_end', 'agreements_forumdisplay_end');
$plugins->add_hook('showthread_end', 'agreements_showthread_end');

/**
 * Returns an array of information about this plugin
 */
function agreements_info()
{
    global $lang;
    $lang->load("agreements", true);

    return array(
        "name" => "Agreements",
        "description" => $lang->agreements_desc,
        "website" => "http://www.jamiesage.co.uk",
        "author" => "Jamie Sage",
        "authorsite" => "http://www.jamiesage.co.uk",
        "version" => "0.1",
        "guid" => "",
        "codename" => str_replace('.php', '', basename(__FILE__)),
        "compatibility" => "18*"
    );
}

/**
 * Called when the plugin is installed
 */
function agreements_install()
{
    global $db, $lang;
    $lang->load("agreements", true);

    // Create the 'agreements' table table
    if (!$db->table_exists('agreements')) {
        $db->write_query("
            CREATE TABLE `" . TABLE_PREFIX . "agreements` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `slug` VARCHAR(255) NOT NULL,
            `name` VARCHAR(255) NOT NULL,
            `content` TEXT NULL,
            `forums` VARCHAR(255) NULL,
            `created_at` DATETIME NULL,
            `updated_at` DATETIME NULL,
            PRIMARY KEY (`id`));
        ");
    }

    // Create the 'user_agreements' pivot table table
    if (!$db->table_exists('user_agreements')) {
        $db->write_query("
            CREATE TABLE `" . TABLE_PREFIX . "user_agreements` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `user_id` INT NOT NULL,
            `agreement_id` INT NOT NULL,
            `created_at` DATETIME NULL,
            PRIMARY KEY (`id`));
        ");
    }

    // Templates
    $template = <<<'EOT'
<html>
<head>
<title>{$mybb->settings['bbname']} - {$agreement['name']}</title>
{$headerinclude}
</head>
<body>
{$header}
<table border="0" cellspacing="0" cellpadding="5" class="tborder">
    <tbody>
        <tr>
            <td class="thead"><strong>{$agreement['name']}</strong></td>
        </tr>
        <tr>
            <td>
                {$agreement['content']}
                <div style="text-align: center;">
                    <br>
                    <form action="agreement.php" method="post">
                        {$agree_button}
                    </form>
                </div>
            </td>
        </tr>
    </tbody>
</table>
{$footer}
</body>
EOT;

    $insert_array = array(
        'title' => 'agreement_template',
        'template' => $db->escape_string($template),
        'sid' => '-1',
        'version' => '',
        'dateline' => time()
    );
    $db->insert_query('templates', $insert_array);

    // Settings
    $setting_group = array(
        'name' => 'agreements',
        'title' => $lang->agreements,
        'description' => $lang->agreements_desc,
        'disporder' => 5,
        'isdefault' => 0
    );
    $gid = $db->insert_query("settinggroups", $setting_group);

    // Rebuild settings
    rebuild_settings();
}

/**
 * Check if Agreements is installed
 * @return bool
 */
function agreements_is_installed()
{
    global $db;
    return $db->table_exists('agreements') && $db->table_exists('user_agreements');
}

/**
 * Called when the plugin is uninstalled
 */
function agreements_uninstall()
{
    global $db;

    // Drop the agreements table
    $db->drop_table('agreements');

    // Drop the user agreements table
    $db->drop_table('user_agreements');

    // Delete the agreement template
    $db->delete_query("templates", "title = 'agreement_template'");
}

/**
 * Hook the admin configuration menu
 * @param $sub_menu
 */
function agreements_admin_config_menu(&$sub_menu)
{
    global $lang;
    $lang->load("agreements", true);

    $sub_menu['300'] = [
        "id" => "agreements",
        "title" => $lang->agreements,
        "link" => "index.php?module=config-agreements"
    ];
}

/**
 * Hook the admin configuration action handler
 * @param $actions
 */
function agreements_admin_config_action_handler(&$actions)
{
    $actions['agreements'] = [
        'active' => 'agreements',
        'file' => 'agreements.php'
    ];
}

/**
 * Hook forum display end
 */
function agreements_forumdisplay_end()
{
    global $mybb;
    return canAccessForum($mybb->input['fid']);
}

function agreements_showthread_end()
{
    global $mybb;
    if ($mybb->input['tid']) {$thread = get_thread($mybb->get_input('tid', MyBB::INPUT_INT));
        return canAccessForum($thread['fid'], $mybb->input['tid']);
    }
}

/**
 * Check if the logged in user can access a thread
 * @param $fid
 * @param null $tid
 * @return bool
 */
function canAccessForum($fid, $tid = null)
{
    global $mybb, $db;
    if ($fid && ($mybb->user['uid'] != '/' || $mybb->user['uid'] != 0)) {
        $query = $db->simple_select("agreements", "id, slug, forums", "forums IS NOT NULL");

        // If there are any agreements which are tied to the any forum
        if($query->num_rows) {
            while($agreement = $db->fetch_array($query)) {
                if (in_array($fid, explode(",", $agreement['forums']))) {
                    // Check if the user has already agreed to the agreement
                    $query = $db->simple_select("user_agreements", "id", "user_id={$mybb->user['uid']} AND agreement_id={$agreement['id']}");

                    if (!$query->num_rows) {
                        // Redirect to the agreement
                        if ($tid) {
                            header("Location: {$mybb->settings['bburl']}/agreement.php?v=" . $agreement['slug'] . "&tid=" . $tid);
                        } else {
                            header("Location: {$mybb->settings['bburl']}/agreement.php?v=" . $agreement['slug'] . "&fid=" . $fid);
                        }
                        exit();
                    }
                }
            }
        }
    }
    return true;
}
