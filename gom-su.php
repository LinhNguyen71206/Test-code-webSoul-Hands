<?php
require_once 'config/database.php';

$cat_config = [
    'category_id'      => 1,
    'hero_img'         => 'https://images.unsplash.com/photo-1610701596007-11502861dcfa?q=80&w=1200&auto=format&fit=crop',
    'hero_img_alt'     => 'Gốm sứ thủ công',
    'hero_bg'          => '',
    'hero_title_color' => '',
    'hero_desc_color'  => '',
    'title'            => 'Gốm sứ',
    'desc'             => 'Khám phá những món đồ gốm sứ được vuốt tay thủ công từ đất sét tự nhiên. Mỗi thiết kế là một tác phẩm độc bản, được nặn với sự tận tâm, mang trong mình một câu chuyện riêng để bạn cảm nhận.',
    'color_group_label'=> 'Màu sắc',
    'allowed_colors'   => ['trang','nau','xanh','den','hong','kem'],
    'allowed_types'    => ['binh-hoa','am-tra','chen-bat','tuong'],
    'allowed_styles'   => ['hien-dai','truyen-thong','toi-gian','dan-gian'],
    'color_labels'  => ['trang'=>'Trắng','nau'=>'Nâu','xanh'=>'Xanh','den'=>'Đen','hong'=>'Hồng','kem'=>'Kem'],
    'type_labels'   => ['binh-hoa'=>'Bình hoa & Lọ','am-tra'=>'Bộ ấm trà','chen-bat'=>'Chén bát','tuong'=>'Tượng trang trí'],
    'style_labels'  => ['hien-dai'=>'Hiện đại','truyen-thong'=>'Truyền thống','toi-gian'=>'Tối giản','dan-gian'=>'Dân gian'],
    'color_options' => [
        'trang' => ['label'=>'Trắng','hex'=>'#F0EFEB','border'=>'#ccc',    'check_dark'=>true],
        'nau'   => ['label'=>'Nâu',  'hex'=>'#8B6348','border'=>'#8B6348', 'check_dark'=>false],
        'xanh'  => ['label'=>'Xanh', 'hex'=>'#5B8DB8','border'=>'#5B8DB8', 'check_dark'=>false],
        'den'   => ['label'=>'Đen',  'hex'=>'#2C2C2A','border'=>'#2C2C2A', 'check_dark'=>false],
        'hong'  => ['label'=>'Hồng', 'hex'=>'#E8939A','border'=>'#E8939A', 'check_dark'=>false],
        'kem'   => ['label'=>'Kem',  'hex'=>'#F5E6C8','border'=>'#D4B896', 'check_dark'=>true],
    ],
];

include 'includes/category-page.php';