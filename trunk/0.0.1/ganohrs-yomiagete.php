<?php
/*
Plugin Name: Yomiagete - Your Messages Instantly Audiolize: Giving Every Text!
Plugin URI: https://ganohr.net/blog/yomiagete/
Description: You can add a simple readout system "Yomiagete" to your WordPress articles!
Version: 0.0.1
Author: Ganohr
Author URI: https://ganohr.net/
License: GPL3
*/
?>
<?php
/*
	Copyright (C) 2023 Ganohr (email : ganohr@gmail.com)

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <https://www.gnu.org/licenses/>.

*/
?>
<?php
// 直接呼び出しは禁止
if (!defined('ABSPATH')) {
	exit();
}

// 関数がなければ定義する
if (!function_exists('ganohrs_yomiagete_func')) :
	require_once('class-ganohrs-yomiagete-options.php');

	// 読み上げに用いるdata-id

	define('GANOHRS_YOMIAGETE_DATA_ID', 'data-yomiage-id');

	// フィルタを登録
	add_filter('the_content', 'ganohrs_yomiagete_func', 2000000);

	// ショートコード本体
	function ganohrs_yomiagete_func($contents)
	{
		// 無駄な処理は行わない
		if (!is_singular() || !is_main_query()) {
			return $contents;
		}
		// オプションで指定された投稿タイプじゃない場合は処理を行わない
		if (!ganohrs_yomiagete_is_target_type()) {
			return $contents;
		}

		// タグの配列を取得する
		$tags_array = ganohrs_yomiagete_get_target_tags_array();
		// 読み上げ対象のタグに順序良くIDを振る
		$contents = ganohrs_yomiagete_append_id($contents, $tags_array);
		// 読み上げ対象のタグの並び順を振り直す
		$contents = ganohrs_yomiagete_reorder_id($contents);

		// コントローラーを出力する
		$contents = ganohrs_yomiagete_get_controller_html() . $contents;

		// オプションの内容を出力する
		$contents .= ganohrs_yomiagete_get_options_html();

		// 必要ならリソースを追加
		if (ganohrs_yomiagete_resource_enqueue_or_head() !== 'head') {
			ganohrs_yomiagete_load_resource();
		}

		// 処理結果を返却する
		return $contents;
	}

	/**
	 *
	 */
	function ganohrs_yomiagete_get_controller_html() {
		return <<<EOF
<div id="yomiagete-controller">
	<div class="yomiagete-controller-space">&nbsp;</div>
	<div id="yomiagete-controller-play">再生</div>
	<div id="yomiagete-controller-label">本文を音声で読み上げる</div>
	<div class="yomiagete-controller-space">&nbsp;</div>
</div>
EOF;
	}

	/**
	 * $contentsの内容に含まれる、$tags_arrayで指定されたHTMLタグにIDを振っていく。
	 *
	 * IDの順序は保証されないため、この処理を行った後に「ganohrs_yomiagete_reorder_id」を呼び出す必要あり
	 *
	 * @param $contents コンテンツ
	 * @param $tags_array 読み上げ対象のタグのリスト(<>などの記号は不要)
	 *
	 * @return string IDが付与された文字列
	 */
	function ganohrs_yomiagete_append_id($contents, $tags_array) {
		$id = 1;
		foreach ($tags_array as $tag) {
			$regex = '/(\<' . $tag . ')([^>]+>|>)(.+\<\/' . $tag . '>)/im';
			$pos = 0;
			while(true) {
				$result = preg_match($regex, $contents, $matches, PREG_OFFSET_CAPTURE, $pos);
				if (!$result) {
					break;
				}
				if (strpos($matches[0][0], GANOHRS_YOMIAGETE_DATA_ID)
// 					|| strpos($matches[0][0], "http")
				) {
					$pos = $matches[0][1] + strlen($matches[0][0]);
					continue;
				}
				$pattern = '/' . preg_quote($matches[0][0], '/') . '/';
				$replace = $matches[1][0] . ' ' . GANOHRS_YOMIAGETE_DATA_ID . '="#' . $id . '"' . $matches[2][0] . $matches[3][0];
				$replaced_contents = preg_replace($pattern, $replace, $contents, 1);
				if ($contents === $replaced_contents) {
					$pos = $matches[0][1] + strlen($matches[0][0]);
					continue;
				}
				$contents = $replaced_contents;
				$pos = $matches[0][1] + strlen($replace);
				$id++;
			}
		}
		return $contents;
	}

	/**
	 * ganohrs_yomiagete_append_idの処理後、IDの並び順を変更する
	 *
	 * @param $contents コンテンツ
	 *
	 * @return string IDが並び直された文字列
	 */
	function ganohrs_yomiagete_reorder_id($contents) {
		$id = 1;
		$pos = 0;
		while(true) {
			$regex = '/' . GANOHRS_YOMIAGETE_DATA_ID . '="#[^"]+"/';
			$result = preg_match($regex, $contents, $matches, PREG_OFFSET_CAPTURE, $pos);
			if (!$result) {
				break;
			}
			$pattern = '/' . preg_quote($matches[0][0], '/') . '/';
			$replace = GANOHRS_YOMIAGETE_DATA_ID . '="' . $id . '"';

			$replaced_contents = preg_replace($pattern, $replace, $contents, 1);
			if ($contents === $replaced_contents) {
				$pos = $matches[0][1] + strlen($matches[0][0]);
				continue;
			}
			$contents = $replaced_contents;
			$pos = $matches[0][1] + strlen($replace);
			$id++;
		}
		return $contents;
	}

	// リソース追加処理用の関数
	function ganohrs_yomiagete_load_resource()
	{
		// AMPページの場合何もしない
		if (ganohrs_is_amp()) {
			return;
		}

		// handleを定義
		$handle = "ganohrs-yomiagete";

		// CSSの格納先を記憶
		$css = plugins_url($handle . '.css', __FILE__);

		// CSSバージョン番号
		$css_ver = "0.0.1";

		// JSの格納先を記憶
		$js = plugins_url($handle . '.js', __FILE__);

		// JSバージョン番号
		$js_ver = "0.0.1";

		// enqueue/headに応じてCSSを追加する
		if (ganohrs_yomiagete_resource_enqueue_or_head() === 'enqueue') {
			// enqueue

			// CSSをエンキューする
			if (!wp_style_is($handle)) {
				wp_enqueue_style(
					$handle . "-css",
					$css,
					false,
					$css_ver,
					"all"
				);
			}
			// JSをエンキューする
			if (!wp_script_is($handle)) {
				wp_enqueue_script(
					$handle . "-js",
					$js,
					false,
					$js_ver,
					array(
						'strategy'  => 'defer',
					)
				);
			}
		} else {
			// head

			echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/prefixfree/1.0.7/prefixfree.min.js'></script>";
			echo "<script src='conic-gradient.js'></script>";

			// CSSをヘッダに直接出力する
			echo "<link rel='stylesheet'"
				. " id='{$handle}-css'"
				. " href='{$css}?ver={$css_ver}'"
				. " type='text/css'"
				. " media='all' />"
				. PHP_EOL;

			// JSをヘッダに直接出力する
			echo "<script"
				. " id='{$handle}-js'"
				. " href='{$js}?ver={$js_ver}'"
				. " type='text/javascript'"
				. " defer />"
				. PHP_EOL;
		}
	}

	/**
	 * オプションから対象とする投稿タイプの指定を取得し、現在のページが投稿タイプに合致するか否か判定する
	 */
	function ganohrs_yomiagete_is_target_type()
	{
		if (is_admin()) {
			return false;
		}
		$option = get_option('ganohrs_yomiagete_options');
		$target_types = 'post,page';
		if ($option) {
			$target_types = @$option['target_types'];
			if (!$target_types || strlen($target_types) == 0) {
				$target_types = 'post,page';
			}
		}
		$now_post_type = get_post_type();
		if (!is_string($now_post_type) || strlen($now_post_type) === 0) {
			return false;
		}
		foreach (explode(',', $target_types) as $post_type) {
			if ($now_post_type === $post_type) {
				return true;
			}
		}
		return false;
	}

	/**
	 * オプションから読み上げ対象とするタグの配列を取得する
	 */
	function ganohrs_yomiagete_get_target_tags_array()
	{
		$option = get_option('ganohrs_yomiagete_options');
		$target_tags = 'h1,h2,h3,h4,h5,h6,p';
		if ($option) {
			$target_tags = @$option['target_tags'];
			if (!$target_tags || strlen($target_tags) == 0) {
				$target_tags = 'h1,h2,h3,h4,h5,h6,p';
			}
		}
		return explode(',', $target_tags);
	}

	/**
	 * オプションから読み上げに関するオプションを取得し、その結果をHTMLで返す
	 */
	function ganohrs_yomiagete_get_options_html() {
		$option = get_option('ganohrs_yomiagete_options');
		$language = strlen(get_locale()) === 0 ? "ja" : get_locale();
		$speaker = 'Microsoft Ayumi,Microsoft Haruka,Microsoft Sayaka,Microsoft Ichiro,Microsoft,Google';
		$rate = 1.0;
		$pitch = 1.0;
		$volume = 1.0;
		if ($option) {
			$language = @$option['language'];
			if (!$language || strlen($language) == 0) {
				$language = strlen(get_locale()) === 0 ? "ja" : get_locale();
			}

			$speaker = @$option['speaker'];
			if (!$speaker || strlen($speaker) == 0) {
				$speaker = 'Microsoft Ayumi,Microsoft Haruka,Microsoft Sayaka,Microsoft Ichiro,Microsoft,Google,Kyoko,Fiona,Alex,Daniel,Fred';
			}

			$rate = @$option['rate'];
			if (!$rate || !is_numeric($rate) || $rate < 0 || $rate > 4.0) {
				$rate = 1.0;
			}

			$pitch = @$option['pitch'];
			if (!$pitch || !is_numeric($pitch) || $pitch < 0 || $pitch > 2.0) {
				$pitch = 1.0;
			}

			$volume = @$option['volume'];
			if (!$volume || !is_numeric($volume) || $volume < 0 || $volume > 1.0) {
				$volume = 1.0;
			}
		}
		return <<<EOF
<div id="ganohrs-yomiagete-options">
<hidden id="ganohrs-yomiagete-options-language" value="$language"></hidden>
<hidden id="ganohrs-yomiagete-options-speaker" value="$speaker"></hidden>
<hidden id="ganohrs-yomiagete-options-rate" value="$rate"></hidden>
<hidden id="ganohrs-yomiagete-options-pitch" value="$pitch"></hidden>
<hidden id="ganohrs-yomiagete-options-volume" value="$volume"></hidden>
</div>
EOF;
	}

	// 「'enqueue'」か「'head'」かを返す。
	// ※ 'enqueue'なら「wp_enqueue_style」でCSS追加
	// ※ 'head'なら「add_action」で「wp_head」にアクションをフックして追加
	// ※ 基本はenqueueを推奨
	function ganohrs_yomiagete_resource_enqueue_or_head()
	{
		$option = get_option('ganohrs_yomiagete_options');
		if ($option) {
			$enqueue_or_head = @$option['enqueue_or_head'];
			if (!$enqueue_or_head || strlen($enqueue_or_head) == 0) {
				$enqueue_or_head = 'enqueue';
			}
			return $enqueue_or_head;
		}
		return 'enqueue';
	}

	// リソース追加アクションを定義
	if (ganohrs_yomiagete_resource_enqueue_or_head() === 'head') {
		if (!has_action('wp_head', 'ganohrs_yomiagete_load_resource')) {
			add_action(
				'wp_head',
				'ganohrs_yomiagete_load_resource'
			);
		}
	} elseif (has_action('wp_head', 'ganohrs_yomiagete_load_resource')) {
		remove_action(
			'wp_head',
			'ganohrs_yomiagete_load_resource'
		);
	}

