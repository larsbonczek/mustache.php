<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2012 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mustache\Test;

use Mustache\Compiler;
use Mustache\Mustache;
use Mustache\Loader\StringLoader;
use Mustache\Loader\ArrayLoader;
use Mustache\Parser;
use Mustache\Tokenizer;

/**
 * @group unit
 */
class MustacheTest extends \PHPUnit_Framework_TestCase {

	private static $tempDir;

	public static function setUpBeforeClass() {
		self::$tempDir = sys_get_temp_dir() . '/mustache_test';
		if (file_exists(self::$tempDir)) {
			self::rmdir(self::$tempDir);
		}
	}

	public function testConstructor() {
		$loader         = new StringLoader;
		$partialsLoader = new ArrayLoader;
		$mustache       = new Mustache(array(
			'template_class_prefix' => '__whot__',
			'cache' => self::$tempDir,
			'loader' => $loader,
			'partials_loader' => $partialsLoader,
			'partials' => array(
				'foo' => '{{ foo }}',
			),
			'charset' => 'ISO-8859-1',
		));

		$this->assertSame($loader, $mustache->getLoader());
		$this->assertSame($partialsLoader, $mustache->getPartialsLoader());
		$this->assertEquals('{{ foo }}', $partialsLoader->load('foo'));
		$this->assertContains('__whot__', $mustache->getTemplateClassName('{{ foo }}'));
		$this->assertEquals('ISO-8859-1', $mustache->getCharset());
	}

	public function testSettingServices() {
		$loader    = new StringLoader;
		$tokenizer = new Tokenizer;
		$parser    = new Parser;
		$compiler  = new Compiler;
		$mustache  = new Mustache;

		$this->assertNotSame($loader, $mustache->getLoader());
		$mustache->setLoader($loader);
		$this->assertSame($loader, $mustache->getLoader());

		$this->assertNotSame($loader, $mustache->getPartialsLoader());
		$mustache->setPartialsLoader($loader);
		$this->assertSame($loader, $mustache->getPartialsLoader());

		$this->assertNotSame($tokenizer, $mustache->getTokenizer());
		$mustache->setTokenizer($tokenizer);
		$this->assertSame($tokenizer, $mustache->getTokenizer());

		$this->assertNotSame($parser, $mustache->getParser());
		$mustache->setParser($parser);
		$this->assertSame($parser, $mustache->getParser());

		$this->assertNotSame($compiler, $mustache->getCompiler());
		$mustache->setCompiler($compiler);
		$this->assertSame($compiler, $mustache->getCompiler());
	}

	/**
	 * @group functional
	 */
	public function testCache() {
		$mustache = new Mustache(array(
			'template_class_prefix' => '__whot__',
			'cache' => self::$tempDir,
		));

		$source    = '{{ foo }}';
		$template  = $mustache->loadTemplate($source);
		$className = $mustache->getTemplateClassName($source);
		$fileName  = self::$tempDir . '/' . $className . '.php';
		$this->assertInstanceOf($className, $template);
		$this->assertFileExists($fileName);
		$this->assertContains("\nclass $className extends \Mustache\Template", file_get_contents($fileName));
	}

	/**
	 * @group functional
	 * @expectedException \RuntimeException
	 */
	public function testCacheFailsThrowException() {
		global $mustacheFilesystemRenameHax;

		$mustacheFilesystemRenameHax = true;

		$mustache = new Mustache(array('cache' => self::$tempDir));
		$mustache->loadTemplate('{{ foo }}');
	}

	/**
	 * @expectedException \RuntimeException
	 */
	public function testImmutablePartialsLoadersThrowException() {
		$mustache = new Mustache(array(
			'partials_loader' => new StringLoader,
		));

		$mustache->setPartials(array('foo' => '{{ foo }}'));
	}
	private static function rmdir($path) {
		$path = rtrim($path, '/').'/';
		$handle = opendir($path);
		while (($file = readdir($handle)) !== false) {
			if ($file == '.' || $file == '..') {
				continue;
			}

			$fullpath = $path.$file;
			if (is_dir($fullpath)) {
				self::rmdir($fullpath);
			} else {
				unlink($fullpath);
			}
		}

		closedir($handle);
		rmdir($path);
	}
}


// It's prob'ly best if you ignore this bit.

namespace Mustache;

function rename($a, $b) {
	global $mustacheFilesystemRenameHax;

	return ($mustacheFilesystemRenameHax) ? false : \rename($a, $b);
}