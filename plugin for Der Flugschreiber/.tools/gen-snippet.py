# -*- coding: utf-8 -*-
import json, os
HERE = os.path.dirname(__file__)
issues = json.load(open(os.path.join(HERE, 'issues.json'), encoding='utf-8'))

def pesc(s):
    return s.replace('\\', '\\\\').replace("'", "\\'")

rows = []
for i in issues:
    rows.append(
        "    array('title'=>'%s','number'=>'%s','date'=>'%s','year'=>'%s','pdf'=>'%s','cover'=>'%s'),"
        % (pesc(i['title']), pesc(i['number_label']), i['date'], i['year'], pesc(i['pdf']), pesc(i['cover']))
    )
rows = "\n".join(rows)

snippet = """if (!class_exists('DF_Subscriptions')) { return; }
if (get_option('df_archive_import_done')) { return; }

$issues = array(
%s
);

$created = 0; $updated = 0;
foreach ($issues as $iss) {
    $existing = get_posts(array(
        'post_type'     => DF_Subscriptions::MAGAZINE_POST_TYPE,
        'title'         => $iss['title'],
        'post_status'   => 'any',
        'numberposts'   => 1,
        'fields'        => 'ids',
        'no_found_rows' => true,
    ));
    if (!empty($existing)) {
        $post_id = (int) $existing[0];
        $updated++;
    } else {
        $post_id = wp_insert_post(array(
            'post_type'   => DF_Subscriptions::MAGAZINE_POST_TYPE,
            'post_status' => 'publish',
            'post_title'  => $iss['title'],
        ));
        if (is_wp_error($post_id) || !$post_id) { continue; }
        $created++;
    }
    update_post_meta($post_id, DF_Subscriptions::MAGAZINE_ACCESS_META, 'free_pdf');
    update_post_meta($post_id, DF_Subscriptions::MAGAZINE_PDF_META, esc_url_raw($iss['pdf']));
    update_post_meta($post_id, DF_Subscriptions::MAGAZINE_COVER_URL_META, esc_url_raw($iss['cover']));
    update_post_meta($post_id, DF_Subscriptions::MAGAZINE_ISSUE_NUMBER_META, $iss['number']);
    update_post_meta($post_id, DF_Subscriptions::MAGAZINE_ISSUE_DATE_META, $iss['date']);
    wp_set_object_terms($post_id, $iss['year'], DF_Subscriptions::ISSUE_YEAR_TAXONOMY, false);
}

// Remove demo content (reversible: moved to Trash) to avoid duplicate issue numbers.
$demo = get_posts(array(
    'post_type'   => array(DF_Subscriptions::MAGAZINE_POST_TYPE, DF_Subscriptions::ARTICLE_POST_TYPE),
    'post_status' => 'any',
    'numberposts' => -1,
    'fields'      => 'ids',
    'meta_key'    => '_df_demo_content',
    'meta_value'  => '1',
));
$trashed = 0;
foreach ($demo as $d) { if (wp_trash_post($d)) { $trashed++; } }

update_option('df_archive_import_result', sprintf('created=%%d updated=%%d demo_trashed=%%d', $created, $updated, $trashed));
update_option('df_archive_import_done', 1);
""" % rows

open(os.path.join(HERE, 'snippet.php'), 'w', encoding='utf-8', newline='\n').write(snippet)
print("wrote snippet.php (%d issues)" % len(issues))
print("bytes:", len(snippet.encode('utf-8')))
