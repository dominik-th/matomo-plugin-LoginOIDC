<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\LoginOIDC\tests\Unit;

use Piwik\Plugins\LoginOIDC\Url;

/**
 * @group LoginOIDC
 * @group UrlTest
 * @group Plugins
 */
class UrlTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Default url string to test with.
     *
     * @var string
     */
    private string $urlString;

    /**
     * Default url object to test with.
     *
     * @var Url
     */
    private Url $url;

    public function setUp() : void
    {
        parent::setUp();
        $this->urlString = "https://example.com/test?key=val&second=2&third=three";
        $this->url = new Url($this->urlString);
    }

    public function testRebuildingUrl() : void
    {
        $this->assertEquals($this->url->buildString(), $this->urlString);
    }

    public function testReadingQueryParameters() : void
    {
        $this->assertEquals($this->url->getQueryParameter("key"), "val");
        $this->assertEquals($this->url->getQueryParameter("second"), "2");
        $this->assertEquals($this->url->getQueryParameter("third"), "three");
    }

    public function testUpdatingQueryParameters() : void
    {
        $this->url->setQueryParameter("key", "one");
        $this->url->setQueryParameter("second", "two");
        $this->url->setQueryParameter("third", "3");
        $this->url->setQueryParameter("fourth", "4");

        $this->assertEquals($this->url->getQueryParameter("key"), "one");
        $this->assertEquals($this->url->getQueryParameter("second"), "two");
        $this->assertEquals($this->url->getQueryParameter("third"), "3");
        $this->assertEquals($this->url->getQueryParameter("fourth"), "4");
    }

    public function testRebuilingModifiedUrl() : void
    {
        $targetUrlString = "https://example.com/test?key=one&second=two&third=3&fourth=4";
        $this->url->setQueryParameter("key", "one");
        $this->url->setQueryParameter("second", "two");
        $this->url->setQueryParameter("third", "3");
        $this->url->setQueryParameter("fourth", "4");

        $this->assertEquals($this->url->buildString(), $targetUrlString);
    }
}
