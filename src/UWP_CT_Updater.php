<?php
/**
 * UpsellWP: Custom taxonomy
 *
 * @package   upsellwp-custom-taxonomy
 * @author    Team UpsellWP <team@upsellwp.com>
 * @license   GPL-3.0-or-later
 * @link      https://upsellwp.com
 */

defined('ABSPATH') || exit;

if (class_exists('UWP_CT_Updater')) {
    return;
}

class UWP_CT_Updater
{
    /**
     * Properties.
     *
     * @var mixed
     */
    private $file, $plugin, $basename, $username, $repository, $github_response;

    /**
     * Constructor.
     *
     * @param string $file
     * @param string $repo
     */
    public function __construct($file, $repo)
    {
        $this->file = $file;
        $this->username = 'upsellwp';
        $this->repository = $repo;
        $this->plugin = get_plugin_data($file);
        $this->basename = plugin_basename($file);

        if (!empty($this->repository)) {
            $this->initialize();
        }
        return $this;
    }

    /**
     * Initialize updater.
     *
     * @return void
     */
    private function initialize()
    {
        add_filter('plugins_api', [$this, 'pluginPopup'], 10, 3);
        add_filter('upgrader_post_install', [$this, 'afterInstall'], 10, 3);
        add_filter('pre_set_site_transient_update_plugins', [$this, 'modifyTransient'], 10, 1);
    }

    /**
     * Fetch latest release.
     */
    private function fetchLatestRelease()
    {
        if (is_null($this->github_response)) {
            $request_uri = sprintf('https://api.github.com/repos/%s/%s/releases/latest', $this->username, $this->repository);
            $response = wp_remote_get($request_uri);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
                $response = json_decode(wp_remote_retrieve_body($response), true);
            }
            $this->github_response = is_array($response) && isset($response['tag_name']) ? $response : [];
        }
    }

    /**
     * Modify transient.
     *
     * @param $transient
     * @return mixed
     */
    public function modifyTransient($transient)
    {
        if (is_object($transient) && property_exists($transient, 'checked')) {
            if ($transient->checked && isset($transient->checked[$this->basename])) {
                $this->fetchLatestRelease();
                if (!empty($this->github_response)) {
                    $github_version = ltrim($this->github_response['tag_name'], 'v');
                    $local_version = $transient->checked[$this->basename];
                    $out_of_date = version_compare($github_version, $local_version, 'gt');
                    if ($out_of_date) {
                        $download_url = $this->github_response['zipball_url'];
                        $slug = current(explode('/', $this->basename));
                        $plugin = [
                            'url' => $this->plugin['PluginURI'],
                            'slug' => $slug,
                            'package' => $download_url,
                            'new_version' => $github_version,
                        ];
                        $transient->response[$this->basename] = (object)$plugin;
                    }
                }
            }
        }
        return $transient;
    }

    /**
     * Set plugin data.
     */
    public function pluginPopup($result, $action, $args)
    {
        if (!empty($args->slug) && $args->slug == current(explode('/', $this->basename))) {
            $this->fetchLatestRelease();
            if (!empty($this->github_response)) {
                $plugin = [
                    'name' => $this->plugin['Name'],
                    'slug' => $this->basename,
                    'requires' => '5.3.0',
                    //'tested' => '6.5',
                    //'rating' => '',
                    //'ratings' => '',
                    //'downloaded' => '',
                    //'added' => '2024-06-01',
                    'version' => ltrim($this->github_response['tag_name'], 'v'),
                    'author' => $this->plugin['AuthorName'],
                    'author_profile' => $this->plugin['AuthorURI'],
                    'last_updated' => $this->github_response['published_at'],
                    'homepage' => $this->plugin['PluginURI'],
                    'short_description' => $this->plugin['Description'],
                    'sections' => [
                        'Description' => $this->plugin['Description'],
                        'Updates' => $this->github_response['body'],
                    ],
                    'download_link' => $this->github_response['zipball_url'],
                ];
                return (object)$plugin;
            }
        }
        return $result;
    }

    /**
     * Extract and activate plugin.
     */
    public function afterInstall($response, $hook_extra, $result)
    {
        if (!empty($result['destination']) && strpos($result['destination'], dirname($this->basename)) !== false) {
            global $wp_filesystem;
            if ($wp_filesystem) {
                $install_directory = plugin_dir_path($this->file);
                $wp_filesystem->move($result['destination'], $install_directory);
                $result['destination'] = $install_directory;
                if (!empty($result['destination_name'])) {
                    $result['destination_name'] = dirname($this->basename);
                }
            }
        }
        return $result;
    }
}