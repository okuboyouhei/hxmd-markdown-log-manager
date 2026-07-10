/**
 * HXMD Admin JS
 * clipboard API のフォールバック処理 + MDツールバー
 */

/**
 * textarea の選択範囲を指定のマーカーで囲む／外す（トグル）
 *
 * @param {string} textareaId
 * @param {string} marker '**' または '~~'
 */
function hxmdToggleWrap( textareaId, marker ) {
	const ta = document.getElementById( textareaId );
	if ( ! ta ) return;

	const start = ta.selectionStart;
	const end   = ta.selectionEnd;
	if ( start === end ) {
		ta.focus();
		return;
	}

	const len      = marker.length;
	const before   = ta.value.slice( 0, start );
	const selected = ta.value.slice( start, end );
	const after    = ta.value.slice( end );

	if ( selected.startsWith( marker ) && selected.endsWith( marker ) && selected.length > len * 2 ) {
		const inner = selected.slice( len, -len );
		ta.value = before + inner + after;
		ta.setSelectionRange( start, start + inner.length );
	} else if ( before.endsWith( marker ) && after.startsWith( marker ) ) {
		ta.value = before.slice( 0, -len ) + selected + after.slice( len );
		ta.setSelectionRange( start - len, end - len );
	} else {
		ta.value = before + marker + selected + marker + after;
		ta.setSelectionRange( start, end + len * 2 );
	}
	ta.focus();
	ta.dispatchEvent( new Event( 'input', { bubbles: true } ) );
}

/**
 * 選択範囲（または現在行）をリスト化／解除する（トグル）
 *
 * @param {string} textareaId
 * @param {string} type 'ul' または 'ol'
 */
function hxmdToggleList( textareaId, type ) {
	const ta = document.getElementById( textareaId );
	if ( ! ta ) return;

	let start = ta.selectionStart;
	let end   = ta.selectionEnd;

	// 選択範囲を行単位に拡張
	while ( start > 0 && ta.value[ start - 1 ] !== '\n' ) { start--; }
	while ( end < ta.value.length && ta.value[ end ] !== '\n' ) { end++; }

	const before = ta.value.slice( 0, start );
	const block  = ta.value.slice( start, end );
	const after  = ta.value.slice( end );
	const lines  = block.split( '\n' );

	const ulRe = /^- /;
	const olRe = /^\d+\. /;
	const isAlready = lines.every( l => l.trim() === '' || ( type === 'ul' ? ulRe.test( l ) : olRe.test( l ) ) );

	let result;
	if ( isAlready ) {
		// リスト解除
		result = lines.map( l => l.replace( type === 'ul' ? ulRe : olRe, '' ) ).join( '\n' );
	} else {
		// リスト化（既存の別種マーカーは付け替え）
		let n = 1;
		result = lines.map( l => {
			if ( l.trim() === '' ) return l;
			const clean = l.replace( ulRe, '' ).replace( olRe, '' );
			return type === 'ul' ? '- ' + clean : ( n++ ) + '. ' + clean;
		} ).join( '\n' );
	}

	ta.value = before + result + after;
	ta.setSelectionRange( start, start + result.length );
	ta.focus();
	ta.dispatchEvent( new Event( 'input', { bubbles: true } ) );
}

/**
 * 後方互換用エイリアス
 *
 * @param {string} textareaId
 */
function hxmdToggleBold( textareaId ) {
	hxmdToggleWrap( textareaId, '**' );
}

/**
 * タブ区切りテキスト（Excel/スプレッドシート/Googleドキュメントの表）を
 * Markdownテーブルに変換する
 *
 * @param {string} text
 * @returns {string|null} 変換後MD。表と判定できなければ null
 */
