<?php
/**
 * Plugin Name: PeepSo Core: Moods
 * Plugin URI: https://peepso.com
 * Description: Express your mood when posting a status
 * Author: PeepSo
 * Author URI: https://peepso.com
 * Version: 1.8.2
 * Copyright: (c) 2015 PeepSo LLP. All Rights Reserved.
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: peepso-moods
 * Domain Path: /language
 *
 * We are Open Source. You can redistribute and/or modify this software under the terms of the GNU General Public License (version 2 or later)
 * as published by the Free Software Foundation. See the GNU General Public License or the LICENSE file for more details.
 * This software is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
 */

class PeepSoMoods {

	private static $_instance = NULL;
	public $moods = array();

	const PLUGIN_VERSION = '1.8.2';
	const PLUGIN_RELEASE = ''; //ALPHA1, BETA1, RC1, '' for STABLE
	const META_POST_MOOD = '_peepso_post_mood';
	const PLUGIN_NAME = 'MoodSo';
	const PLUGIN_SLUG = 'moodso';
	const PEEPSOCOM_LICENSES = 'http://tiny.cc/peepso-licenses';

	const PLUGIN_DEV = FALSE;

	private $class_prefix = 'ps-emo-';

	/**
	 * Initialize all variables, filters and actions
	 */
	private function __construct()
	{
		add_action('peepso_init', array(&$this, 'init'));

		if (is_admin())
		{
			add_action('admin_init', array(&$this, 'peepso_check'));
		}

		add_action('plugins_loaded', array(&$this, 'load_textdomain'));

		add_filter('peepso_all_plugins', array($this, 'filter_all_plugins'));

		// You can't call register_activation_hook() inside a function hooked to the 'plugins_loaded' or 'init' hooks
		register_activation_hook(__FILE__, array(&$this, 'activate'));
	}

	/*
	 * Return singleton instance of plugin
	 */

	public static function get_instance()
	{
		if (NULL === self::$_instance)
		{
			self::$_instance = new self();
		}

		return (self::$_instance);
	}

