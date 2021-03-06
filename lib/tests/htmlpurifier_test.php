<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for the HTMLPurifier integration
 *
 * @package    core
 * @category   phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * HTMLPurifier test case
 *
 * @package    core
 * @category   phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_htmlpurifier_testcase extends basic_testcase {

    /**
     * Verify _blank target is allowed
     * @return void
     */
    public function test_allow_blank_target() {
        $text = '<a href="http://moodle.org" target="_blank">Some link</a>';
        $result = format_text($text, FORMAT_HTML);
        $this->assertSame($text, $result);

        $result = format_text('<a href="http://moodle.org" target="some">Some link</a>', FORMAT_HTML);
        $this->assertSame('<a href="http://moodle.org">Some link</a>', $result);
    }

    /**
     * Verify our nolink tag accepted
     * @return void
     */
    public function test_nolink() {
        // we can not use format text because nolink changes result
        $text = '<nolink><div>no filters</div></nolink>';
        $result = purify_html($text, array());
        $this->assertSame($text, $result);

        $text = '<nolink>xxx<em>xx</em><div>xxx</div></nolink>';
        $result = purify_html($text, array());
        $this->assertSame($text, $result);
    }

    /**
     * Verify our tex tag accepted
     * @return void
     */
    public function test_tex() {
        $text = '<tex>a+b=c</tex>';
        $result = purify_html($text, array());
        $this->assertSame($text, $result);
    }

    /**
     * Verify our algebra tag accepted
     * @return void
     */
    public function test_algebra() {
        $text = '<algebra>a+b=c</algebra>';
        $result = purify_html($text, array());
        $this->assertSame($text, $result);
    }

    /**
     * Verify our hacky multilang works
     * @return void
     */
    public function test_multilang() {
        $text = '<lang lang="en">hmmm</lang><lang lang="anything">hm</lang>';
        $result = purify_html($text, array());
        $this->assertSame($text, $result);

        $text = '<span lang="en" class="multilang">hmmm</span><span lang="anything" class="multilang">hm</span>';
        $result = purify_html($text, array());
        $this->assertSame($text, $result);

        $text = '<span lang="en">hmmm</span>';
        $result = purify_html($text, array());
        $this->assertNotSame($text, $result);

        // keep standard lang tags

        $text = '<span lang="de_DU" class="multilang">asas</span>';
        $result = purify_html($text, array());
        $this->assertSame($text, $result);

        $text = '<lang lang="de_DU">xxxxxx</lang>';
        $result = purify_html($text, array());
        $this->assertSame($text, $result);
    }

    /**
     * Tests the 'allowid' option for format_text.
     * @return void
     */
    public function test_format_text_allowid() {
        // Start off by not allowing ids (default)
        $options = array(
            'nocache' => true
        );
        $result = format_text('<div id="example">Frog</div>', FORMAT_HTML, $options);
        $this->assertSame('<div>Frog</div>', $result);

        // Now allow ids
        $options['allowid'] = true;
        $result = format_text('<div id="example">Frog</div>', FORMAT_HTML, $options);
        $this->assertSame('<div id="example">Frog</div>', $result);
    }

    /**
     * Test if linebreaks kept unchanged.
     * @return void
     */
    public function test_line_breaking() {
        $text = "\n\raa\rsss\nsss\r";
        $this->assertSame($text, purify_html($text));
    }

    /**
     * Test fixing of strict problems.
     * @return void
     */
    public function test_tidy() {
        $text = "<p>xx";
        $this->assertSame('<p>xx</p>', purify_html($text));

        $text = "<P>xx</P>";
        $this->assertSame('<p>xx</p>', purify_html($text));

        $text = "xx<br>";
        $this->assertSame('xx<br />', purify_html($text));
    }

    /**
     * Test nesting - this used to cause problems in earlier versions
     * @return void
     */
    public function test_nested_lists() {
        $text = "<ul><li>One<ul><li>Two</li></ul></li><li>Three</li></ul>";
        $this->assertSame($text, purify_html($text));
    }

    /**
     * Test that XSS protection works, complete smoke tests are in htmlpurifier itself.
     * @return void
     */
    public function test_cleaning_nastiness() {
        $text = "x<SCRIPT>alert('XSS')</SCRIPT>x";
        $this->assertSame('xx', purify_html($text));

        $text = '<DIV STYLE="background-image:url(javascript:alert(\'XSS\'))">xx</DIV>';
        $this->assertSame('<div>xx</div>', purify_html($text));

        $text = '<DIV STYLE="width:expression(alert(\'XSS\'));">xx</DIV>';
        $this->assertSame('<div>xx</div>', purify_html($text));

        $text = 'x<IFRAME SRC="javascript:alert(\'XSS\');"></IFRAME>x';
        $this->assertSame('xx', purify_html($text));

        $text = 'x<OBJECT TYPE="text/x-scriptlet" DATA="http://ha.ckers.org/scriptlet.html"></OBJECT>x';
        $this->assertSame('xx', purify_html($text));

        $text = 'x<EMBED SRC="http://ha.ckers.org/xss.swf" AllowScriptAccess="always"></EMBED>x';
        $this->assertSame('xx', purify_html($text));

        $text = 'x<form></form>x';
        $this->assertSame('xx', purify_html($text));
    }
}