function hxmdTsvToMdTable( text ) {
	const lines = text.replace( /\r\n/g, '\n' ).replace( /\n+$/, '' ).split( '\n' );

	// 2行以上、全行にタブが含まれる場合のみ表と判定
	if ( lines.length < 2 ) return null;
	if ( ! lines.every( l => l.includes( '\t' ) ) ) return null;

	const rows = lines.map( l => l.split( '\t' ).map( c => c.trim().replace( /\|/g, '\\|' ) ) );
	const cols = Math.max( ...rows.map( r => r.length ) );

	// 列数を揃える
	const norm = rows.map( r => {
		while ( r.length < cols ) r.push( '' );
		return r;
	} );

	const header = '| ' + norm[0].join( ' | ' ) + ' |';
	const sep    = '|' + Array( cols ).fill( ' --- ' ).join( '|' ) + '|';
	const body   = norm.slice( 1 ).map( r => '| ' + r.join( ' | ' ) + ' |' ).join( '\n' );

	return header + '\n' + sep + '\n' + body;
}

/**
 * HTML（Googleドキュメント等のリッチテキスト）をMarkdownに変換する。
 * 対応: h1-h3, strong/b, s/del, ul/ol/li, table, p/br/div
 *
 * @param {string} html
 * @returns {string}
 */
function hxmdHtmlToMd( html ) {
	const doc  = new DOMParser().parseFromString( html, 'text/html' );
	const body = doc.body;

	function esc( text ) {
		return text.replace( /\s+/g, ' ' );
	}

	function convertTable( table ) {
		const rows = [ ...table.querySelectorAll( 'tr' ) ].map( tr =>
			[ ...tr.querySelectorAll( 'th, td' ) ].map( cell =>
				inline( cell ).trim().replace( /\|/g, '\\|' ).replace( /\n/g, ' ' )
			)
		);
		if ( ! rows.length ) return '';
		const cols = Math.max( ...rows.map( r => r.length ) );
		rows.forEach( r => { while ( r.length < cols ) r.push( '' ); } );
		const header = '| ' + rows[0].join( ' | ' ) + ' |';
		const sep    = '|' + Array( cols ).fill( ' --- ' ).join( '|' ) + '|';
		const bodyMd = rows.slice( 1 ).map( r => '| ' + r.join( ' | ' ) + ' |' ).join( '\n' );
		return header + '\n' + sep + ( bodyMd ? '\n' + bodyMd : '' );
	}

	// インライン要素の変換（strong/b/s/del はここで処理）
	function inline( node ) {
		let out = '';
		node.childNodes.forEach( child => {
			if ( child.nodeType === Node.TEXT_NODE ) {
				out += esc( child.textContent );
				return;
			}
			if ( child.nodeType !== Node.ELEMENT_NODE ) return;
			const tag   = child.tagName.toLowerCase();
			const inner = inline( child ).trim();
			if ( ! inner && tag !== 'br' ) return;

			// Googleドキュメントは太字を font-weight:700 の span で出すことがある
			const fw     = child.style ? child.style.fontWeight : '';
			const isBold = tag === 'strong' || tag === 'b' || fw === '700' || fw === 'bold';
			const isStrike = tag === 's' || tag === 'del' || tag === 'strike' ||
				( child.style && child.style.textDecoration && child.style.textDecoration.includes( 'line-through' ) );

			if ( tag === 'br' ) {
				out += '\n';
			} else if ( isBold && isStrike ) {
				out += '**~~' + inner + '~~**';
			} else if ( isBold ) {
				out += '**' + inner + '**';
			} else if ( isStrike ) {
				out += '~~' + inner + '~~';
			} else {
				out += inline( child );
			}
		} );
		return out;
	}

	// リストの変換（ネスト対応）
	function convertList( listEl, depth ) {
		let out = '';
		let n   = 1;
		const tag    = listEl.tagName.toLowerCase();
		const indent = '  '.repeat( depth );
		[ ...listEl.children ].forEach( li => {
			if ( li.tagName.toLowerCase() !== 'li' ) return;
			// li直下のテキスト（ネストリストを除いたインライン部分）
			const liClone = li.cloneNode( true );
			[ ...liClone.querySelectorAll( 'ul, ol' ) ].forEach( el => el.remove() );
			const marker = tag === 'ul' ? '- ' : ( n++ ) + '. ';
			const text   = inline( liClone ).trim();
			if ( text ) out += indent + marker + text + '\n';
			// ネストされたリストを再帰処理
			[ ...li.children ].forEach( sub => {
				const st = sub.tagName.toLowerCase();
				if ( st === 'ul' || st === 'ol' ) {
					out += convertList( sub, depth + 1 );
				}
			} );
		} );
		return out;
	}

	// ブロック要素の変換
	function block( node, listDepth = 0 ) {
		let out = '';
		node.childNodes.forEach( child => {
			if ( child.nodeType === Node.TEXT_NODE ) {
				const t = child.textContent.trim();
				if ( t ) out += esc( t );
				return;
			}
			if ( child.nodeType !== Node.ELEMENT_NODE ) return;
			const tag = child.tagName.toLowerCase();

			if ( /^h[1-6]$/.test( tag ) ) {
				const level = Math.min( parseInt( tag[1], 10 ), 3 );
				out += '\n\n' + '#'.repeat( level ) + ' ' + inline( child ).trim() + '\n\n';
			} else if ( tag === 'ul' || tag === 'ol' ) {
				out += '\n' + convertList( child, 0 ) + '\n';
			} else if ( tag === 'table' ) {
				out += '\n\n' + convertTable( child ) + '\n\n';
			} else if ( tag === 'p' || tag === 'div' ) {
				// 内部にブロック要素があれば再帰、なければインライン処理
				if ( child.querySelector( 'h1,h2,h3,h4,h5,h6,ul,ol,table,p' ) ) {
					out += block( child, listDepth );
				} else {
					const content = inline( child ).trim();
					if ( content ) out += content + '\n\n';
				}
			} else if ( tag === 'br' ) {
				out += '\n';
			} else {
				out += block( child, listDepth );
			}
		} );
		return out;
	}

	return block( body )
		.replace( /\n{3,}/g, '\n\n' )
		.trim();
}

