<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Config\Writer;

use Zend\Config\Writer\PhpArray;
use Zend\Config\Config;
use ZendTest\Config\Writer\TestAssets\PhpReader;

/**
 * @group      Zend_Config
 */
class PhpArrayTest extends AbstractWriterTestCase
{
    protected $_tempName;

    public function setUp()
    {
        $this->writer = new PhpArray();
        $this->reader = new PhpReader();
    }

    /**
     * @group ZF-8234
     */
    public function testRender()
    {
        $config = new Config([
            'test' => 'foo',
            'bar' => [0 => 'baz', 1 => 'foo'],
            'emptyArray' => [],
            'object' => (object) ['foo' => 'bar'],
            'integer' => 123,
            'boolean' => false,
            'null' => null,
        ]);

        $configString = $this->writer->toString($config);

        // build string line by line as we are trailing-whitespace sensitive.
        $expected = "<?php\n";
        $expected .= "return array(\n";
        $expected .= "    'test' => 'foo',\n";
        $expected .= "    'bar' => array(\n";
        $expected .= "        0 => 'baz',\n";
        $expected .= "        1 => 'foo',\n";
        $expected .= "    ),\n";
        $expected .= "    'emptyArray' => array(),\n";
        $expected .= "    'object' => stdClass::__set_state(array(\n";
        $expected .= "   'foo' => 'bar',\n";
        $expected .= ")),\n";
        $expected .= "    'integer' => 123,\n";
        $expected .= "    'boolean' => false,\n";
        $expected .= "    'null' => null,\n";
        $expected .= ");\n";

        $this->assertEquals($expected, $configString);
    }

    public function testRenderWithBracketArraySyntax()
    {
        $config = new Config(['test' => 'foo', 'bar' => [0 => 'baz', 1 => 'foo'], 'emptyArray' => []]);

        $this->writer->setUseBracketArraySyntax(true);
        $configString = $this->writer->toString($config);

        // build string line by line as we are trailing-whitespace sensitive.
        $expected = "<?php\n";
        $expected .= "return [\n";
        $expected .= "    'test' => 'foo',\n";
        $expected .= "    'bar' => [\n";
        $expected .= "        0 => 'baz',\n";
        $expected .= "        1 => 'foo',\n";
        $expected .= "    ],\n";
        $expected .= "    'emptyArray' => [],\n";
        $expected .= "];\n";

        $this->assertEquals($expected, $configString);
    }

    public function testRenderWithQuotesInString()
    {
        $config = new Config(['one' => 'Test with "double" quotes', 'two' => 'Test with \'single\' quotes']);

        $configString = $this->writer->toString($config);

        $expected = "<?php\n";
        $expected .= "return array(\n";
        $expected .= "    'one' => 'Test with \"double\" quotes',\n";
        $expected .= "    'two' => 'Test with \\'single\\' quotes',\n";
        $expected .= ");\n";

        $this->assertEquals($expected, $configString);
    }

    public function testWriteConvertsPathToDirWhenWritingBackToFile()
    {
        $filename = $this->getTestAssetFileName();
        file_put_contents($filename, file_get_contents(__DIR__ . '/_files/array.php'));

        $this->writer->toFile($filename, include $filename);

        // Ensure file endings are same
        $expected = trim(file_get_contents(__DIR__ . '/_files/array.php'));
        $expected = preg_replace("~\r\n|\n|\r~", PHP_EOL, $expected);

        $result = trim(file_get_contents($filename));
        $result = preg_replace("~\r\n|\n|\r~", PHP_EOL, $result);

        $this->assertSame($expected, $result);
    }

    public function testRenderWithClassNameScalarsEnabled()
    {
        $this->writer->setUseClassNameScalars(true);

        $dummyFqnA = 'ZendTest\Config\Writer\TestAssets\DummyClassA';
        $dummyFqnB = 'ZendTest\Config\Writer\TestAssets\DummyClassB';

        // Dummy classes should not be loaded prior this test
        $this->assertFalse(class_exists($dummyFqnA, false));
        $this->assertFalse(class_exists($dummyFqnB, false));

        $config = new Config([
            'PhpArrayTest' => 'PhpArrayTest',
            '' => 'emptyString',
            'TestAssets\DummyClass' => 'foo',
            $dummyFqnA => [
                'fqnValue' => $dummyFqnB
            ]
        ]);

        $expected = "<?php\n";
        $expected .= "return array(\n";
        $expected .= "    'PhpArrayTest' => 'PhpArrayTest',\n";
        $expected .= "    '' => 'emptyString',\n";
        $expected .= "    'TestAssets\\\\DummyClass' => 'foo',\n";
        $expected .= "    $dummyFqnA::class => array(\n";
        $expected .= "        'fqnValue' => $dummyFqnB::class,\n";
        $expected .= "    ),\n";
        $expected .= ");\n";

        $result = $this->writer->toString($config);

        $this->assertSame($expected, $result);
    }

    public function testSetUseBracketArraySyntaxReturnsFluentInterface()
    {
        $this->assertSame($this->writer, $this->writer->setUseBracketArraySyntax(true));
    }

    public function testSetUseClassNameScalarsReturnsFluentInterface()
    {
        $this->assertSame($this->writer, $this->writer->setUseClassNameScalars(true));
    }
}
