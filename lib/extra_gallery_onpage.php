<?
require $_SERVER['DOCUMENT_ROOT'].'/admin/config.php';
require $_SERVER['DOCUMENT_ROOT'].'/lib/func.php';
require $_SERVER['DOCUMENT_ROOT'].'/lib/func_user.php';
require $_SERVER['DOCUMENT_ROOT'].'/admin/lang.php';
require $_SERVER['DOCUMENT_ROOT'].'/admin/settings.php';

session_name(SES_NAME);
session_start();

dbconnect($config['dbhost'], $config['dbname'], $config['dblogin'], $config['dbpassword']);
$englang = (int)@$_POST['englang'];

switch($_POST['type']){
    default:

        try{
            $query = "SELECT * FROM ".TABLE_PHOTO." WHERE owner_id=".$_POST['id']." AND owner=9 ORDER BY ord";

            if(!$sql = mysql_query($query)) throw new Exception($query);

            $rows = array();
            while($row = @mysql_fetch_array($sql, MYSQL_ASSOC)){
                $row['alt'] =  $englang ? $row['alt_en'] : $row['alt'];
				$rows[] = $row;
            }

            $html = '
            <div class="extra-gallery-inner type1">
                <div class="connected-carousels">
                    <div class="stage">
                        <div class="carousel carousel-stage">
                            <ul>';
                            foreach($rows as $row){
                                $html .= '<li>';
                                if(!empty($row['description'])) {
                                    $html .= '<div><div>';
                                    $html .= $row['description'];
                                    $html .= '</div></div>';
                                }
                                $html .= '<img src="/images/gallery/'.$row['photo_id'].'.jpg" width="570" height="360" alt="'.$row['alt'].'"></li>';
                            }
                            $html .= '
                            </ul>
                        </div>
                        <p class="photo-credits">

                        </p>
                        <a href="#" class="prev prev-stage"><span></span></a>
                        <a href="#" class="next next-stage"><span></span></a>
                    </div>

                    <div class="navigation">';
                        if(count($rows) > 3) {
                            $html .= '
                            <a href="#" class="prev prev-navigation"></a>
                            <a href="#" class="next next-navigation"></a>';
                        }
                        $html .= '
                        <div class="carousel carousel-navigation">
                            <ul>';
                            foreach($rows as $row){
                                $html .= '<li><img src="/images/gallery/'.$row['photo_id'].'-s.jpg" width="105" height="67" alt="'.$row['alt'].'"></li>';
                            }
                            $html .= '
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="clear"></div>
            </div>
            ';

        }catch (Exception $e){
            //$html = $e->getMessage();
        }

    break;

    case 2:
    $active[$_POST['section']] = ' active';

    $page_id = (int)@$_POST['id'];
	
	$owner = $_POST['section']==1 ? 2 : ($_POST['section']==2 ? 4 : 1);
	$query = "SELECT * FROM ".TABLE_PHOTO." WHERE owner_id=$page_id AND owner=$owner AND public ORDER BY ord";

	if(!$sql = mysql_query($query)) throw new Exception($query);
			
	$rows = array();
	while($row = @mysql_fetch_array($sql, MYSQL_ASSOC)){
		$row['alt'] =  $englang ? $row['alt_en'] : $row['alt'];
		$rows[] = $row;
	}
	

	$html .= '

	<div class="extra-gallery-tabs">
        <ul>
            <li class="photo'.$active[0].'" onclick="initExtraGallery(2, '.$page_id.', 0, 0, '.$englang.');"></li>
            <li class="panorama'.$active[2].'" onclick="initExtraGallery(2, '.$page_id.', 2, 0, '.$englang.');"></li>
            <li class="video'.$active[1].'" onclick="initExtraGallery(2, '.$page_id.', 1, 0, '.$englang.');"></li>
        </ul>
	</div>

	<div class="extra-gallery-inner">';
	
	switch($_POST['section']){
		default:
			$html .= '
				<!-- Tab '.$_POST['section'].' -->
				<div class="extra-gallery-sections">
					<div class="connected-carousels">
						<div class="stage">
							<div class="carousel carousel-stage">
								<ul>';
                            foreach($rows as $row){
								$html .= '
									<li><img src="/images/item/'.$row['photo_id'].'.'.$row['ext_b'].'" width="570" height="360" alt=""></li>';
							}
			                $html .= '
								</ul>
							</div>
							<p class="photo-credits">

							</p>
							<a href="#" class="prev prev-stage"><span></span></a>
							<a href="#" class="next next-stage"><span></span></a>
						</div>

						<div class="navigation">';
                            if(count($rows) > 3) {
                                $html .= '
                                <a href="#" class="prev prev-navigation"></a>
                                <a href="#" class="next next-navigation"></a>';
                            }
                            $html .= '<div class="carousel carousel-navigation">
								<ul>';
                                foreach($rows as $row){
                                    $html .= '
                                    <li><img src="/images/item/'.$row['photo_id'].'-s.'.$row['ext'].'" width="105" height="67" alt=""></li>';
                                }
			                    $html .= '
								</ul>
							</div>
						</div>
					</div>

					<div class="extra-gallery-info">
					    <div class="extra-gallery-close" onclick="hideGalleryInfo();"></div>';
                            foreach($rows as $row){
								$html .= '
                                <div class="items">
                                    '.($row['alt'] ? '<h1>'.$row['alt'].'</h1>' : '').'
                                    '.$row['description'].'
                                </div>';
							}
			        $html .= '
					</div>
				</div>
				<!-- End of Tab '.$_POST['section'].' -->';
			break;
		case 1:
			$html .= '
				<!-- Tab '.$_POST['section'].' -->
				<div class="extra-gallery-sections">
					<div class="connected-carousels">
						<div class="stage">
							<div class="carousel carousel-stage">
								<ul>';
                            foreach($rows as $row){
								$html .= '
									<li>'.$row['description'].'</li>';
							}
			                    $html .= '
								</ul>
							</div>
							<p class="photo-credits">

							</p>
							<a href="#" class="prev prev-stage"><span></span></a>
							<a href="#" class="next next-stage"><span></span></a>
						</div>

						<div class="navigation">';
                            if(count($rows) > 3) {
                                $html .= '
                                <a href="#" class="prev prev-navigation"></a>
                                <a href="#" class="next next-navigation"></a>';
                            }
                            $html .= '<div class="carousel carousel-navigation">
                                <ul>';
                                foreach($rows as $row){
                                    $html .= '
                                    <li><img src="/images/virtual/'.$row['photo_id'].'-s.'.$row['ext'].'" width="105" height="67" alt=""></li>';
                                }
                                $html .= '
								</ul>
							</div>
						</div>
					</div>

					<div class="extra-gallery-info">';
                            foreach($rows as $row){
								$html .= '
						<div class="items">
							'.$row['alt'].'
						</div>';
							}
                    $html .= '
					</div>
				</div>
				<!-- End of Tab '.$_POST['section'].' -->';
			break;
		case 2:
			$html .= '
				<!-- Tab '.$_POST['section'].' -->
				<div class="extra-gallery-sections">
					<div class="connected-carousels">
						<div class="stage">
							<div class="carousel carousel-stage">
								<ul>';
                                foreach($rows as $row){
                                    $html .= '
                                        <li><a target="_blank" title="" href="'.$row['description'].'"><img src="/images/virtual/'.$row['photo_id'].'.'.$row['ext_b'].'" width="570" height="360" alt=""></li></a>';
                                }
			                    $html .= '
								</ul>
							</div>
							<p class="photo-credits">

							</p>
							<a href="#" class="prev prev-stage"><span></span></a>
							<a href="#" class="next next-stage"><span></span></a>
						</div>

						<div class="navigation">';
                            if(count($rows) > 3) {
                                $html .= '
                                <a href="#" class="prev prev-navigation"></a>
                                <a href="#" class="next next-navigation"></a>
                                <div class="carousel carousel-navigation">';
                            }
                            $html .= '<div class="carousel carousel-navigation">
								<ul>';
                                    foreach($rows as $row){
                                        $html .= '
                                        <li><img src="/images/virtual/'.$row['photo_id'].'-s.'.$row['ext'].'" width="105" height="67" alt=""></li>';
                                    }
                                    $html .= '
								</ul>
							</div>
						</div>
					</div>

					<div class="extra-gallery-info">
					    <div class="extra-gallery-close" onclick="hideGalleryInfo();"></div>';
                            foreach($rows as $row){
								$html .= '
						        <div class="items">
                                    '.$row['alt'].'
                                    <br /><br />
                                    <a target="_blank" title="" href="'.$row['description'].'">Посмотреть</a>
                                </div>';
							}
			        $html .= '
					</div>
				</div>
				<!-- End of Tab '.$_POST['section'].' -->';
			break;
	}

	    $html .= '
		<div class="clear"></div>
	</div>';

    break;
}

echo json_encode(array(
    'html'=>iconv('windows-1251', 'utf-8', $html),
));
?>