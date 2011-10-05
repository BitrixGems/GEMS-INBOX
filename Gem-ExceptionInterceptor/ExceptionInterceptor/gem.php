<?php
/**
 * Подключаем jQuery :)
 *
 * @author		Vladimir Savenkov <iVariable@gmail.com>
 *
 */
class BitrixGem_ExceptionInterceptor extends BaseBitrixGem{

	protected $aGemInfo = array(
		'GEM'			=> 'ExceptionInterceptor',
		'AUTHOR'		=> 'Vladimir Savenkov',
		'DATE'			=> '18.02.2011',
		'VERSION'		=> '0.1',
		'NAME' 			=> 'ExceptionInterceptor',
		'DESCRIPTION' 	=> "Гем, перехватывающий PHP-ошибки (включая Fatal Error) и непойманные исключения и отправляющий их на почту админу с подробным трейсом.",
		'DESCRIPTION_FULL' => 'Если ошибка фатальна (непойманный Exception, Fatal Error), то пользователь перенаправляется на кастомную страницу сайта (задается в настройках гема). Также через API можно установить кастомную callback-функцию на эти события. Также доступны методы mail и mailException( $oException ) отправляющие сообщенияна почту админа (задается в настройках).',
		'CHANGELOG'		=> 'Релизная версия',
		'REQUIREMENTS'	=> '',
	);

	public function initGem(){
		require_once( $this->getGemFolder().'/lib/BGExceptionInterceptor.class.php' );
		$aOptions = $this->getOptions();
		BGExceptionInterceptor::configure(
			$aOptions['sMail'],
			$aOptions['aErrorTypes'],
			$aOptions['sRedirectToPage'],
			$aOptions['bAllowDirectDump'],
			$mCallbackFunction = null
		);
	}

	protected function getDefaultOptions(){
		return array(
			'sMail' => array(
				'name' => 'Кому отсылать уведомления (e-mail\'ы через запятую)',
				'type' => 'text',
				'value' => '',
			),
			'sRedirectToPage' => array(
				'name' => 'Куда перенаправить пользователя при возникновении ошибки',
				'type' => 'text',
				'value' => '/500.php',
			),
			'aErrorTypes' => array(
				'name' => 'Типы ошибок на которые отсылать письмо',
				'type' => 'select',
				'multiple' => true,
				'value' => array( 'E_ERROR' ),
				'options' => array(
					E_ERROR 	=> '[E_ERROR] Фатальная ошибка PHP',
					E_WARNING 	=> '[E_WARNING] Warning PHP',
					E_NOTICE 	=> '[E_NOTICE] Notice PHP',
					E_ALL 		=> '[E_ALL] Все предупреждения PHP'
				),
			),
			'bAllowDirectDump' => array(
				'name' => 'Разрешить прямой вывод ошибки при помощи EXCEPTION_DEBUG_ON',
				'type' => 'checkbox',
				'value' => false,
			),
		);
	}
	
	public function needAdminPage(){
		return true;
	}
	
	public function installGem(){
		if( !file_exists( $_SERVER['DOCUMENT_ROOT'].'/500.php' ) ) copy( $this->getGemFolder().'/helpers/500.php', $_SERVER['DOCUMENT_ROOT'].'/500.php' );
	}
	
	public function unInstallGem(){
		if( file_exists( $_SERVER['DOCUMENT_ROOT'].'/500.php' ) && ( file_get_contents( $_SERVER['DOCUMENT_ROOT'].'/500.php' ) == file_get_contents( $this->getGemFolder().'/helpers/500.php' ) )){
			unlink( $_SERVER['DOCUMENT_ROOT'].'/500.php' );
		}
	}

}
?>