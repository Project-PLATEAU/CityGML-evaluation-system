<?php
/**
 * WordPress の基本設定
 *
 * このファイルは、インストール時に wp-config.php 作成ウィザードが利用します。
 * ウィザードを介さずにこのファイルを "wp-config.php" という名前でコピーして
 * 直接編集して値を入力してもかまいません。
 *
 * このファイルは、以下の設定を含みます。
 *
 * * MySQL 設定
 * * 秘密鍵
 * * データベーステーブル接頭辞
 * * ABSPATH
 *
 * @link https://ja.wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// 注意:
// Windows の "メモ帳" でこのファイルを編集しないでください !
// 問題なく使えるテキストエディタ
// (http://wpdocs.osdn.jp/%E7%94%A8%E8%AA%9E%E9%9B%86#.E3.83.86.E3.82.AD.E3.82.B9.E3.83.88.E3.82.A8.E3.83.87.E3.82.A3.E3.82.BF 参照)
// を使用し、必ず UTF-8 の BOM なし (UTF-8N) で保存してください。

// ** MySQL 設定 - この情報はホスティング先から入手してください。 ** //
/** WordPress のためのデータベース名 */
define( 'DB_NAME', 'DBNAME' );

/** MySQL データベースのユーザー名 */
define( 'DB_USER', 'DBUSER' );

/** MySQL データベースのパスワード */
define( 'DB_PASSWORD', 'DBPASSWORD' );

/** MySQL のホスト名 */
define( 'DB_HOST', 'localhost' );

/** データベースのテーブルを作成する際のデータベースの文字セット */
define( 'DB_CHARSET', 'utf8mb4' );

/** データベースの照合順序 (ほとんどの場合変更する必要はありません) */
define( 'DB_COLLATE', '' );

/**#@+
 * 認証用ユニークキー
 *
 * それぞれを異なるユニーク (一意) な文字列に変更してください。
 * {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org の秘密鍵サービス} で自動生成することもできます。
 * 後でいつでも変更して、既存のすべての cookie を無効にできます。これにより、すべてのユーザーを強制的に再ログインさせることになります。
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'm0n(P4-wh:HRZUCcjv@rM<gP6BG_(oUkqa=eVA@e8eOhOliv0&BTho>fs694*kFG' );
define( 'SECURE_AUTH_KEY',  'NdBGv0(@xbv*oNo&->A55l/T%g~p2,kmzjbH*rBQl$vb.P*BQCvkO}nulTqpG+V>' );
define( 'LOGGED_IN_KEY',    'ncu*[9iS)E8L|L}3B{q3TKu)X,.eM.wJ8[LA2Hfe&Yr:yNqe3>$aUI5t^ixu*g0Y' );
define( 'NONCE_KEY',        'lg;9W>b=wh0Cxb&p;5[@S[8jW w/z*;ml8Zh<^>Z,::yp,q)Yfll9P^CmJ;.r(C:' );
define( 'AUTH_SALT',        'mb9?[XHV4LfF%g%F^!m0SN)J!&M56T7Mi2SCfT^$#XfCZ`3~~8&(*0kx{&Pp{V0~' );
define( 'SECURE_AUTH_SALT', 'GLDc6gyFTmcxU#kv[qGJ%U4a g|EPX#_dSG._Gx;dmH{$N+M!}eBP%+1&sm$~v[-' );
define( 'LOGGED_IN_SALT',   '3<u+ZLgtKK7aePD`&a-bfW)m&zV$m:H-i(jB%$B7EL ]+c9MIa^:twy8y=u@oS/A' );
define( 'NONCE_SALT',       '>:5,WHUKv$FJr8hH%zA3A(502Ksa*4iY#A4NRf8E6vSE8|6fVamlGghr!OY$w|le' );

/**#@-*/

/**
 * WordPress データベーステーブルの接頭辞
 *
 * それぞれにユニーク (一意) な接頭辞を与えることで一つのデータベースに複数の WordPress を
 * インストールすることができます。半角英数字と下線のみを使用してください。
 */
$table_prefix = 'wp_';

define('ALLOW_UNFILTERED_UPLOADS', true);

/**
 * 開発者へ: WordPress デバッグモード
 *
 * この値を true にすると、開発中に注意 (notice) を表示します。
 * テーマおよびプラグインの開発者には、その開発環境においてこの WP_DEBUG を使用することを強く推奨します。
 *
 * その他のデバッグに利用できる定数についてはドキュメンテーションをご覧ください。
 *
 * @link https://ja.wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* 編集が必要なのはここまでです ! WordPress でのパブリッシングをお楽しみください。 */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

//自動アップデート無効化
define( 'AUTOMATIC_UPDATER_DISABLED', true );
