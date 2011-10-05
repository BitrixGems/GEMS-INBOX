<?
/**
 * Защита от зацикливания при эксепшене в инициализации, либо в шаблоне сайта.
 */
session_start();
if( isset( $_SESSION['IV_500_SHOWN'] ) ){
	unset($_SESSION['IV_500_SHOWN']);
	die('500');
}
$_SESSION['IV_500_SHOWN'] = true;
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle( "Ошибка 500" );
?>
<h3>К сожалению, на сайте произошла ошибка</h3>

<p>Приносим свои извинения.</p>
<p>Администрация сайта уже уведомлена об этом, и в ближайшее время ошибка будет исправлена.</p>
<p>В случае Вашего желания предоставить еще какую-либо информацию просьба связаться с администрацией.</p>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
unset($_SESSION['IV_500_SHOWN']);
?>