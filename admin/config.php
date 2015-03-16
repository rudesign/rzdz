<?

require 'config_site.php';

// название страниц, title 
define('TITLE_SEPAR', ' - ');

// страницы без названия
define('NONAME', '[без названия]');

define('SES_NAME', 'zdor');

define('ADMIN_TITLE', 'Страница администатора');
define('ADMIN_GALLERY_PAGE', 'Фотогалерея');

define('IMG_WIDTH', 163);
define('IMG_HEIGHT', 105);
define('MAX_BIG_SIZE', 800);


// параметры загружаемых картинок
global $photo_owner, $photo_dir, $photo_limit;
global $rus_month, $rus_month_1;

// таблицы MySql
define('TABLE_PREFIX', 'zdor_');
define('PAGE_404', 'Ошибка 404');
define('GALLERY_PAGE', 'Галерея');
define('NEWS_PAGE', 'Новости');
define('ORDER_PAGE', 'Бронирование');
define('SITEMAP_PAGE', 'Карта сайта');
define('OPINION_PAGE', 'Отзывы');
define('MEDIA_PAGE', 'Библиотека');
define('SENDPHOTO_PAGE', 'Прислать фото');
define('SEARCH_PAGE', 'Поиск');
define('CITIES_PAGE', 'Курорты');
define('SPEC_PAGE', 'Спецпредложения');
define('QUESTIONNAIRE_PAGE', 'Опрос');

define('TABLE_GALLERY', TABLE_PREFIX.'gallery');
define('TABLE_USER', TABLE_PREFIX.'user');
define('TABLE_ORDER', TABLE_PREFIX.'order');
define('TABLE_DIR', TABLE_PREFIX.'dir');
define('TABLE_PHOTO', TABLE_PREFIX.'photo');
define('TABLE_PAGE', TABLE_PREFIX.'page');
define('TABLE_NEWS', TABLE_PREFIX.'news');
define('TABLE_MENU', TABLE_PREFIX.'menu');
define('TABLE_FIELD', TABLE_PREFIX.'field');
define('TABLE_TOPIMG', TABLE_PREFIX.'topimg');
define('TABLE_BANNER', TABLE_PREFIX.'banner');
define('TABLE_OPINION', TABLE_PREFIX.'opinion');
define('TABLE_REGION', TABLE_PREFIX.'region');
define('TABLE_CITY', TABLE_PREFIX.'city');
define('TABLE_RECOM', TABLE_PREFIX.'recom');
define('TABLE_SEARCHSTAT', TABLE_PREFIX.'searchstat');
define('TABLE_RATINGIP', TABLE_PREFIX.'ratingip');
define('TABLE_FORM', TABLE_PREFIX.'form');
define('TABLE_FIELD2', TABLE_PREFIX.'field2');
define('TABLE_CURE', TABLE_PREFIX.'cure');
define('TABLE_CUREHOTEL', TABLE_PREFIX.'curehotel');
define('TABLE_CURESTR', TABLE_PREFIX.'curestr');
define('TABLE_SPEC', TABLE_PREFIX.'spec');
define('TABLE_GEST', TABLE_PREFIX.'gest');
define('TABLE_GTEMA', TABLE_PREFIX.'gtema');
define('TABLE_SLIDER', TABLE_PREFIX.'slider');
define('TABLE_TABLE', TABLE_PREFIX.'table');
define('TABLE_QUESTFIELD', TABLE_PREFIX.'questfield');
define('TABLE_QUESTIONNAIRE', TABLE_PREFIX.'questionnaire');
define('TABLE_QUESTMAIL', TABLE_PREFIX.'questmail');

$photo_owner = array(
	'item'=>1,
	'video'=>2,
	'pdf'=>3,
	'virtual'=>4,
	'cure'=>5,
	'logo'=>6,
	'news'=>7,
	'slide'=>8,
	'gallery'=>9,
	'topimg'=>10,
	'brochure'=>11,
	'block'=>12,
	'spec'=>13,
	'specblock'=>14,
	'slider'=>15,
	'slider_preview'=>16,
	'cure_part'=>17,
	'logo_en'=>18,
	'brochure_en'=>19,
	'license'=>20
);
$photo_dir = array(
	'item'=>'item',
	'video'=>'video',
	'pdf'=>'pdf',
	'virtual'=>'virtual',
	'cure'=>'cure',
	'logo'=>'logo',
	'news'=>'news',
	'slide'=>'slide',
	'gallery'=>'gallery',
	'topimg'=>'topimg',
	'brochure'=>'brochure',
	'block'=>'news',
	'spec'=>'spec',
	'specblock'=>'spec',
	'slider'=>'slider',
	'slider_preview'=>'slider',
	'cure_part'=>'cure',
	'logo_en'=>'logo',
	'brochure_en'=>'brochure',
	'license'=>'license'
);
$photo_limit = array(
	'item'=>1000,
	'video'=>1000,
	'pdf'=>1000,
	'virtual'=>1000,
	'cure'=>1000,
	'logo'=>1,
	'news'=>1,
	'slide'=>1,
	'gallery'=>1000,
	'topimg'=>1,
	'brochure'=>1,
	'block'=>1,
	'spec'=>1,
	'specblock'=>1,
	'slider'=>1,
	'slider_preview'=>1,
	'cure_part'=>1,
	'logo_en'=>1,
	'brochure_en'=>1,
	'license'=>1
);

$rus_month = array('', 'январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь');
$rus_month_1 = array('', 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');

$city_parent_dir_id = 7;
?>