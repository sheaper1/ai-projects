if (!class_exists('DF_Subscriptions')) { return; }
if (get_option('df_archive_import_done')) { return; }

$issues = array(
    array('title'=>'# 01 | März 2022','number'=>'# 01','date'=>'2022-03-01','year'=>'2022','pdf'=>'https://www.derflugschreiber.at/_files/ugd/f473ae_1c03716f1a884d6e9557365f9195514b.pdf','cover'=>'https://static.wixstatic.com/media/f473ae_e6e95aa21c934a73a52fb04143a22364~mv2.png'),
    array('title'=>'# 02 | Juni 2022','number'=>'# 02','date'=>'2022-06-01','year'=>'2022','pdf'=>'https://www.derflugschreiber.at/_files/ugd/f473ae_d6eea84547f9453ca5250b2668a39a92.pdf','cover'=>'https://static.wixstatic.com/media/f473ae_c59942c7cdba41a7b8cbbba6cfe63994~mv2.png'),
    array('title'=>'# 03 | September 2022','number'=>'# 03','date'=>'2022-09-01','year'=>'2022','pdf'=>'https://www.derflugschreiber.at/_files/ugd/f473ae_f469488313d548099b82a41929713b27.pdf','cover'=>'https://static.wixstatic.com/media/f473ae_3a4d3726b0474b259ac973ec3ab40a9c~mv2.png'),
    array('title'=>'# 04 | Dezember 2022','number'=>'# 04','date'=>'2022-12-01','year'=>'2022','pdf'=>'https://www.derflugschreiber.at/_files/ugd/f473ae_e4c83ff902364a1d8e9c0a3f4047e5e4.pdf','cover'=>'https://static.wixstatic.com/media/f473ae_cb91e6a881164d3b9c63d6e2b9dff8c9~mv2.png'),
    array('title'=>'# 05 | März 2023','number'=>'# 05','date'=>'2023-03-01','year'=>'2023','pdf'=>'https://www.derflugschreiber.at/_files/ugd/f473ae_2aaa72d120f1448d8348bc78ac05a86f.pdf','cover'=>'https://static.wixstatic.com/media/f473ae_f4935de125d7428096cb523c3f477b3a~mv2.png'),
    array('title'=>'# 06 | Juni 2023','number'=>'# 06','date'=>'2023-06-01','year'=>'2023','pdf'=>'https://www.derflugschreiber.at/_files/ugd/f473ae_f5b514fcba034fe6926dbda2f7256a6a.pdf','cover'=>'https://static.wixstatic.com/media/f473ae_47e699a29d8349f395132d3ca3ac9174~mv2.png'),
    array('title'=>'# 07 | September 2023','number'=>'# 07','date'=>'2023-09-01','year'=>'2023','pdf'=>'https://www.derflugschreiber.at/_files/ugd/f473ae_c13e1d8d46b942a382f1ab6c75a31c2a.pdf','cover'=>'https://static.wixstatic.com/media/f473ae_2ed080b3f5924476be2b36ef9565ce05~mv2.jpg'),
    array('title'=>'# 08 | Dezember 2023','number'=>'# 08','date'=>'2023-12-01','year'=>'2023','pdf'=>'https://www.derflugschreiber.at/_files/ugd/f473ae_76110c6b79a44eb9aeb39bbf56d2f6e7.pdf','cover'=>'https://static.wixstatic.com/media/f473ae_25a00e740cd94b28853c8410326b7d57~mv2.png'),
    array('title'=>'# 09 | März 2024','number'=>'# 09','date'=>'2024-03-01','year'=>'2024','pdf'=>'https://www.derflugschreiber.at/_files/ugd/f473ae_da0dfbd777eb48cf8911dacf020e7a63.pdf','cover'=>'https://static.wixstatic.com/media/798a96_1fd1b03a824c4176ab72cf4a15b65452~mv2.jpg'),
    array('title'=>'# 10 | Juni 2024','number'=>'# 10','date'=>'2024-06-01','year'=>'2024','pdf'=>'https://www.derflugschreiber.at/_files/ugd/798a96_1dcb9344d64640f5b74cce91668db436.pdf','cover'=>'https://static.wixstatic.com/media/798a96_2a34e1cba1ab4b2ca993c5162f701b13~mv2.jpg'),
    array('title'=>'# 11 | September 2024','number'=>'# 11','date'=>'2024-09-01','year'=>'2024','pdf'=>'https://www.derflugschreiber.at/_files/ugd/798a96_dbb0554a622a4b1a88bd35f930aeae5f.pdf','cover'=>'https://static.wixstatic.com/media/798a96_bd1243c946444b84a9ff46535dd70c71~mv2.jpg'),
    array('title'=>'# 12 | Dezember 2024','number'=>'# 12','date'=>'2024-12-01','year'=>'2024','pdf'=>'https://www.derflugschreiber.at/_files/ugd/798a96_7054b3d548b945998216f0f33b8e91d4.pdf','cover'=>'https://static.wixstatic.com/media/798a96_8beae377e9704ed8a5b3e8ad368f103e~mv2.jpg'),
    array('title'=>'# 13 | März 2025','number'=>'# 13','date'=>'2025-03-01','year'=>'2025','pdf'=>'https://www.derflugschreiber.at/_files/ugd/798a96_4a66018851b84856a77aa6bb3a4eb7a9.pdf','cover'=>'https://static.wixstatic.com/media/798a96_731119d62d594a90969fcd01bb4e7f4f~mv2.jpg'),
    array('title'=>'# 14 | Juni 2025','number'=>'# 14','date'=>'2025-06-01','year'=>'2025','pdf'=>'https://www.derflugschreiber.at/_files/ugd/798a96_924cfa4f9e054deaa14d7ba6b3fa741f.pdf','cover'=>'https://static.wixstatic.com/media/798a96_fc784e044ae3479d9946291ac74a265a~mv2.jpg'),
    array('title'=>'# 15 | September 2025','number'=>'# 15','date'=>'2025-09-01','year'=>'2025','pdf'=>'https://www.derflugschreiber.at/_files/ugd/798a96_976e0329f63d44ffbd2435c43538d7c1.pdf','cover'=>'https://static.wixstatic.com/media/798a96_0f0f633a9c88439db6e7d476cea2e17f~mv2.jpg'),
    array('title'=>'# 16 | Dezember 2025','number'=>'# 16','date'=>'2025-12-01','year'=>'2025','pdf'=>'https://www.derflugschreiber.at/_files/ugd/798a96_32bdab67871b4d71919143c86ee2d0bc.pdf','cover'=>'https://static.wixstatic.com/media/798a96_81474e338c4d45089685646ab1b1338d~mv2.jpg'),
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

update_option('df_archive_import_result', sprintf('created=%d updated=%d demo_trashed=%d', $created, $updated, $trashed));
update_option('df_archive_import_done', 1);
