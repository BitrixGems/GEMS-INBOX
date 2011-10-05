<?php
/**
 * Перехватчик непойманных исключений
 *
 * @author 		Vladimir Savenkov
 * @since		01.09.2010
 */
class BGExceptionInterceptor {

	//-----API
	
	public static function on(){
		return self::getInstance()->_on();
	}
	
	public static function off(){
		return self::getInstance()->_off();
	}
	
	public static function handleException( Exception $oException ){
		return self::getInstance()->_handleException( $oException );
	}
	
	public static function mail( $sSubject, $sMessage ){
		 return self::getInstance()->_mail( $sSubject, $sMessage );
	}
	
	public static function mailException( Exception $oException, $sCustomTitle = '', $sCustomMessage = '' ) {
		return self::getInstance()->_mailException( $oException, $sCustomTitle, $sCustomMessage );
	}
	
	
	//-----Technical stuff
	
	protected static $oInstance = null;
	
	protected $sMail = '';
	protected $mCallbackFunction = null;
	protected $bAllowDirectDump = false;
	
	protected $aHandledErrorTypes = array(E_ERROR);
	protected $sPage4Redirect = '/500.php';
	
	protected $bEnabled = false;
	
	public static function configure( $sMail = '', $aErrorTypes = array( E_ERROR ) ,$sRedirectToPage = "/500.php", $bAllowDirectDump = false, $mCallbackFunction = null ){
		if( self::$oInstance === null ){
			self::$oInstance = new self( $sMail, $aErrorTypes, $sRedirectToPage, $bAllowDirectDump, $mCallbackFunction );
		}
	}
	
	public static function getInstance(){
		if( self::$oInstance === null ) throw new Exception( 'BGExceptionInterceptor must be configured with CiVExceptionInterceptor::configure() method!' );
		return self::$oInstance;
	}
	protected function __clone(){}
	
	protected function __construct( $sMail, $aErrorTypes, $sRedirectToPage, $bAllowDirectDump, $mCallbackFunction ) {
		if( !is_array( $aErrorTypes ) ) $aErrorTypes = array( E_ERROR );
		
		$this->sMail 				= $sMail;
		$this->mCallbackFunction 	= $mCallbackFunction;
		$this->bAllowDirectDump 	= (bool) $bAllowDirectDump;
		$this->aHandledErrorTypes 	= $aErrorTypes;
		
		$this->_on();
		
		if ( isset( $_GET['EXCEPTION_DEBUG_ON'] ) ) {
			setcookie("EXCEPTION_DEBUG_ON", true);
		}
		if ( isset( $_GET['EXCEPTION_DEBUG_OFF'] ) ) {
			setcookie("EXCEPTION_DEBUG_ON");
		}
	}
	
	public function _on(){
		set_exception_handler( array($this, '_handleException') );
		register_shutdown_function( array($this, 'shutdownFunction') );
		$this->bEnabled = true;
	}
	
	public function _off(){
		restore_exception_handler();
		$this->bEnabled = false;
	}
	
	public function shutdownFunction() {
		if( $this->bEnabled ){
			$aError = error_get_last();
			
			if( ( $aError['type'] != E_ERROR ) && in_array( $aError['type'], $this->aHandledErrorTypes ) ){
				$this->_mailException( new Exception( 'Error [Type:'.$aError['type'].']:' . $aError['message'] . ' [' . $aError['file'] . ':' . $aError['line'] . ']'  ) );
			}
			
			if(  $aError['type'] == E_ERROR ){
				$this->_handleException( new Exception( 'Fatal Error:' . $aError['message'] . ' [' . $aError['file'] . ':' . $aError['line'] . ']' ) );
			}
						
		}
	}

	public function setCallbackFunction( $mCallbackFunction ) {
		if ( !is_callable( $mCallbackFunction ) ) {
			return false;
		}
		$this->mCallbackFunction = $mCallbackFunction;
	}

	public function _handleException( $oException ) {
		if( $this->bEnabled ){
			if ( $this->mCallbackFunction !== null ) {
				$mResult = call_user_func_array( $this->mCallbackFunction, array($oException) );
				if ( $mResult === true )
					return $mResult;
			}
			if ( $_COOKIE['EXCEPTION_DEBUG_ON'] && ( $this->bAllowDirectDump ) ) {
				$sMessage = 'Uncaught ' . get_class( $oException ) . ', code: ' . $oException->getCode() . "\n\nMessage: " . htmlentities( $oException->getMessage() ) . "\n\n";
				$sMessage.= "Exception Dump:\n\n" . $oException->getMessage() . '( ' . $oException->getCode() . ' )' . '[' . $oException->getFile() . ':' . $oException->getLine() . ']' . "\n\n" . $oException->getTraceAsString() . "\n\n";
				$sMessage.= '$_SERVER Dump:' . "\n\n" . var_export( $_SERVER, true ) . "\n\n";
				echo '<pre>' . $sMessage . '</pre>';
				$this->_mailException( $oException );
			} else {
				$this->_mailException( $oException );
				$this->redirectTo500();
			}
			ob_end_flush();
		}
	}
	
	public function _mail( $sSubject, $sMessage ){
		if( empty( $this->sMail ) ) return false;
		return mail( $this->sMail, $sSubject, $sMessage );
	}

	public function _mailException( Exception $oException, $sCustomTitle = '', $sCustomMessage = '' ) {
		if ( empty( $this->sMail ) )
			return;
		$sMessage = $sCustomMessage."\n\n".'Uncaught ' . get_class( $oException ) . ', code: ' . $oException->getCode() . "\n\nMessage: " . htmlentities( $oException->getMessage() ) . "\n\nPage: ".$_SERVER['HTTP_HOST'].$_SERVER['REDIRECT_URL']."\n\n";
		$sMessage.= "Exception Dump:\n\n" . $oException->getMessage() . '( ' . $oException->getCode() . ' )' . '[' . $oException->getFile() . ':' . $oException->getLine() . ']' . "\n\n" . $oException->getTraceAsString() . "\n\n";
		$sMessage.= '$_SERVER Dump:' . "\n\n" . var_export( $_SERVER, true ) . "\n\n";
		$this->_mail( $sCustomTitle." ".$_SERVER['HTTP_HOST'] . ': UNHANDLED EXCEPTION!', $sMessage );
	}

	protected function redirectTo500() {
		header( 'Location: '.$this->sPage4Redirect );
		die();
	}

}