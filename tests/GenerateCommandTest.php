<?php

use Aws\Magerun\Modman\Generate\AbsoluteCommand;

// TODO Replace with YAML source providers and expected values.

class GenerateCommandTest extends PHPUnit_Framework_TestCase
{
    /** @var  AbsoluteCommand */
    protected $command;

    public function setUp()
    {
        $this->command = new AbsoluteCommand();
    }

    /**
     * @dataProvider rewrites
     * @param string $source
     * @param string $expected
     */
    public function testRewrite($source, $expected)
    {
        $this->command->raw = false;
        $this->assertSame($expected, $this->command->rewritePath($source));
    }

    /**
     * @return array
     */
    public function rewrites()
    {
        return array(
            array('app/code/community/foo/bar/etc/config.xml', 'app/code/community/foo/bar'),
            array('app/code/community/foo_bar/baz/etc/config.xml', 'app/code/community/foo_bar/baz'),
            array('app/code/community/fooBar/baz/etc/config.xml', 'app/code/community/fooBar/baz'),
            array('lib/foo/bar.php', 'lib/foo'),
            array('js/foo/bar/baz.js', 'js/foo/bar'),
            array('app/design/frontend/base/default/layout/foo/layout.xml', 'app/design/frontend/base/default/layout/foo'),
            array('app/design/frontend/base/default/template/foo/bar.phtml', 'app/design/frontend/base/default/template/foo'),
            array('skin/frontend/base/default/foo/bar/baz.css', 'skin/frontend/base/default/foo/bar')
        );
    }

    /**
     * @dataProvider rewritesAbsolute
     * @param string $source
     * @param string $expected
     */
    public function testAbsoluteRewrites($source, $expected)
    {
        $this->command->raw = false;
        $result = $this->command->processPaths(array($source));
        if (is_array($result)) {
            $result = key($result) . '=>' . $result[key($result)];
        }
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function rewritesAbsolute()
    {
        return array(
            // Not RAW
            array('app/code/local/Foo/Bar/emulate/js/foo/bar/some.js', 'app/code/local/Foo/Bar/emulate/js/foo/bar=>js/foo/bar'),
            array('app/code/local/Foo/Bar/emulate/app/etc/modules/Foo_Bar.xml', 'app/code/local/Foo/Bar/emulate/app/etc/modules/Foo_Bar.xml=>app/etc/modules/Foo_Bar.xml'),
            array('app/code/local/Foo/Bar/emulate/app/design/frontend/base/default/template/foo/bar.phtml', 'app/code/local/Foo/Bar/emulate/app/design/frontend/base/default/template/foo=>app/design/frontend/base/default/template/foo'),
            array('app/code/local/Foo/Bar/emulate/app/design/adminhtml/base/default/layout/foo/bar.xml', 'app/code/local/Foo/Bar/emulate/app/design/adminhtml/base/default/layout/foo=>app/design/adminhtml/base/default/layout/foo')
        );
    }

    /**
     * @dataProvider rewritesAbsoluteRaw
     * @param string $source
     * @param string $expected
     */
    public function testAbsoluteRewritesRaw($source, $expected)
    {
        $this->command->raw = true;
        $result = $this->command->processPaths(array($source), true);
        if (is_array($result)) {
            $result = key($result) . '=>' . $result[key($result)];
        }
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function rewritesAbsoluteRaw()
    {
        return array(
            // Not RAW
            array('app/code/local/Foo/Bar/emulate/js/foo/bar/some.js', 'app/code/local/Foo/Bar/emulate/js/foo/bar/some.js=>js/foo/bar/some.js'),
            array('app/code/local/Foo/Bar/emulate/app/etc/modules/Foo_Bar.xml', 'app/code/local/Foo/Bar/emulate/app/etc/modules/Foo_Bar.xml=>app/etc/modules/Foo_Bar.xml'),
            array('app/code/local/Foo/Bar/emulate/app/design/frontend/base/default/template/foo/bar.phtml', 'app/code/local/Foo/Bar/emulate/app/design/frontend/base/default/template/foo/bar.phtml=>app/design/frontend/base/default/template/foo/bar.phtml'),
            array('app/code/local/Foo/Bar/emulate/app/design/adminhtml/base/default/layout/foo/bar.xml', 'app/code/local/Foo/Bar/emulate/app/design/adminhtml/base/default/layout/foo/bar.xml=>app/design/adminhtml/base/default/layout/foo/bar.xml')
        );
    }

    /**
     * @dataProvider noRewrites
     * @param string $source
     */
    public function testSkipsRewrite($source)
    {
        $this->assertSame($source, $this->command->rewritePath($source));
    }

    public function noRewrites()
    {
        return array(
            array('app/code/local/Mage/Catalog/somefile.php'),
            array('lib/Varien/overwrite.php')
        );
    }
}
