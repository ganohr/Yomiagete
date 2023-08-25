<?php
/**
 * Yomiagete - Your Messages Instantly Audiolize: Giving Every Text!
 *
 * PHP Version >= 5.0
 *
 * @since	   0.0.1
 * @package    Ganohrs Yomiagete
 * @author	   Ganohr<ganohr@gmail.com>
 */

// 直接呼び出しは禁止
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'ganohrs_yomiagete_Options' ) ) :


	/**
	 * Ganohrs Yomiagete Options
	 *
	 * @author	   Ganohr<ganohr@gmail.com>
	 * @return	   void
	 */
	class ganohrs_yomiagete_Options {

		/**
		 * 設定ページ用の識別ID
		 */
		const PAGE_ID = 'ganohrs-yomiagete-options';

		/**
		 * オプション記憶用
		 */
		private $options = array();

		/**
		 * コンストラクタ
		 *
		 * @return	   void
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
			add_action( 'admin_init', array( $this, 'page_init' ) );
			add_filter( 'plugin_action_links_ganohrs-yomiagete/ganohrs-yomiagete.php', array( $this, 'add_plugin_action_links' ) );
		}

		/**
		 * プラグイン一覧に設定リンクを付加する
		 *
		 * @param array $links プラグイン一覧のリンクリスト
		 * @return array 更新後のプラグイン一覧のリンクリスト
		 */
		function add_plugin_action_links( $links ) {
			$url = admin_url( 'options-general.php?page=' . self::PAGE_ID );
			array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . __( 'Settings' ) . '</a>' );
			return $links;
		}

		/**
		 * 設定ページへプラグインを追加する
		 *
		 * @return	   void
		 */
		public function add_plugin_page() {
			$load_hook = add_options_page(
				"Ganohrs Yomiagete",
				"Ganohrs Yomiagete",
				'manage_options',
				self::PAGE_ID,
				array( $this, 'admin_manage_page' )
			);
		}

		/**
		 * オプションページ
		 *
		 * @return	   void
		 */
		public function admin_manage_page() {
			$this->options = get_option( 'ganohrs_yomiagete_options' );
			$target_tags  = @$this->options['target_tags'];
			$target_types = @$this->options['target_types'];
			?>
			<div class="wrap">
				<h1>Yomiagete - Your Messages Instantly Audiolize: Giving Every Text!</h1>

				<form method="post" action="options.php">
					<?php
						settings_fields( 'ganohrs_yomiagete_options_group' );
						do_settings_sections( 'ganohrs_yomiagete_options' );

						submit_button();
					?>
				</form>
			</div>
			<style>
			#wpbody-content {
				font-size: 110%;
			}
			h2 {
				background: white;
				line-height: 2;
				font-weight: bold;
				font-size: 1.4rem;
				border-left: 0.3rem solid black;
				padding: 0.3rem;
				margin: 0.3rem 0;
			}
			code {
				background: none;
			}
			textarea {
				width: 100%;
				height: 30vh;
			}
			dl {
				padding-left: 2em;
			}
			dt {
				font-size: 120%;
			}
			dt:first-letter {
				font-size: 140%;
				border-bottom: 2px solid black;
			}
			strong {
				font-weight: 600;
				border-bottom: 1px dotted black;
			}
			</style>
			<?php
		}

		/**
		 * ページ初期化
		 *
		 * @return	   void
		 */
		public function page_init() {
			register_setting(
				'ganohrs_yomiagete_options_group',
				'ganohrs_yomiagete_options',
				array( $this, 'sanitize_and_check' )
			);

			add_settings_section(
				'ganohrs_yomiagete_setting_section',
				'Settings',
				null,
				'ganohrs_yomiagete_options'
			);
			add_settings_field(
				'enqueue_or_head',
				'Enqueue Type',
				array( $this, 'enqueue_type_callback' ),
				'ganohrs_yomiagete_options',
				'ganohrs_yomiagete_setting_section'
			);
			add_settings_field(
				'target_tags',
				'Target Tags',
				array( $this, 'target_tags_callback' ),
				'ganohrs_yomiagete_options',
				'ganohrs_yomiagete_setting_section'
			);
			add_settings_field(
				'target_types',
				'Target Types',
				array( $this, 'target_types_callback' ),
				'ganohrs_yomiagete_options',
				'ganohrs_yomiagete_setting_section'
			);
			add_settings_field(
				'language',
				'Target Language',
				array( $this, 'language_callback' ),
				'ganohrs_yomiagete_options',
				'ganohrs_yomiagete_setting_section'
			);
			add_settings_field(
				'speaker',
				'Target Speaker',
				array( $this, 'speaker_callback' ),
				'ganohrs_yomiagete_options',
				'ganohrs_yomiagete_setting_section'
			);
			add_settings_field(
				'rate',
				'Speak Rate',
				array( $this, 'rate_callback' ),
				'ganohrs_yomiagete_options',
				'ganohrs_yomiagete_setting_section'
			);
			add_settings_field(
				'pitch',
				'Speak Pitch',
				array( $this, 'pitch_callback' ),
				'ganohrs_yomiagete_options',
				'ganohrs_yomiagete_setting_section'
			);
			add_settings_field(
				'volume',
				'Speak Volume',
				array( $this, 'volume_callback' ),
				'ganohrs_yomiagete_options',
				'ganohrs_yomiagete_setting_section'
			);
		}

		/**
		 * 入力値をサニタイズし、適切な値に設定する
		 *
		 * @param array $input POSTされた入力値の配列
		 * @return サニタイズされた入力値の配列
		 */
		public function sanitize_and_check( $input ) {
			$new_input = array();

			$new_input['enqueue_or_head'] = isset( $input['enqueue_or_head'] ) ? @$input['enqueue_or_head'] : 'enqueue';
			$new_input['target_tags']     = isset( $input['target_tags'] )     ? @$input['target_tags']     : 'h1,h2,h3,h4,h5,h6,p,figcaption,li,pre';
			$new_input['target_types']    = isset( $input['target_types'] )    ? @$input['target_types']    : 'post,page';
			$new_input['language']        = isset( $input['language'] )        ? @$input['language']        : (strlen(get_locale()) === 0 ? "ja" : get_locale());
			$new_input['speaker']         = isset( $input['speaker'] )         ? @$input['speaker']         : 'Microsoft Ayumi,Microsoft Haruka,Microsoft Sayaka,Microsoft Ichiro,Microsoft,Google';
			$new_input['rate']            = isset( $input['rate'] )            ? @$input['rate']            : '1.0';
			$new_input['pitch']           = isset( $input['pitch'] )           ? @$input['pitch']           : '1.0';
			$new_input['volume']          = isset( $input['volume'] )          ? @$input['volume']          : '1.0';
			return $new_input;
		}

		/**
		 * 読み上げるタグを指定するコールバック
		 *
		 * @return	   void
		 */
		public function target_tags_callback() {
			$target_tags = is_array( $this->options ) ? @$this->options['target_tags'] : '';
			if ( ! is_string( $target_tags ) || strlen( $target_tags ) === 0 ) {
				$target_tags = 'h1,h2,h3,h4,h5,h6,p,figcaption,li,pre';
			}
			?>
				<label for="target_tags" >Target Tags
				<input id="target_tags" type="input" name="ganohrs_yomiagete_options[target_tags]" value="<?php echo htmlspecialchars($target_tags); ?>" />
				</label>
			<?php
		}

		/**
		 * 読み上げる投稿タイプを指定するコールバック
		 *
		 * @return	   void
		 */
		public function target_types_callback() {
			$target_types = is_array( $this->options ) ? @$this->options['target_types'] : '';
			if ( ! is_string( $target_types ) || strlen( $target_types ) === 0 ) {
				$target_types = 'post,page';
			}
			?>
				<label for="target_types" >Target Post Types
				<input id="target_types" type="input" name="ganohrs_yomiagete_options[target_types]" value="<?php echo htmlspecialchars($target_types); ?>" />
				</label>
			<?php
		}

		/**
		 * 読み上げる言語（記事の言語）を指定するコールバック
		 *
		 * @return	   void
		 */
		public function language_callback() {
			$language = is_array( $this->options ) ? @$this->options['language'] : '';
			if ( ! is_string( $language ) || strlen( $language ) === 0 ) {
				$language = strlen(get_locale()) === 0 ? "ja" : get_locale();
			}
			?>
				<label for="language" >Target Language
				<input id="language" type="input" name="ganohrs_yomiagete_options[language]" value="<?php echo htmlspecialchars($language); ?>" />
				</label>
				<p>* Specify the IANA-defined two-letter language code, or two-letter language code with a <em>"-"</em> and the country code added in two-letter uppercase.</p>
				<p>* Examples: ja, ja-JP, en, en-US, zh, zh-CN; partial match and case sensitive.</p>
			<?php
		}

		/**
		 * 読み上げる話者を指定するコールバック
		 *
		 * @return	   void
		 */
		public function speaker_callback() {
			$speaker = is_array( $this->options ) ? @$this->options['speaker'] : '';
			if ( ! is_string( $speaker ) || strlen( $speaker ) === 0 ) {
				$speaker = 'Microsoft Ayumi,Microsoft Haruka,Microsoft Sayaka,Microsoft Ichiro,Microsoft,Google';
			}
			?>
				<label for="speaker" >Target Speaker
				<input id="speaker" type="input" name="ganohrs_yomiagete_options[speaker]" value="<?php echo htmlspecialchars($speaker); ?>" />
				</label>
			<?php
		}

		/**
		 * 読み上げる速度を指定するコールバック
		 *
		 * @return	   void
		 */
		public function rate_callback() {
			$rate = is_array( $this->options ) ? @$this->options['rate'] : '';
			if (! is_numeric($rate) || $rate < 0 || $rate > 4) {
				$rate = 1.0;
			}
			$rate = round($rate * 10) / 10;
			?>
				<label for="rate" >Speak rate
				<input id="rate" type="range" name="ganohrs_yomiagete_options[rate]" min="0" max="4.0" step="0.1" value="<?php echo $rate; ?>" />
				</label>
			<?php
		}

		/**
		 * 読み上げるピッチを指定するコールバック
		 *
		 * @return	   void
		 */
		public function pitch_callback() {
			$pitch = is_array( $this->options ) ? @$this->options['pitch'] : '';
			if (! is_numeric($pitch) || $pitch < 0 || $pitch > 2) {
				$pitch = 1.0;
			}
			$pitch = round($pitch * 10) / 10;
			?>
				<label for="pitch" >Speak pitch
				<input id="pitch" type="range" name="ganohrs_yomiagete_options[pitch]" min="0" max="2.0" step="0.1" value="<?php echo $pitch; ?>" />
				</label>
			<?php
		}

		/**
		 * 読み上げるボリュームを指定するコールバック
		 *
		 * @return	   void
		 */
		public function volume_callback() {
			$volume = is_array( $this->options ) ? @$this->options['volume'] : '';
			if (! is_numeric($volume) || $volume < 0 || $volume > 2) {
				$volume = 1.0;
			}
			$volume = round($volume * 10) / 10;
			?>
				<label for="volume" >Speak volume
				<input id="volume" type="range" name="ganohrs_yomiagete_options[volume]" min="0" max="1.0" step="0.1" value="<?php echo $volume; ?>" />
				</label>
			<?php
		}

		/**
		 * Enqueue Type変更用コールバック
		 *
		 * @return	   void
		 */
		public function enqueue_type_callback() {
			$enqueue_type = is_array( $this->options ) ? @$this->options['enqueue_or_head'] : '';
			if ( ! is_string( $enqueue_type ) || strlen( $enqueue_type ) === 0 ) {
				$enqueue_type = 'enqueue';
			}
			?>
				<label for="enqueue_type_enqueue"><input id="enqueue_type_enqueue" type="radio" name="ganohrs_yomiagete_options[enqueue_or_head]" <?php echo ( $enqueue_type === 'enqueue' ? 'checked' : '' ); ?> value="enqueue" />Enqueue</label>
				<label for="enqueue_type_head"	 ><input id="enqueue_type_head"    type="radio" name="ganohrs_yomiagete_options[enqueue_or_head]" <?php echo ( $enqueue_type === 'head'    ? 'checked' : '' ); ?> value="head"    />Head   </label>
			<?php
		}
	}

	if ( is_admin() ) {
		new ganohrs_yomiagete_Options();
	}

endif;
