/**
 * HXMD Admin JS
 * clipboard API のフォールバック処理
 */

/**
 * テキストをクリップボードにコピーする
 * navigator.clipboard が使えない環境（HTTP）でも動作するフォールバック付き
 *
 * @param {string} text
 * @returns {Promise<void>}
 */
async function hxmdCopyText( text ) {
	if ( navigator.clipboard && window.isSecureContext ) {
		await navigator.clipboard.writeText( text );
		return;
	}
	// HTTP環境用フォールバック（execCommand）
	const textarea = document.createElement( 'textarea' );
	textarea.value = text;
	textarea.style.position = 'fixed';
	textarea.style.opacity  = '0';
	document.body.appendChild( textarea );
	textarea.focus();
	textarea.select();
	document.execCommand( 'copy' );
	document.body.removeChild( textarea );
}
