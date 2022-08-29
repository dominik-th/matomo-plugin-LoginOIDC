<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\LoginOIDC;

class Url
{
    /**
     * Scheme of the url.
     *
     * @var string
     */
    protected $scheme;

    /**
     * Username as part of the url.
     *
     * @var string
     */
    protected $username;

    /**
     * Password as part of the url.
     *
     * @var string
     */
    protected $password;

    /**
     * Full host of the url.
     *
     * @var string
     */
    protected $host;

    /**
     * Port of the url.
     *
     * @var int
     */
    protected $port;

    /**
     * Path of the url.
     *
     * @var string
     */
    protected $path;

    /**
     * Query parameters of the url.
     *
     * @var array
     */
    protected $query;

    /**
     * Fragment identifier of the url.
     *
     * @var string
     */
    protected $fragment;

    /**
     * Constructor.
     *
     * @param string  $url full URL as string
     */
    public function __construct(string $url)
    {
        $urlParts = parse_url($url);

        $this->scheme = $urlParts["scheme"] ?? null;
        $this->host = $urlParts["host"] ?? null;
        $this->port = $urlParts["port"] ?? null;
        $this->path = $urlParts["path"] ?? null;

        if (isset($urlParts["query"])) { 
            parse_str($urlParts["query"], $this->query);
        }
    }

    /**
     * Build a full url string based on the parts.
     *
     * @return string
     */
    public function buildString() : string
    {
        $url = $this->scheme . "://";
        if (!empty($this->username) || !empty($this->password)) {
            $url .= $this->username . ":" . $this->password . "@";
        }
        $url .= $this->host;
        if (!empty($this->port)) {
            $url .= ":" . $this->port;
        }
        $url .= $this->path;
        if (!empty($this->query)) {
            $url .= "?" . http_build_query($this->query);
        }
        if (!empty($this->fragment)) {
            $url .= "#" . $this->fragment;
        }
        return $url;
    }

    /**
     * Get value of a given query parameter.
     *
     * @return string
     */
    public function getQueryParameter(string $parameter) : string
    {
        return $this->query[$parameter];
    }

    /**
     * Set value of a given query parameter.
     *
     * @return void
     */
    public function setQueryParameter(string $parameter, string $value) : void
    {
        $this->query[$parameter] = $value;
    }
}
