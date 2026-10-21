<?php
require_once 'config/database.php';

$cat_config = [
    'category_id'      => 2,
    'hero_img'         => 'https://sf-static.upanhlaylink.com/img/image_20260509df2eee76a625b5485d2adaac51c3441a.jpg',
    'hero_img_alt'     => 'Gỗ điêu khắc thủ công',
    'hero_bg'          => '#6B4F3A',
    'hero_title_color' => '#F5EDE3',
    'hero_desc_color'  => '#F0E0CC',
    'title'            => 'Gỗ điêu khắc',
    'desc'             => 'Từ những khúc gỗ mít, gỗ hương được tuyển chọn kỹ lưỡng, đôi bàn tay nghệ nhân khắc nên từng đường nét sống động. Mỗi tác phẩm là sự kết hợp giữa hồn gỗ tự nhiên và tâm huyết của người thợ điêu khắc.',
    'color_group_label'=> 'Màu gỗ',
    'allowed_colors'   => ['nau-sang','nau-dam','den','tu-nhien','vang-gold'],
    'allowed_types'    => ['tuong-nghe-thuat','khay-dia','do-trang-tri','do-dung'],
    'allowed_styles'   => ['hien-dai','truyen-thong','dan-gian','thien-nhien'],
    'color_labels'  => ['nau-sang'=>'Nâu sáng','nau-dam'=>'Nâu đậm','den'=>'Đen','tu-nhien'=>'Tự nhiên','vang-gold'=>'Vàng gold'],
    'type_labels'   => ['tuong-nghe-thuat'=>'Tượng nghệ thuật','khay-dia'=>'Khay & Đĩa gỗ','do-trang-tri'=>'Đồ trang trí bàn','do-dung'=>'Đồ dùng hàng ngày'],
    'style_labels'  => ['hien-dai'=>'Hiện đại','truyen-thong'=>'Truyền thống','dan-gian'=>'Dân gian','thien-nhien'=>'Thiên nhiên'],
    'color_options' => [
        'nau-sang'  => ['label'=>'Nâu sáng', 'hex'=>'#C49A72','border'=>'#A07850','check_dark'=>true],
        'nau-dam'   => ['label'=>'Nâu đậm',  'hex'=>'#6B3E26','border'=>'#6B3E26','check_dark'=>false],
        'den'       => ['label'=>'Đen',       'hex'=>'#2C2C2A','border'=>'#2C2C2A','check_dark'=>false],
        'tu-nhien'  => ['label'=>'Tự nhiên',  'hex'=>'#D4B483','border'=>'#B8945A','check_dark'=>true],
        'vang-gold' => ['label'=>'Vàng gold', 'hex'=>'#C9A84C','border'=>'#A8882A','check_dark'=>false],
    ],
];

include 'includes/category-page.php';