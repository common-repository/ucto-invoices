<?php

/**
 * Class BaseUctoplusAPI
 *
 * @author MimoGraphix <mimographix@gmail.com>
 * @copyright Epic Fail | Studio
 */
abstract class BaseUctoplusAPI
{
    private $apiUrl = 'https://moje.uctoplus.sk/api/v2';

    protected $options;

    public function __construct()
    {
        $this->loadOptions();
    }

    protected function loadOptions()
    {
        $this->options = get_option('uctoplus_options');
    }

    public function setApiKey($apiKey)
    {
        $this->options[ 'uctoplus_api_key' ] = $apiKey;
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        if (isset($this->options[ 'sandbox_enviroment' ])) {
            return $this->apiUrl."/sandbox";
        }
        return $this->apiUrl."/production";
    }

    /**
     * @param $method
     * @param $endpoint
     * @param $data
     *
     * @return mixed|string
     */
    protected function request($method, $endpoint, $data = null)
    {
        $headers = [];
        $headers['Content-type'] = 'application/json';
        $headers['api-key'] = $this->options[ 'uctoplus_api_key' ];

        if ($method === "POST") {
            $response = wp_remote_post($this->getUrl().$endpoint, [
                'headers' => $headers,
                'body' => ( $data != null ? json_encode($data, JSON_UNESCAPED_UNICODE) : "" ),
                'timeout' => 30
            ]);
        } elseif ($method === "GET") {
            $response = wp_remote_get($this->getUrl().$endpoint, [
                'headers' => $headers,
                'timeout' => 30
            ]);
        }

        $result = wp_remote_retrieve_body($response);
        $http_code = wp_remote_retrieve_response_code($response);

        switch ($http_code) {
            case 200:
                $result = json_decode($result);
                break;
            case 300:
            case 400:
            case 401:
                return json_decode($result);
                break;
            default:
                throw new \Exception('Unexpected HTTP code: '.$http_code);
        }

        return $result;
    }

}