	/**
	 * Loads the translation file for the PeepSo plugin
	 */
	public function load_textdomain()
	{
		$path = str_ireplace(WP_PLUGIN_DIR, '', dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR;
		load_plugin_textdomain('peepso-moods', FALSE, $path);
	}

	/*
	 * Initialize the PeepSoMoods plugin
	 */

	public function init()
	{
		if (is_admin()) {
			add_action('admin_init', array(&$this, 'peepso_check'));
		} else {
			add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
			add_action('wp_insert_post', array(&$this, 'save_mood'), 100);
			add_action('peepso_activity_after_save_post', array(&$this, 'save_mood'), 100);
			add_filter('peepso_postbox_interactions', array(&$this, 'filter_postbox_interactions'), 20);
			add_filter('peepso_activity_allow_empty_content', array(&$this, 'filter_activity_allow_empty_content'), 10, 1);
			add_filter('peepso_post_extras', array(&$this, 'filter_post_extras'), 10, 1);
			add_filter('peepso_activity_post_edit', array(&$this, 'filter_post_edit'), 10, 1);


		}

		add_filter('peepso_moods_mood_value', array(&$this, 'filter_mood_value'));

		// initialize moods list
		$this->moods = array(
			1 => __('joyful', 'peepso-moods'),
			2 => __('meh', 'peepso-moods'),
			3 => __('love', 'peepso-moods'),
			4 => __('flattered', 'peepso-moods'),
			5 => __('crazy', 'peepso-moods'),
			6 => __('cool', 'peepso-moods'),
			7 => __('tired', 'peepso-moods'),
			8 => __('confused', 'peepso-moods'),
			9 => __('speechless', 'peepso-moods'),
			10 => __('confident', 'peepso-moods'),
			11 => __('relaxed', 'peepso-moods'),
			12 => __('strong', 'peepso-moods'),
			13 => __('happy', 'peepso-moods'),
			14 => __('angry', 'peepso-moods'),
			15 => __('scared', 'peepso-moods'),
			16 => __('sick', 'peepso-moods'),
			17 => __('sad', 'peepso-moods'),
			18 => __('blessed', 'peepso-moods')
		);

		// Compare last version stored in transient with current version
		if( $this::PLUGIN_VERSION.$this::PLUGIN_RELEASE != get_transient($trans = 'peepso_'.$this::PLUGIN_SLUG.'_version')) {
			set_transient($trans, $this::PLUGIN_VERSION.$this::PLUGIN_RELEASE);
			$this->activate();
		}
	}

	/**
	 * Plugin activation
	 * Check PeepSo
	 * @return bool
	 */
	public function activate()
	{
		if (!$this->peepso_check())
		{
			return (FALSE);
		}

		return (TRUE);
	}

	/**
	 * Check if PeepSo class is present (ie the PeepSo plugin is installed and activated)
	 * If there is no PeepSo, immediately disable the plugin and display a warning
	 * Run license and new version checks against PeepSo.com
	 * @return bool
	 */
	public function peepso_check()
	{
		if (!class_exists('PeepSo')) {
			add_action('admin_notices', array(&$this, 'peepso_disabled_notice'));
			unset($_GET['activate']);
			deactivate_plugins(plugin_basename(__FILE__));
			return (FALSE);
		}
	}

	/**
	 * Display a message about PeepSo not present
	 */
	public function peepso_disabled_notice()
	{
		?>
		<div class="error fade">
			<strong>
				<?php echo sprintf(__('The %s plugin requires the PeepSo plugin to be installed and activated.', 'peepso-moods'), self::PLUGIN_NAME);?>
				<a href="<?php echo self::PEEPSOCOM_LICENSES;?>" target="_blank">
					<?php _e('Get it now!', 'peepso-moods');?>
				</a>
			</strong>
		</div>
		<?php
	}

	/**
	 * Hooks into PeepSo Core for compatibility checks
	 * @param $plugins
	 * @return mixed
	 */
	public function filter_all_plugins($plugins)
	{
		$plugins[plugin_basename(__FILE__)] = get_class($this);
		return $plugins;
	}

	/**
	 * Load required styles and scripts
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_style('peepso-moods', plugin_dir_url(__FILE__) . 'assets/css/peepsomoods.css', array('peepso'), self::PLUGIN_VERSION, 'all');

		if (self::PLUGIN_DEV) {
			wp_enqueue_script('peepso-moods', plugin_dir_url(__FILE__) . 'assets/js/peepsomoods.js', array('peepso', 'peepso-postbox'), self::PLUGIN_VERSION, TRUE);
		} else {
			wp_enqueue_script('peepso-moods', plugin_dir_url(__FILE__) . 'assets/js/bundle.min.js', array('peepso', 'peepso-postbox'), self::PLUGIN_VERSION, TRUE);
		}
	}

	/**
	 * This function inserts mood selection box on the post box
	 * @param array $out_html is the formated html code that get inserted in the postbox
	 */
	public function filter_postbox_interactions($out_html = array())
	{
		$mood_list = '';
		foreach ($this->moods as $id => $mood)
		{
			$mood_list .= "
				<li class='mood-list'>
					<a id='postbox-mood-{$id}' href='javascript:' data-option-value='{$id}' data-option-display-value='{$mood}'>
						<i class='ps-emoticon {$this->class_prefix}{$id}'></i><span>" . $mood . "</span>
					</a>
				</li>";
		}

		$mood_remove = __('Remove Mood', 'peepso-moods');
		$mood_ux = '<div style="display:none">
				<input type="hidden" id="postbox-mood-input" name="postbox-mood-input" value="0" />
				<span id="mood-text-string">' . __(' feeling ', 'peepso-moods') . '</span>
				</div>';

		$mood_data = array(
			'label' => __('Mood', 'peepso-moods'),
			'id' => 'mood-tab',
			'class' => 'ps-postbox__menu-item',
			'icon' => 'happy',
			'click' => 'return;',
			'title' => __('Mood settings for your post', 'peepso-moods'),
			'extra' => "<ul id='postbox-mood' class='ps-dropdown-menu ps-postbox-moods' style='display:none'>
							{$mood_list}
							<li class='mood-list' style='width:100%; display:none'><button id='postbox-mood-remove' class='ps-btn ps-btn-danger ps-remove-location' style='width:100%'><i class='ps-icon-remove'></i>{$mood_remove}</button></li>
						</ul>{$mood_ux}"
		);

		$out_html['Mood'] = $mood_data;
		return ($out_html);
	}

	/**
	 * This function saves the mood data for the post
	 * @param $post_id is the ID assign to the posted content
	 */
	public function save_mood($post_id)
	{
		$input = new PeepSoInput();
		$mood = $input->val('mood');

		if (apply_filters('peepso_moods_apply_to_post_types', array(PeepSoActivityStream::CPT_POST)))
		{
			if (empty($mood)) {
				delete_post_meta($post_id, self::META_POST_MOOD);
			} else {
				update_post_meta($post_id, self::META_POST_MOOD, $mood);
			}
		}
	}

	/**
	 * TODO: docblock
	 */
	public function filter_post_extras( $extras = array() )
	{
		global $post;
		$post_mood_id = get_post_meta($post->ID, self::META_POST_MOOD, TRUE);
		$post_mood = apply_filters('peepso_moods_mood_value', $post_mood_id);

		if (!empty($post_mood))
		{
			ob_start();?>
			<span>
				<i class="ps-emoticon <?php echo $this->class_prefix . $post_mood_id;?>"></i>
				<span><?php echo __(' feeling ', 'peepso-moods') . ucwords($post_mood);?></span>
			</span>
			<?php
			$extras[] = ob_get_clean();
		}

		return $extras;
	}

	/**
	 * Allows empty post content if a mood is set
	 * @param boolean $allowed Current state of the allow posting check
	 * @return boolean Rturns TRUE when mood information is present to indicate that a post with not content and a mood is publishable
	 */
	public function filter_activity_allow_empty_content($allowed)
	{
		$input = new PeepSoInput();
		$mood = $input->val('mood');
		if (!empty($mood))
		{
			$allowed = TRUE;
		}

		return ($allowed);
	}

	public function filter_mood_value($mood)
	{
		if (!$mood)
		{
			return;
		}

		if (array_key_exists($mood, $this->moods))
		{
			return $this->moods[$mood];
		}

		return $mood . "*";
	}

	public function filter_post_edit( $data = array() )
	{
		$input = new PeepSoInput();
		$post_id = $input->int('postid');

		$mood = get_post_meta($post_id, self::META_POST_MOOD, TRUE);
		if (!empty($mood)) {
			$data['mood'] = $mood;
		}

		return $data;
	}

}

PeepSoMoods::get_instance();

// EOF
