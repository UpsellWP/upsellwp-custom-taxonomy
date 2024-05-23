<?php
defined('ABSPATH') || exit;

if (class_exists('UWP_GH_Updater')) {
    return;
}

class UWP_GH_Updater
{
    /**
     * Properties.
     *
     * @var mixed
     */
    private $file, $plugin, $basename, $username, $repository, $authorize_token, $github_response;

    /**
     * Constructor.
     *
     * @param string $file
     * @param string $repo
     * @param string|null $access_token
     */
    public function __construct($file, $repo, $access_token = null)
    {
        $this->file = $file;
        $this->username = 'upsellwp';
        $this->repository = $repo;
        $this->authorize_token = $access_token;

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
        add_action('admin_init', array($this, 'setPluginProperties'));

        add_filter('pre_set_site_transient_update_plugins', array($this, 'modifyTransient'), 10, 1);
        add_filter('plugins_api', array($this, 'pluginPopup'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'afterInstall'), 10, 3);

        if (!empty($this->authorize_token)) {
            add_filter('upgrader_pre_download', function ($status) {
                add_filter('http_request_args', [$this, 'addTokenInHeader'], 100, 2);
                return $status; // upgrader_pre_download filter default return value
            });
        }
    }

    /**
     * Fetch latest release.
     */
    private function fetchLatestRelease()
    {
        if (is_null($this->github_response)) { // Do we have a response?
            $args = array();
            $request_uri = sprintf('https://api.github.com/repos/%s/%s/releases/latest', $this->username, $this->repository); // Build URI
            if ($this->authorize_token) { // Is there an access token?
                $args['headers']['Authorization'] = 'token {$this->authorize_token}'; // Set the headers
            }
            $response = wp_remote_get($request_uri, $args);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
                $response = json_decode(wp_remote_retrieve_body($response), true);
            }
            $this->github_response = is_array($response) && isset($response['tag_name']) ? $response : array();
        }
    }

    /**
     * Set plugin props.
     *
     * @return void
     */
    public function setPluginProperties()
    {
        $this->plugin = get_plugin_data($this->file);
        $this->basename = plugin_basename($this->file);
    }

    /**
     * Modify transient.
     *
     * @param $transient
     * @return mixed
     */
    public function modifyTransient($transient)
    {
        if (is_object($transient) && property_exists($transient, 'checked')) { // Check if transient has a checked property
            if ($transient->checked && isset($transient->checked[$this->basename])) { // Did WordPress check for updates?
                $this->fetchLatestRelease(); // Get the repo info
                if (!empty($this->github_response)) {
                    $github_version = ltrim($this->github_response['tag_name'], 'v');
                    $local_version = $transient->checked[$this->basename];
                    $out_of_date = version_compare($github_version, $local_version, 'gt'); // Check if we're out of date
                    if ($out_of_date) {
                        $download_url = $this->github_response['zipball_url']; // Get the ZIP
                        $slug = current(explode('/', $this->basename)); // Create valid slug
                        $plugin = array( // setup our plugin info
                            'url' => $this->plugin['PluginURI'],
                            'slug' => $slug,
                            'package' => $download_url,
                            'new_version' => $github_version,
                        );
                        $transient->response[$this->basename] = (object)$plugin; // Return it in response
                    }
                }
            }
        }
        return $transient; // Return filtered transient
    }

    /**
     * Set plugin data.
     */
    public function pluginPopup($result, $action, $args)
    {
        if (!empty($args->slug)) { // If there is a slug
            if ($args->slug == current(explode('/', $this->basename))) { // And it's our slug
                $this->fetchLatestRelease(); // Get our repo info
                if (!empty($this->github_response)) {
                    // Set it to an array
                    $plugin = array(
                        'name' => $this->plugin['Name'],
                        'slug' => $this->basename,
                        'requires' => '5.3.0',
                        // 'tested' => '',
                        // 'rating' => '',
                        // 'ratings' => '',
                        // 'downloaded' => '',
                        'added' => '2024-05-22',
                        'version' => ltrim($this->github_response['tag_name'], 'v'),
                        'author' => $this->plugin['AuthorName'],
                        'author_profile' => $this->plugin['AuthorURI'],
                        'last_updated' => $this->github_response['published_at'],
                        'homepage' => $this->plugin['PluginURI'],
                        'short_description' => $this->plugin['Description'],
                        'sections' => array(
                            'Description' => $this->plugin['Description'],
                            'Updates' => $this->github_response['body'],
                        ),
                        'download_link' => $this->github_response['zipball_url'],
                    );
                    return (object)$plugin; // Return the data
                }
            }
        }
        return $result; // Otherwise return default
    }

    /**
     * Download package.
     */
    public function addTokenInHeader($args, $url)
    {
        if (null !== $args['filename']) {
            if ($this->authorize_token) {
                $args = array_merge($args, array('headers' => array('Authorization' => "token {$this->authorize_token}")));
            }
        }
        remove_filter('http_request_args', [$this, 'addTokenInHeader'], 100);
        return $args;
    }

    /**
     * Extract and activate plugin.
     */
    public function afterInstall($response, $hook_extra, $result)
    {
        if (!empty($result['destination']) && strpos($result['destination'], dirname($this->basename)) !== false) {
            global $wp_filesystem; // Get global FS object
            $install_directory = plugin_dir_path($this->file); // Our plugin directory
            $wp_filesystem->move($result['destination'], $install_directory); // Move files to the plugin dir
            $result['destination'] = $install_directory; // Set the destination for the rest of the stack
            if (!empty($result['destination_name'])) {
                $result['destination_name'] = dirname($this->basename);
            }
        }
        return $result;
    }
}