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
    private $file, $plugin, $basename, $active, $username, $repository, $authorize_token, $github_response;

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

        // Add Authorization Token to downloadPackage
        add_filter('upgrader_pre_download', function () {
            add_filter('http_request_args', [$this, 'downloadPackage'], 15, 2);
            return false; // upgrader_pre_download filter default return value.
        });
    }

    /**
     * Get repo info.
     *
     * @return void
     */
    private function getRepositoryInfo()
    {
        if (is_null($this->github_response)) { // Do we have a response?
            $args = array();
            $request_uri = sprintf('https://api.github.com/repos/%s/%s/releases', $this->username, $this->repository); // Build URI
            if ($this->authorize_token) { // Is there an access token?
                $args['headers']['Authorization'] = 'token {$this->authorize_token}'; // Set the headers
            }
            $response = json_decode(wp_remote_retrieve_body(wp_remote_get($request_uri, $args)), true); // Get JSON and parse it
            if (is_array($response)) { // If it is an array
                $response = current($response); // Get the first item
            }
            $this->github_response = $response; // Set it to our property
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
        $this->active = function_exists('is_plugin_active') && is_plugin_active($this->basename);
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
            if ($transient->checked && $transient->checked[$this->basename]) { // Did WordPress check for updates?
                $this->getRepositoryInfo(); // Get the repo info
                $github_version = ltrim($this->github_response['tag_name'], 'v');
                $local_version = $transient->checked[$this->basename];
                $out_of_date = version_compare($github_version, $local_version, 'gt'); // Check if we're out of date
                if ($out_of_date) {
                    $new_files = $this->github_response['zipball_url']; // Get the ZIP
                    $slug = current(explode('/', $this->basename)); // Create valid slug
                    $plugin = array( // setup our plugin info
                        'url' => $this->plugin['PluginURI'],
                        'slug' => $slug,
                        'package' => $new_files,
                        'new_version' => ltrim($this->github_response['tag_name'], 'v'),
                    );
                    $transient->response[$this->basename] = (object)$plugin; // Return it in response
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
                $this->getRepositoryInfo(); // Get our repo info

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
        return $result; // Otherwise return default
    }

    /**
     * Download package.
     */
    public function downloadPackage($args, $url)
    {
        if (null !== $args['filename']) {
            if ($this->authorize_token) {
                $args = array_merge($args, array('headers' => array('Authorization' => 'token {$this->authorize_token}')));
            }
        }
        remove_filter('http_request_args', [$this, 'downloadPackage']);
        return $args;
    }

    /**
     * Extract and activate plugin.
     */
    public function afterInstall($response, $hook_extra, $result)
    {
        global $wp_filesystem; // Get global FS object
        $install_directory = plugin_dir_path($this->file); // Our plugin directory
        $wp_filesystem->move($result['destination'], $install_directory); // Move files to the plugin dir
        $result['destination'] = $install_directory; // Set the destination for the rest of the stack

        if ($this->active) { // If it was active
            activate_plugin($this->basename); // Reactivate
        }
        return $result;
    }
}