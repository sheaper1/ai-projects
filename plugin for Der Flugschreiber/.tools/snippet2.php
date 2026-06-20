if (!class_exists('DF_Subscriptions')) { return; }
if (get_option('df_topics_demo_done')) { return; }

// 1) Best-effort topic categories for the 16 imported archive issues (by issue number).
$topic_map = array(
    '# 01' => array('Flugzeuge & Technik', 'Menschen & Reisen'),
    '# 02' => array('Ausbildung & Praxis', 'Menschen & Reisen'),
    '# 03' => array('Flugzeuge & Technik', 'Ausbildung & Praxis'),
    '# 04' => array('Menschen & Reisen'),
    '# 05' => array('Flugzeuge & Technik', 'Flugsicherheit'),
    '# 06' => array('Menschen & Reisen', 'Flugsicherheit'),
    '# 07' => array('Ausbildung & Praxis'),
    '# 08' => array('Menschen & Reisen', 'Flugsicherheit'),
    '# 09' => array('Flugzeuge & Technik', 'Flugsicherheit'),
    '# 10' => array('Flugzeuge & Technik', 'Menschen & Reisen'),
    '# 11' => array('Flugzeuge & Technik', 'Ausbildung & Praxis'),
    '# 12' => array('Menschen & Reisen', 'Ausbildung & Praxis'),
    '# 13' => array('Flugzeuge & Technik', 'Flugsicherheit'),
    '# 14' => array('Flugzeuge & Technik', 'Menschen & Reisen'),
    '# 15' => array('Ausbildung & Praxis', 'Flugzeuge & Technik'),
    '# 16' => array('Flugzeuge & Technik', 'Flugsicherheit'),
);

$topics_done = 0;
foreach ($topic_map as $num => $cats) {
    $ids = get_posts(array(
        'post_type'   => DF_Subscriptions::MAGAZINE_POST_TYPE,
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields'      => 'ids',
        'meta_query'  => array(
            array('key' => DF_Subscriptions::MAGAZINE_ISSUE_NUMBER_META, 'value' => $num, 'compare' => '='),
            array('key' => '_df_demo_content', 'compare' => 'NOT EXISTS'),
        ),
    ));
    foreach ($ids as $pid) {
        wp_set_object_terms($pid, $cats, DF_Subscriptions::TOPIC_TAXONOMY, false);
        $topics_done++;
    }
}

// 2) Restore demo content from Trash, publish it, and clearly mark titles with [DEMO].
$restored = 0;
$demo = get_posts(array(
    'post_type'   => array(DF_Subscriptions::MAGAZINE_POST_TYPE, DF_Subscriptions::ARTICLE_POST_TYPE),
    'post_status' => 'trash',
    'numberposts' => -1,
    'fields'      => 'ids',
    'meta_key'    => '_df_demo_content',
    'meta_value'  => '1',
));
foreach ($demo as $pid) {
    wp_untrash_post($pid);
    $title = get_the_title($pid);
    $args = array('ID' => $pid, 'post_status' => 'publish');
    if (strpos($title, '[DEMO]') !== 0) {
        $args['post_title'] = '[DEMO] ' . $title;
    }
    wp_update_post($args);
    $restored++;
}

update_option('df_topics_demo_result', sprintf('topics=%d restored=%d', $topics_done, $restored));
update_option('df_topics_demo_done', 1);
