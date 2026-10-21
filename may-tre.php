<?php
require_once 'config/database.php';

$cat_config = [
    'category_id'      => 3,
    'hero_img'         => 'https://sf-static.upanhlaylink.com/img/image_2026051091d52581f79b8c8e94fa0a8af4e6ae03.jpg',
    'hero_img_alt'     => 'Mây tre đan tay thủ công',
    'hero_bg'          => '#4A6741',
    'hero_title_color' => '#F0F4E8',
    'hero_desc_color'  => '#D8E8C8',
    'title'            => 'Mây tre',
    'desc'             => 'Những sợi mây, nan tre được chẻ mỏng, ngâm kỹ rồi đan tay thành từng sản phẩm bền chắc và tinh xảo. Mang hơi thở của làng nghề Việt, mỗi chiếc giỏ, mỗi khay mây là một câu chuyện kiên nhẫn và khéo léo.',
    'color_group_label'=> 'Màu sắc',
    'allowed_colors'   => ['kem-vang','nau-tre','trang-moc','xanh-la','den-bong'],
    'allowed_types'    => ['gio-ro','khay-may','den-long','do-noi-that'],
    'allowed_styles'   => ['thien-nhien','truyen-thong','hien-dai','bohemian'],
    'color_labels'  => ['kem-vang'=>'Kem vàng','nau-tre'=>'Nâu tre','trang-moc'=>'Trắng mộc','xanh-la'=>'Xanh lá','den-bong'=>'Đen bóng'],
    'type_labels'   => ['gio-ro'=>'Giỏ & Rổ đan tay','khay-may'=>'Khay khảm mây','den-long'=>'Đèn lồng mây tre','do-noi-that'=>'Đồ nội thất nhỏ'],
    'style_labels'  => ['thien-nhien'=>'Thiên nhiên','truyen-thong'=>'Truyền thống','hien-dai'=>'Hiện đại','bohemian'=>'Bohemian'],
    'color_options' => [
        'kem-vang'  => ['label'=>'Kem vàng',  'hex'=>'#D4C068','border'=>'#B8A040','check_dark'=>true],
        'nau-tre'   => ['label'=>'Nâu tre',   'hex'=>'#8B6E4E','border'=>'#8B6E4E','check_dark'=>false],
        'trang-moc' => ['label'=>'Trắng mộc', 'hex'=>'#E8E0D0','border'=>'#C8C0B0','check_dark'=>true],
        'xanh-la'   => ['label'=>'Xanh lá',   'hex'=>'#5A8A50','border'=>'#5A8A50','check_dark'=>false],
        'den-bong'  => ['label'=>'Đen bóng',  'hex'=>'#2C2C2A','border'=>'#2C2C2A','check_dark'=>false],
    ],
];

include 'includes/category-page.php';