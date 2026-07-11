<?php
/**
 * HXMD_Post_Export — WordPress投稿の構造化MDエクスポート
 *
 * 投稿・固定ページを選択して、AI可読な構造化Markdownとしてコピーできる。
 * HTML→MD変換はサーバーサイドで行う（見出し・太字・取り消し線・リスト・表・
 * リンク・画像・引用・コードに対応）。
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class HXMD_Post_Export {

	public static function init(): void {
		add_filter( 'post_row_actions', [ __CLASS__, 'row_action' ], 10, 2 );
		add_filter( 'page_row_actions', [ __CLASS__, 'row_action' ], 10, 2 );
	}

	/**
	 * 投稿・固定ページ・カスタム投稿の一覧に「HXMD: MD」行アクションを追加する。
	 * リンク先は投稿エクスポート画面（該当投稿タイプで絞り込み + 検索済み状態）。
	 *
	 * @param array   $actions 既存の行アクション
	 * @param WP_Post $post    投稿
	 * @return array
	 */
	public static function row_action( array $actions, WP_Post $post ): array {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $actions;
		}
		// 注意: 'post_type' はWP管理画面の予約パラメータ（$typenowに影響し
		// 親メニュー解決が edit.php 側に化けて "Cannot load" になる）ため、
		// 独自名 hxmd_pt / hxmd_s を使う。
		$url = add_query_arg(
			[
				'page'    => 'hxmd-post-export',
				'hxmd_pt' => $post->post_type,
				'hxmd_s'  => rawurlencode( $post->post_title ),
			],
			admin_url( 'admin.php' )
		);
		$actions['hxmd_md'] = '<a href="' . esc_url( $url ) . '">HXMD: MD</a>';
		return $actions;
	}

	/**
	 * 投稿1件を構造化MDとして組み立てる。
	 *
	 * @param WP_Post $post 投稿オブジェクト
	 * @return string
	 */
	public static function render_post( WP_Post $post ): string {
		$type_obj   = get_post_type_object( $post->post_type );
		$type_label = $type_obj ? $type_obj->labels->singular_name : $post->post_type;

		$status_labels = [
			'publish' => '公開',
			'draft'   => '下書き',
			'pending' => 'レビュー待ち',
			'private' => '非公開',
			'future'  => '予約済み',
		];
		$status = $status_labels[ $post->post_status ] ?? $post->post_status;

		$lines   = [];
		$lines[] = '# ' . $post->post_title;
		$lines[] = '';
		$lines[] = '- URL: ' . get_permalink( $post );
		$lines[] = '- 投稿タイプ: ' . $type_label;
		$lines[] = '- 公開日: ' . get_the_date( 'Y-m-d', $post );
		$lines[] = '- 更新日: ' . get_the_modified_date( 'Y-m-d H:i', $post );

		// タクソノミー（カテゴリ・タグ・カスタム分類すべて）
		foreach ( get_object_taxonomies( $post->post_type, 'objects' ) as $tax ) {
			if ( ! $tax->public ) {
				continue;
			}
			$terms = get_the_terms( $post->ID, $tax->name );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$lines[] = '- ' . $tax->labels->singular_name . ': ' . implode( ', ', wp_list_pluck( $terms, 'name' ) );
			}
		}

		$lines[] = '- ステータス: ' . $status;
		$lines[] = '';
		$lines[] = '---';
		$lines[] = '';

		// 本文: ブロック→HTML→MD
		$html    = apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- applying the WordPress core content filter (standard way to render post content incl. blocks/shortcodes), not defining a new hook.
		$lines[] = self::html_to_md( $html );

		return implode( "\n", $lines );
	}

	/**
	 * 複数件をまとめてMD化する。
	 *
	 * @param WP_Post[] $posts
	 * @return string
	 */
	public static function render_bulk( array $posts ): string {
		$date  = date_i18n( 'Y-m-d' );
		$total = count( $posts );

		$lines   = [];
		$lines[] = '# HXMD Post Export';
		$lines[] = "Generated: {$date}";
		$lines[] = "Total: {$total} 件";
		$lines[] = '';
		$lines[] = '---';

		foreach ( $posts as $post ) {
			$lines[] = '';
			$lines[] = self::render_post( $post );
			$lines[] = '';
			$lines[] = '---';
		}

		return implode( "\n", $lines );
	}

	/**
	 * HTML→Markdown変換（サーバーサイド）。
	 * hxmd-admin.js の hxmdHtmlToMd と同等の変換ルールをPHPで実装。
	 *
	 * @param string $html
	 * @return string
	 */
	public static function html_to_md( string $html ): string {
		if ( '' === trim( $html ) ) {
			return '';
		}

		$doc = new DOMDocument();
		// 文字化け防止: UTF-8を明示。HTMLの断片なのでエラーは抑制
		libxml_use_internal_errors( true );
		$doc->loadHTML(
			'<?xml encoding="UTF-8"><div id="hxmd-root">' . $html . '</div>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();

		$root = $doc->getElementById( 'hxmd-root' );
		if ( ! $root ) {
			return wp_strip_all_tags( $html );
		}

		$md = self::block_to_md( $root, 0 );

		// 3連以上の空行を圧縮
		$md = preg_replace( "/\n{3,}/", "\n\n", $md );
		return trim( $md );
	}

	/**
	 * ブロック要素の変換（再帰）。
	 *
	 * @param DOMNode $node
	 * @param int     $list_depth
	 * @return string
	 */
	private static function block_to_md( DOMNode $node, int $list_depth ): string {
		$out = '';
		foreach ( $node->childNodes as $child ) {
			if ( XML_TEXT_NODE === $child->nodeType ) {
				$text = trim( $child->textContent );
				if ( '' !== $text ) {
					$out .= preg_replace( '/\s+/u', ' ', $text );
				}
				continue;
			}
			if ( XML_ELEMENT_NODE !== $child->nodeType ) {
				continue;
			}

			$tag = strtolower( $child->nodeName );

			if ( preg_match( '/^h([1-6])$/', $tag, $m ) ) {
				$level = min( (int) $m[1], 4 );
				$out  .= "\n\n" . str_repeat( '#', $level ) . ' ' . trim( self::inline_to_md( $child ) ) . "\n\n";
			} elseif ( 'ul' === $tag || 'ol' === $tag ) {
				$out .= "\n" . self::list_to_md( $child, $list_depth ) . "\n";
			} elseif ( 'table' === $tag ) {
				$out .= "\n\n" . self::table_to_md( $child ) . "\n\n";
			} elseif ( 'blockquote' === $tag ) {
				$inner = trim( self::block_to_md( $child, $list_depth ) );
				$out  .= "\n\n" . preg_replace( '/^/m', '> ', $inner ) . "\n\n";
			} elseif ( 'pre' === $tag ) {
				// コードブロック。言語クラス（language-xxx）があれば拾う
				$code = $child->getElementsByTagName( 'code' )->item( 0 );
				$lang = '';
				if ( $code && $code->getAttribute( 'class' ) && preg_match( '/language-([\w-]+)/', $code->getAttribute( 'class' ), $lm ) ) {
					$lang = $lm[1];
				}
				$content = $code ? $code->textContent : $child->textContent;
				$out    .= "\n\n```" . $lang . "\n" . rtrim( $content ) . "\n```\n\n";
			} elseif ( 'img' === $tag ) {
				$out .= self::img_to_md( $child ) . "\n\n";
			} elseif ( 'hr' === $tag ) {
				$out .= "\n\n---\n\n";
			} elseif ( 'p' === $tag || 'div' === $tag || 'figure' === $tag || 'section' === $tag || 'article' === $tag ) {
				// 内部にブロック要素があれば再帰、なければインライン
				if ( self::has_block_child( $child ) ) {
					$out .= self::block_to_md( $child, $list_depth );
				} else {
					$content = trim( self::inline_to_md( $child ) );
					if ( '' !== $content ) {
						$out .= $content . "\n\n";
					}
				}
			} elseif ( 'br' === $tag ) {
				$out .= "\n";
			} else {
				$out .= self::block_to_md( $child, $list_depth );
			}
		}
		return $out;
	}

	/**
	 * インライン要素の変換。
	 *
	 * @param DOMNode $node
	 * @return string
	 */
	private static function inline_to_md( DOMNode $node ): string {
		$out = '';
		foreach ( $node->childNodes as $child ) {
			if ( XML_TEXT_NODE === $child->nodeType ) {
				$out .= preg_replace( '/\s+/u', ' ', $child->textContent );
				continue;
			}
			if ( XML_ELEMENT_NODE !== $child->nodeType ) {
				continue;
			}

			$tag   = strtolower( $child->nodeName );
			$inner = trim( self::inline_to_md( $child ) );

			if ( 'strong' === $tag || 'b' === $tag ) {
				$out .= '' !== $inner ? "**{$inner}**" : '';
			} elseif ( 's' === $tag || 'del' === $tag || 'strike' === $tag ) {
				$out .= '' !== $inner ? "~~{$inner}~~" : '';
			} elseif ( 'em' === $tag || 'i' === $tag ) {
				$out .= '' !== $inner ? "*{$inner}*" : '';
			} elseif ( 'code' === $tag ) {
				$out .= '' !== $inner ? "`{$inner}`" : '';
			} elseif ( 'a' === $tag ) {
				$href = $child->getAttribute( 'href' );
				$out .= $href && '' !== $inner ? "[{$inner}]({$href})" : $inner;
			} elseif ( 'img' === $tag ) {
				$out .= self::img_to_md( $child );
			} elseif ( 'br' === $tag ) {
				$out .= "\n";
			} else {
				$out .= self::inline_to_md( $child );
			}
		}
		return $out;
	}

	/**
	 * リストの変換（ネスト対応）。
	 *
	 * @param DOMNode $list_el
	 * @param int     $depth
	 * @return string
	 */
	private static function list_to_md( DOMNode $list_el, int $depth ): string {
		$out    = '';
		$n      = 1;
		$tag    = strtolower( $list_el->nodeName );
		$indent = str_repeat( '  ', $depth );

		foreach ( $list_el->childNodes as $li ) {
			if ( XML_ELEMENT_NODE !== $li->nodeType || 'li' !== strtolower( $li->nodeName ) ) {
				continue;
			}
			// li直下のインライン（ネストリストは除外して抽出）
			$clone = $li->cloneNode( true );
			$nested_in_clone = [];
			foreach ( $clone->childNodes as $c ) {
				if ( XML_ELEMENT_NODE === $c->nodeType && in_array( strtolower( $c->nodeName ), [ 'ul', 'ol' ], true ) ) {
					$nested_in_clone[] = $c;
				}
			}
			foreach ( $nested_in_clone as $c ) {
				$clone->removeChild( $c );
			}

			$marker = 'ul' === $tag ? '- ' : ( $n++ ) . '. ';
			$text   = trim( self::inline_to_md( $clone ) );
			if ( '' !== $text ) {
				$out .= $indent . $marker . $text . "\n";
			}
			// ネストリストを再帰
			foreach ( $li->childNodes as $sub ) {
				if ( XML_ELEMENT_NODE === $sub->nodeType && in_array( strtolower( $sub->nodeName ), [ 'ul', 'ol' ], true ) ) {
					$out .= self::list_to_md( $sub, $depth + 1 );
				}
			}
		}
		return $out;
	}

	/**
	 * テーブルの変換。
	 *
	 * @param DOMNode $table
	 * @return string
	 */
	private static function table_to_md( DOMNode $table ): string {
		$rows = [];
		foreach ( $table->getElementsByTagName( 'tr' ) as $tr ) {
			$cells = [];
			foreach ( $tr->childNodes as $cell ) {
				if ( XML_ELEMENT_NODE !== $cell->nodeType ) {
					continue;
				}
				$cell_tag = strtolower( $cell->nodeName );
				if ( 'td' !== $cell_tag && 'th' !== $cell_tag ) {
					continue;
				}
				$text    = trim( self::inline_to_md( $cell ) );
				$cells[] = str_replace( [ '|', "\n" ], [ '\\|', ' ' ], $text );
			}
			if ( ! empty( $cells ) ) {
				$rows[] = $cells;
			}
		}
		if ( empty( $rows ) ) {
			return '';
		}

		$cols = max( array_map( 'count', $rows ) );
		foreach ( $rows as &$row ) {
			while ( count( $row ) < $cols ) {
				$row[] = '';
			}
		}
		unset( $row );

		$header = '| ' . implode( ' | ', $rows[0] ) . ' |';
		$sep    = '|' . implode( '|', array_fill( 0, $cols, ' --- ' ) ) . '|';
		$body   = [];
		foreach ( array_slice( $rows, 1 ) as $row ) {
			$body[] = '| ' . implode( ' | ', $row ) . ' |';
		}

		return $header . "\n" . $sep . ( $body ? "\n" . implode( "\n", $body ) : '' );
	}

	/**
	 * 画像の変換。
	 *
	 * @param DOMNode $img
	 * @return string
	 */
	private static function img_to_md( DOMNode $img ): string {
		$src = $img->getAttribute( 'src' );
		$alt = $img->getAttribute( 'alt' );
		return $src ? "![{$alt}]({$src})" : '';
	}

	/**
	 * 直下にブロック要素を含むか。
	 *
	 * @param DOMNode $node
	 * @return bool
	 */
	private static function has_block_child( DOMNode $node ): bool {
		$blocks = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'table', 'p', 'blockquote', 'pre', 'figure', 'div', 'hr' ];
		foreach ( $node->childNodes as $child ) {
			if ( XML_ELEMENT_NODE === $child->nodeType && in_array( strtolower( $child->nodeName ), $blocks, true ) ) {
				return true;
			}
		}
		return false;
	}
}
