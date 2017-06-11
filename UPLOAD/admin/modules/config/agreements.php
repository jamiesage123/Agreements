<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$page->add_breadcrumb_item($lang->agreements, "index.php?module=config-agreements");

/**
 * View a specific agreement
 */
if($mybb->input['action'] === 'view' && $mybb->input['id'])
{
    $query = $db->simple_select("agreements", "*", "id='".$mybb->get_input('id', MyBB::INPUT_INT)."'");
    $agreement = $db->fetch_array($query);

    if(!$agreement['id']) {
        admin_redirect("index.php?module=config-agreements");
    }

    header("Location: {$mybb->settings['bburl']}/agreement.php?v=" . $agreement['slug']);
}

/**
 * View all users who have agreed to an agreement
 */
if($mybb->input['action'] === 'view_users' && $mybb->input['id'])
{
    $query = $db->simple_select("agreements", "*", "id='".$mybb->get_input('id', MyBB::INPUT_INT)."'");
    $agreement = $db->fetch_array($query);

    if(!$agreement['id']) {
        admin_redirect("index.php?module=config-agreements");
    }

    $per_page = 20;
    if($mybb->input['page'] && $mybb->input['page'] > 1) {
        $mybb->input['page'] = $mybb->get_input('page', MyBB::INPUT_INT);
        $start = ($mybb->input['page']*$per_page)-$per_page;}
    else {
        $mybb->input['page'] = 1;
        $start = 0;
    }

    $page->add_breadcrumb_item($lang->agreements_update);
    $page->output_header($lang->agreements." - ".$lang->agreements_update);

    $sub_tabs['agreements'] = [
        'title' => $lang->agreements,
        'link' => "index.php?module=config-agreements"
    ];

    $sub_tabs['add_agreement'] = array(
        'title' => $lang->agreements_add_new,
        'link' => "index.php?module=config-agreements&amp;action=add",
    );

    $sub_tabs['user_agreement_users'] = array(
        'title' => $lang->agreements_view_users,
        'link' => "index.php?module=config-agreements&amp;action=view_users&amp;id=" . $agreement['id'],
    );

    $page->output_nav_tabs($sub_tabs, 'user_agreement_users');

    $table = new Table;
    $table->construct_header($lang->agreement_username, array('width' => '20%'));
    $table->construct_header($lang->agreement_last_active, array('width' => '15%', 'class' => 'align_center'));
    $table->construct_header($lang->agreement_email, array('width' => '15%', 'class' => 'align_center'));
    $table->construct_header($lang->agreement_agreed_at, array('width' => '15%', 'class' => 'align_center'));

    $query = $db->query("
		SELECT u.uid, u.username, u.lastactive, u.email, u.coppauser, a.created_at
		FROM ".TABLE_PREFIX."users u
		JOIN ".TABLE_PREFIX."user_agreements a ON (a.user_id = u.uid)
		WHERE a.agreement_id = {$agreement['id']}
		ORDER BY created_at DESC
		LIMIT {$start}, {$per_page}
	");

    while($user = $db->fetch_array($query)) {
        $user['username'] = htmlspecialchars_uni($user['username']);
        $user['profilelink'] = build_profile_link($user['username'], $user['uid'], "_blank");
        $user['email'] = htmlspecialchars_uni($user['email']);
        $user['lastactive'] = my_date('relative', $user['lastactive']);

        $table->construct_cell($user['profilelink']);
        $table->construct_cell($user['lastactive'], array("class" => "align_center"));
        $table->construct_cell($user['email'], array("class" => "align_center"));
        $table->construct_cell($user['created_at'], array("class" => "align_center"));
        $table->construct_row();
    }

    if($table->num_rows() == 0)
    {
        $table->construct_cell($lang->agreement_no_users, array('colspan' => 5));
        $table->construct_row();
        $table->output($lang->agreement_users);
    } else {
        $table->output($lang->agreement_users);
    }

    $query = $db->simple_select("user_agreements", "COUNT(user_id) AS users", "agreement_id=" . $agreement['id']);
    $total_rows = $db->fetch_field($query, "users");

    echo "<br />".draw_admin_pagination($mybb->input['page'], $per_page, $total_rows, "index.php?module=config-agreements&amp;action=view_user&amp;id=" . $agreement['id'] . "&amp;page={page}");

    $page->output_footer();
}

/**
 * Add an agreement
 */
if($mybb->input['action'] === 'add')
{
    $page->extra_header .= <<<EOF
    <link rel="stylesheet" href="../jscripts/sceditor/editor_themes/mybb.css" type="text/css" media="all" />
    <script type="text/javascript" src="../jscripts/sceditor/jquery.sceditor.bbcode.min.js?ver=1805"></script>
    <script type="text/javascript" src="../jscripts/bbcodes_sceditor.js?ver=1808"></script>
    <script type="text/javascript" src="../jscripts/sceditor/editor_plugins/undo.js?ver=1805"></script>
EOF;

    /**
     * Store the agreement
     */
    if($mybb->request_method == "post")
    {
        // Validation
        if(!trim($mybb->input['agreement_name'])) {
            $errors[] = $lang->agreement_error_name;
        }

        if(strlen($mybb->input['agreement_content']) === 0) {
            $errors[] = $lang->agreement_error_content;
        }

        if(!$errors)
        {
            $forums = $db->escape_string(implode($mybb->input['agreement_forums'], ','));
            $agreement = [
                "slug" => generate_slug($db->escape_string($mybb->input['agreement_name'])),
                "name" => $db->escape_string($mybb->input['agreement_name']),
                "content" => $db->escape_string($mybb->input['agreement_content']),
                "forums" => strlen($forums) === 0 ? null : $forums,
                "created_at" => date('Y-m-d H:i:s'),
                "updated_at" => date('Y-m-d H:i:s')
            ];
            $db->insert_query("agreements", $agreement);

            flash_message($lang->agreement_add_success, 'success');
            admin_redirect("index.php?module=config-agreements");
        }
    }

    $page->add_breadcrumb_item($lang->agreements_add_new);
    $page->output_header($lang->agreements." - ".$lang->agreements_add_new);


    $sub_tabs['agreements'] = [
        'title' => $lang->agreements,
        'link' => "index.php?module=config-agreements"
    ];

    $sub_tabs['add_agreement'] = array(
        'title' => $lang->agreements_add_new,
        'link' => "index.php?module=config-agreements&amp;action=add",
    );

    $page->output_nav_tabs($sub_tabs, 'add_agreement');

    $form = new Form("index.php?module=config-agreements&amp;action=add", "post", "add");

    if($errors) {
        $page->output_inline_error($errors);
    } else {
        $mybb->input['extra'] = 0;
    }

    $form_container = new FormContainer($lang->agreements_add_new);
    $form_container->output_row($lang->agreement_name . " <em>*</em>", "", $form->generate_text_box('agreement_name', $mybb->input['agreement_name'], array('id' => 'agreement_name')), 'agreement_name');
    $form_container->output_row($lang->agreement_content . " <em>*</em>", $lang->agreement_content_desc, $form->generate_text_area('agreement_content', $mybb->input['agreement_content'], ['id' => 'agreement_content', 'rows' => 15]) . build_mycode_inserter("agreement_content"), 'agreement_content');
    $form_container->output_row($lang->agreement_forums, $lang->agreement_content_desc, $form->generate_forum_select('agreement_forums[]', -1, ['id' => 'agreement_forums', 'multiple' => true, 'size' => 5]), 'agreement_forums');
    $form_container->end();

    $buttons[] = $form->generate_submit_button($lang->agreement_forums_create);

    $form->output_submit_wrapper($buttons);
    $form->end();

    $page->output_footer();
}

/**
 * Edit an agreement
 */
if($mybb->input['action'] === 'edit' && $mybb->input['id'])
{
    $page->extra_header .= <<<EOF
    <link rel="stylesheet" href="../jscripts/sceditor/editor_themes/mybb.css" type="text/css" media="all" />
    <script type="text/javascript" src="../jscripts/sceditor/jquery.sceditor.bbcode.min.js?ver=1805"></script>
    <script type="text/javascript" src="../jscripts/bbcodes_sceditor.js?ver=1808"></script>
    <script type="text/javascript" src="../jscripts/sceditor/editor_plugins/undo.js?ver=1805"></script>
EOF;

    $query = $db->simple_select("agreements", "*", "id='".$mybb->get_input('id', MyBB::INPUT_INT)."'");
    $agreement = $db->fetch_array($query);

    if(!$agreement['id']) {
        admin_redirect("index.php?module=config-agreements");
    }

    /**
     * Update the agreement
     */
    if($mybb->request_method == "post")
    {
        // Validation
        if(!trim($mybb->input['agreement_name'])) {
            $errors[] = $lang->agreement_error_name;
        }

        if(strlen($mybb->input['agreement_content']) === 0) {
            $errors[] = $lang->agreement_error_content;
        }

        if(!$errors)
        {
            $forums = $db->escape_string(implode($mybb->input['agreement_forums'], ','));
            $agreement_update = [
                "slug" => generate_slug($db->escape_string($mybb->input['agreement_name'])),
                "name" => $db->escape_string($mybb->input['agreement_name']),
                "content" => $db->escape_string($mybb->input['agreement_content']),
                "forums" => strlen($forums) === 0 ? null : $forums,
                "created_at" => date('Y-m-d H:i:s'),
                "updated_at" => date('Y-m-d H:i:s')
            ];
            $db->update_query("agreements", $agreement_update, "id='{$agreement['id']}'");

            flash_message($lang->agreement_update_success, 'success');
            admin_redirect("index.php?module=config-agreements&amp;action=edit&amp;id=" . $agreement['id']);
        }
    }

    $page->add_breadcrumb_item($lang->agreements_update);
    $page->output_header($lang->agreements." - ".$lang->agreements_update);

    $sub_tabs['agreements'] = [
        'title' => $lang->agreements,
        'link' => "index.php?module=config-agreements"
    ];

    $sub_tabs['add_agreement'] = array(
        'title' => $lang->agreements_add_new,
        'link' => "index.php?module=config-agreements&amp;action=add",
    );

    $sub_tabs['update_agreement'] = array(
        'title' => $lang->agreements_update,
        'link' => "index.php?module=config-agreements&amp;action=edit&amp;id=" . $agreement['id'],
    );

    $page->output_nav_tabs($sub_tabs, 'update_agreement');

    $form = new Form("index.php?module=config-agreements&amp;action=edit&amp;id=" . $agreement['id'], "post", "edit");

    if($errors) {
        $page->output_inline_error($errors);
    } else {
        $mybb->input['extra'] = 0;
    }

    $form_container = new FormContainer($lang->agreements_update);
    $form_container->output_row($lang->agreement_name . " <em>*</em>", "", $form->generate_text_box('agreement_name', $agreement['name'], array('id' => 'agreement_name')), 'agreement_name');
    $form_container->output_row($lang->agreement_content . " <em>*</em>", $lang->agreement_content_desc, $form->generate_text_area('agreement_content', $agreement['content'], ['id' => 'agreement_content', 'rows' => 15]) . build_mycode_inserter("agreement_content"), 'agreement_content');
    $form_container->output_row($lang->agreement_forums, $lang->agreement_content_desc, $form->generate_forum_select('agreement_forums[]', explode(",", $agreement['forums']), ['id' => 'agreement_forums', 'multiple' => true, 'size' => 5]), 'agreement_forums');
    $form_container->end();

    $buttons[] = $form->generate_submit_button($lang->agreement_forums_create);

    $form->output_submit_wrapper($buttons);
    $form->end();

    $page->output_footer();
}

/**
 * Delete an agreement
 */
if($mybb->input['action'] == "delete" && $mybb->input['id'])
{
    if($mybb->input['no']) {
        admin_redirect("index.php?module=config-agreements");
    }

    $query = $db->simple_select("agreements", "*", "id='".$mybb->get_input('id', MyBB::INPUT_INT)."'");
    $agreement = $db->fetch_array($query);

    if(!$agreement['id'] || $mybb->request_method != "post") {
        admin_redirect("index.php?module=config-agreements");
    }

    // Delete any pivot references
    $db->delete_query("user_agreements", "agreement_id='{$agreement['id']}'");

    // Delete the agreement
    $db->delete_query("agreements", "id='{$agreement['id']}'");

    admin_redirect("index.php?module=config-agreements");
}

/**
 * Clear all users from this agreement
 */
if($mybb->input['action'] == "clear-agreed" && $mybb->input['id'])
{
    if($mybb->input['no']) {
        admin_redirect("index.php?module=config-agreements");
    }

    $query = $db->simple_select("agreements", "*", "id='".$mybb->get_input('id', MyBB::INPUT_INT)."'");
    $agreement = $db->fetch_array($query);

    if(!$agreement['id'] || $mybb->request_method != "post") {
        admin_redirect("index.php?module=config-agreements");
    }

    // Delete any pivot references
    $db->delete_query("user_agreements", "agreement_id='{$agreement['id']}'");

    flash_message($lang->agreement_users_cleared, 'success');

    admin_redirect("index.php?module=config-agreements");
}

/**
 * Show the agreements configuration
 */
if(!$mybb->input['action'])
{
	$page->output_header($lang->agreements);

	$sub_tabs['agreements'] = [
		'title' => $lang->agreements,
		'link' => "index.php?module=config-agreements"
    ];

	$sub_tabs['add_agreement'] = array(
		'title' => $lang->agreements_add_new,
		'link' => "index.php?module=config-agreements&amp;action=add",
	);

	$page->output_nav_tabs($sub_tabs, 'agreements');

	$form_container = new FormContainer($lang->agreements);
	$form_container->output_row_header("Title", ['width' => '80%']);
	$form_container->output_row_header("Total Agreed", ['width' => '10%']);
	$form_container->output_row_header("Actions", ['width' => '10%']);

	$query = $db->simple_select("agreements", "*", "", ['order_by' => 'id', 'order_dir' => 'desc']);
	while($agreement = $db->fetch_array($query)) {
        $count = $db->query("
            SELECT id
            FROM ".TABLE_PREFIX."user_agreements a
            WHERE a.agreement_id = {$agreement['id']}
	    ");

		$form_container->output_cell(htmlspecialchars_uni($agreement['name']));
		$form_container->output_cell($count->num_rows);
		$popup = new PopupMenu("agreement_{$agreement['id']}", $lang->options);
		$popup->add_item("View Agreement", "index.php?module=config-agreements&amp;action=view&amp;id={$agreement['id']}");
        $popup->add_item("View Agreed Users", "index.php?module=config-agreements&amp;action=view_users&amp;id={$agreement['id']}");
        $popup->add_item("Clear Agreed Users", "index.php?module=config-agreements&amp;action=clear-agreed&amp;id={$agreement['id']}&amp;my_post_key={$mybb->post_code}", "return AdminCP.deleteConfirmation(this, 'Are you sure you want to clear all users from this agreement?')");
        $popup->add_item("Edit Agreement", "index.php?module=config-agreements&amp;action=edit&amp;id={$agreement['id']}");
		$popup->add_item("Delete Agreement", "index.php?module=config-agreements&amp;action=delete&amp;id={$agreement['id']}&amp;my_post_key={$mybb->post_code}", "return AdminCP.deleteConfirmation(this, 'Are you sure you want to delete this agreement?')");
		$form_container->output_cell($popup->fetch(), array("class" => "align_center"));
		$form_container->construct_row();
	}

	if($form_container->num_rows() == 0)
	{
		$form_container->output_cell("You have no agreements", array('colspan' => 5));
		$form_container->construct_row();
	}

	$form_container->end();

	$page->output_footer();
}

/**
 * @author http://ourcodeworld.com/articles/read/253/creating-url-slugs-properly-in-php-including-transliteration-support-for-utf-8
 * @param $string
 * @return string
 */
function generate_slug($string)
{
    return strtolower(trim(preg_replace('~[^0-9a-z]+~i', '-', html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities($string, ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8')), '-'));
}