/**
 * HTMLに変換価値のある構造（見出し・リスト・表・太字）が含まれるか
 *
 * @param {string} html
 * @returns {boolean}
 */
function hxmdHtmlHasStructure( html ) {
	return /<(h[1-6]|ul|ol|table|strong|b|s|del|strike)[\s>]/i.test( html ) ||
		/font-weight:\s*(700|bold)/i.test( html ) ||
		/text-decoration:[^;"]*line-through/i.test( html );
}

/**
 * textarea にペーストハンドラを登録する。
 * 優先順: HTML構造 → タブ区切り表 → 通常ペースト
 *
 * @param {string} textareaId
 */
function hxmdEnableTablePaste( textareaId ) {
	const ta = document.getElementById( textareaId );
	if ( ! ta ) return;

	ta.addEventListener( 'paste', function ( e ) {
		const cd   = e.clipboardData || window.clipboardData;
		const html = cd.getData( 'text/html' );
		const text = cd.getData( 'text' );

		let md     = null;
		let label  = '';

		// 1. HTML構造があればMD変換を試みる（Googleドキュメント等）
		if ( html && hxmdHtmlHasStructure( html ) ) {
			md    = hxmdHtmlToMd( html );
			label = '書式付きテキストを検出しました。見出し・太字・リスト・表をMarkdownに変換して貼り付けますか？\n（キャンセルでプレーンテキストのまま貼り付け）';
		}
		// 2. タブ区切りの表（Excel/スプレッドシート）
		else {
			md = hxmdTsvToMdTable( text );
			if ( md ) {
				label = '表データを検出しました。Markdownテーブルに変換して貼り付けますか？\n（キャンセルでそのまま貼り付け）';
			}
		}

		if ( ! md ) return; // 変換対象なし → 通常ペースト
		if ( ! window.confirm( label ) ) return;

		e.preventDefault();
		const start  = ta.selectionStart;
		const end    = ta.selectionEnd;
		const before = ta.value.slice( 0, start );
		const after  = ta.value.slice( end );

		const pre  = before && ! before.endsWith( '\n\n' ) ? ( before.endsWith( '\n' ) ? '\n' : '\n\n' ) : '';
		const post = after && ! after.startsWith( '\n' ) ? '\n' : '';

		ta.value = before + pre + md + post + after;
		const pos = ( before + pre + md ).length;
		ta.setSelectionRange( pos, pos );
		ta.focus();
		ta.dispatchEvent( new Event( 'input', { bubbles: true } ) );
	} );
}

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

/**
 * Alpine コンポーネント: ログ一覧
 * （list.php から移設）
 */
function hxmdList() {
	return {
		selected: [],
		copied: false,
		toggleAll( e ) {
			const checkboxes = document.querySelectorAll( '.hxmd-table tbody input[type="checkbox"]' );
			this.selected = e.target.checked ? [ ...checkboxes ].map( c => parseInt( c.value ) ) : [];
		},
		async copyOneMd( id, e ) {
			const btn = e.target;
			const md  = await this.fetchMd( [ id ] );
			if ( ! md ) return;
			await hxmdCopyText( md );
			const orig = btn.textContent;
			btn.textContent = 'コピーしました！';
			setTimeout( () => btn.textContent = orig, 2000 );
		},
		async bulkCopyMd() {
			const md = await this.fetchMd( this.selected );
			if ( ! md ) return;
			await hxmdCopyText( md );
			this.copied = true;
			setTimeout( () => this.copied = false, 2000 );
		},
		async fetchMd( ids ) {
			const body = new FormData();
			body.append( 'action', 'hxmd_get_md' );
			body.append( '_ajax_nonce', hxmdData.nonce );
			ids.forEach( id => body.append( 'ids[]', id ) );
			const res  = await fetch( hxmdData.ajaxUrl, { method: 'POST', body } );
			const json = await res.json();
			return json.success ? json.data.md : null;
		},
	};
}

/**
 * Alpine コンポーネント: MDプレビュー
 * （edit.php から移設）
 */
function hxmdPreview( id ) {
	return {
		md: '読み込み中...',
		copied: false,
		async init() {
			this.md = await this.fetchMd( [ id ] );
		},
		async copy() {
			await hxmdCopyText( this.md );
			this.copied = true;
			setTimeout( () => this.copied = false, 2000 );
		},
		async fetchMd( ids ) {
			const body = new FormData();
			body.append( 'action', 'hxmd_get_md' );
			body.append( '_ajax_nonce', hxmdData.nonce );
			ids.forEach( i => body.append( 'ids[]', i ) );
			const res  = await fetch( hxmdData.ajaxUrl, { method: 'POST', body } );
			const json = await res.json();
			return json.success ? json.data.md : '取得に失敗しました';
		},
	};
}

/**
 * Alpine コンポーネント: 設定画面
 * 保存済みデータは hxmdData.customTypes / hxmdData.categories 経由で受け取る
 * （settings.php から移設）
 */
function hxmdSettings() {
	return {
		customTypes: hxmdData.customTypes || [],
		categories: hxmdData.categories || [],
		addType()         { this.customTypes.push( { key: '', label: '' } ); },
		removeType( i )   { this.customTypes.splice( i, 1 ); },
		addCategory()     { this.categories.push( { key: '', label: '' } ); },
		removeCategory( i ) { this.categories.splice( i, 1 ); },
	};
}

/* 編集画面のペーストハンドラ初期化（edit.php から移設） */
document.addEventListener( 'DOMContentLoaded', function () {
	hxmdEnableTablePaste( 'body' );
	hxmdEnableTablePaste( 'instruction' );
} );

/* MDツールバーと削除確認のイベントデリゲーション（インラインハンドラ排除） */
document.addEventListener( 'DOMContentLoaded', function () {
	document.querySelectorAll( '.hxmd-md-btn' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			const target = btn.dataset.target;
			if ( 'wrap' === btn.dataset.md ) {
				hxmdToggleWrap( target, btn.dataset.marker );
			} else if ( 'list' === btn.dataset.md ) {
				hxmdToggleList( target, btn.dataset.list );
			}
		} );
	} );

	const deleteForm = document.querySelector( '.hxmd-delete-form' );
	if ( deleteForm ) {
		deleteForm.addEventListener( 'submit', function ( e ) {
			if ( ! window.confirm( 'このログを削除しますか？' ) ) {
				e.preventDefault();
			}
		} );
	}
} );
