<?php
define("IN_MYBB", 1);
define('THIS_SCRIPT', 'agreement.php');

require_once "./global.php";
require_once MYBB_ROOT."inc/functions_post.php";
require_once MYBB_ROOT."inc/functions_user.php";
require_once MYBB_ROOT."inc/class_parser.php";
$parser = new postParser;

// Load global language phrases
$lang->load('agreements');

// User must be logged in and valid
if($mybb->user['uid'] == '/' || $mybb->user['uid'] == 0 || !$mybb->input['v'])
{
    error_no_permission();
}

// Find the agreement
$query = $db->simple_select("agreements", "*", "slug='".$db->escape_string($mybb->get_input('v', MyBB::INPUT_STRING))."'");
$agreement = $db->fetch_array($query);

if (!$agreement['id']) {
    error_no_permission();
}

// Check if this user has already agreed to this agreement
$query = $db->simple_select("user_agreements", "id", "user_id={$mybb->user['uid']} AND agreement_id={$agreement['id']}");

if ($mybb->request_method == "post")
{
    // Agree to this agreement
    if (!$query->num_rows) {
        $db->insert_query("user_agreements", [
            'user_id' => $mybb->user['uid'],
            'agreement_id' => $agreement['id'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    if ($mybb->input['fid']) {
        header("Location: forumdisplay.php?fid=" . $mybb->input['fid']);
    } elseif ($mybb->input['tid']) {
        header("Location: showthread.php?tid=" . $mybb->input['tid']);
    } else {
        header("Location: {$mybb->settings['bburl']}/agreement.php?v=" . $agreement['slug']);
    }
}
else
{
    /**
     * View the agreement
     */
    add_breadcrumb($agreement['name']);

    // Parse the content
    $parser_options = array(
        "allow_html" => $mybb->settings['pmsallowhtml'],
        "allow_mycode" => $mybb->settings['pmsallowmycode'],
        "allow_smilies" => true,
        "allow_imgcode" => $mybb->settings['pmsallowimgcode'],
        "allow_videocode" => $mybb->settings['pmsallowvideocode'],
        "me_username" => $mybb->user['username'],
        "filter_badwords" => true
    );

    $agreement['content'] = $parser->parse_message($agreement['content'], $parser_options);

    $agree_button  = '<input type="hidden" name="v" id="v" value="' . $agreement['slug'] . '">';
    $agree_button .= '<input type="hidden" name="fid" id="fid" value="' . $mybb->input['fid'] . '">';
    $agree_button .= '<input type="hidden" name="tid" id="tid" value="' . $mybb->input['tid'] . '">';
    $agree_button .= '<input type="submit" class="button" name="agree" value="' . $lang->agreement_agree . '">';

    if ($query->num_rows) {
        $agree_button = 'You have already agreed to this agreement!';
    }
    eval("\$agreement_template = \"".$templates->get("agreement_template")."\";");
    output_page($agreement_template);

}