endif;

////AMPページか否か判定する
if (!function_exists('ganohrs_is_amp')) :

	function ganohrs_is_amp()
	{
		if (function_exists('is_amp') && is_amp()) {
			return true;
		} elseif (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
			return true;
		} elseif (function_exists('ampforwp_is_amp_endpoint') && ampforwp_is_amp_endpoint()) {
			return true;
		} elseif (@$_GET['amp'] === '1') {
			return true;
		} elseif (@$_GET['type'] === 'AMP') {
			return true;
		}
		$uri = ganohrs_get_uri_full();
		return ganohrs_is_amp_pattern($uri);
	}
	function ganohrs_get_uri_full()
	{
		return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http')
			. '://'
			. $_SERVER['SERVER_NAME']
			. $_SERVER['REQUEST_URI'];
	}
	function ganohrs_is_amp_pattern($uri)
	{
		if (ganohrs_tail_pattern_matched($uri, '/amp')) {
			return true;
		}
		if (ganohrs_tail_pattern_matched($uri, '/amp/')) {
			return true;
		}
		if (ganohrs_tail_pattern_matched($uri, '?amp=1')) {
			return true;
		}
		if (ganohrs_tail_pattern_matched($uri, 'type=AMP')) {
			return true;
		}
		return false;
	}
	function ganohrs_tail_pattern_matched($target, $pattern)
	{
		if (empty($target) && empty($pattern)) {
			return true;
		} elseif (empty($target)) {
			return false;
		} elseif (empty($pattern)) {
			return false;
		}
		$s_end = strlen($target);
		$s_len = strlen($pattern);
		$offset = $s_end - $s_len;
		if ($offset < 0) {
			return false;
		}
		$pos = strpos($target, $pattern, $offset);
		return $pos === $offset;
	}
	function ganohrs_remove_amp_uri_part($uri, $pattern)
	{
		$s_end = strlen($uri);
		$s_len = strlen($pattern);
		$offset = $s_end - $s_len;
		if ($offset < 0) {
			return $uri;
		}
		$pos = strpos($uri, $pattern, $offset);
		if ($pos === $offset) {
			return substr($uri, 0, $pos);
		}
		return $uri;
	}
endif;
