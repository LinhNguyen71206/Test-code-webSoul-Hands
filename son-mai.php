<?php
require_once 'config/database.php';

$cat_config = [
    'category_id'      => 4,
    'hero_img'         => 'https://images.unsplash.com/photo-1579783902614-a3fb3927b6a5?q=80&w=1200&auto=format&fit=crop',
    'hero_img_alt'     => 'Sơn mài thủ công Việt Nam',
    'hero_bg'          => '#5C1A1A',
    'hero_title_color' => '#FAE8D0',
    'hero_desc_color'  => '#F0D0B0',
    'title'            => 'Sơn mài',
    'desc'             => 'Nghệ thuật sơn mài Việt Nam trải qua hàng chục lớp sơn ta, mài giũa kiên nhẫn để lộ ra vẻ đẹp óng ánh sâu thẳm. Mỗi tác phẩm là sự kết tinh của kỹ thuật truyền thống hàng nghìn năm và tâm hồn người nghệ nhân.',
    'color_group_label'=> 'Màu men',
    'allowed_colors'   => ['do-son','den-son','vang-nhung','xanh-co-vit','nau-canh-gian'],
    'allowed_types'    => ['tranh-tuong','hop-khay','trang-suc','do-trang-tri'],
    'allowed_styles'   => ['truyen-thong','hien-dai','co-dien','phong-dong'],
    'color_labels'  => ['do-son'=>'Đỏ son','den-son'=>'Đen sơn','vang-nhung'=>'Vàng nhũ','xanh-co-vit'=>'Xanh cổ vịt','nau-canh-gian'=>'Nâu cánh gián'],
    'type_labels'   => ['tranh-tuong'=>'Tranh treo tường','hop-khay'=>'Hộp & Khay sơn mài','trang-suc'=>'Trang sức sơn mài','do-trang-tri'=>'Đồ trang trí'],
    'style_labels'  => ['truyen-thong'=>'Truyền thống','hien-dai'=>'Hiện đại','co-dien'=>'Cổ điển','phong-dong'=>'Phương Đông'],
    'color_options' => [
        'do-son'        => ['label'=>'Đỏ son',       'hex'=>'#C0392B','border'=>'#A02020','check_dark'=>false],
        'den-son'       => ['label'=>'Đen sơn',      'hex'=>'#2C2C2A','border'=>'#2C2C2A','check_dark'=>false],
        'vang-nhung'    => ['label'=>'Vàng nhũ',     'hex'=>'#C9A84C','border'=>'#A8882A','check_dark'=>false],
        'xanh-co-vit'   => ['label'=>'Xanh cổ vịt', 'hex'=>'#2E7D7A','border'=>'#1E5D5A','check_dark'=>false],
        'nau-canh-gian' => ['label'=>'Nâu cánh gián','hex'=>'#7B4F2E','border'=>'#7B4F2E','check_dark'=>false],
    ],
];

include 'includes/category-page.php';