<?

require 'admin/config.php';
require 'admin/lang.php';
require 'lib/func.php';

session_name(SES_NAME);
session_start();

dbconnect($config['dbhost'], $config['dbname'], $config['dblogin'], $config['dbpassword']);

$q = "SELECT name$englang as name, description$englang as description FROM ".TABLE_CURE." WHERE cure_id=14";
$sql1 = mysql_query($q) or Error(1, __FILE__, __LINE__);
$info = @mysql_fetch_array($sql1);
$name = $info['name'];
$description = $info['description'];

switch($_SESSION['messageType']){
    default:
        echo '
        <style>
            .pop-content{
                display: table-cell;
                vertical-align: middle;
                width: 560px;
                height: 160px;
                color: #fff;
                font-family: Arial,sans-serif;
                font-size: 16px;
                line-height: 1.3;
                border:none;
                background: url("/img/medicine_popup_window.png") no-repeat;
            }
            .pop-content a{
                color: #fff;
            }
            .pop-content .u{
                font-size: 18px;
                line-height: 1.2em;
                text-transform: uppercase;
            }
            .pop-content .centered{
                text-align: center;
            }
        </style>
        ';
    break;
    case 'feedback.sent':
        echo '
        <style>
            .pop-content{
                display: table-cell;
                vertical-align: middle;
                width: 667px;
                height: 214px;
                color: #00848c;
                font-family: Arial,sans-serif;
                font-size: 16px;
                font-style: italic;
                font-weight: bold;
                line-height: 1.3;
                border:none;
                background: url("/img/feedbackPopup1.jpg") no-repeat;
            }
            .pop-content h2{
                font-size: 18px;
                line-height: 1.2;
                margin-bottom: 30px;
            }
            .pop-content a{
                color: #fff;
            }
            .pop-content .u{
                font-size: 18px;
                line-height: 1.2em;
                text-transform: uppercase;
            }
            .pop-content .centered{
                text-align: center;
            }
        </style>
        ';
    break;
}

?>

<script language="javascript">
    $(document).ready(function(){
        var content = $('.root_popup_message').html();
        $('.pop-content .centered').html(content);
    });

</script>

<div class="pop-content">
    <div class="centered">
        <?= $_SESSION['lastMessage'] ?>
    </div>
</div>
