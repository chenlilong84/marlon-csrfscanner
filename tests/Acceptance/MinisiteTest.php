<?php
namespace Acceptance;

class MinisiteTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @test
	 * @covers Scanner\Console\ScanCommand
	 * @covers Scanner\Rule\AbstractRule<extended>
	 */
	public function ScansMinisite()
	{
		$csrfscan = realpath(__DIR__.'/../../bin/csrfscan');
		$profile = realpath(__DIR__.'/../minisite.profile');
		if ( !is_file($profile) ) {
			$profile = __DIR__.'/../minisite.profile.dist';
		}
		$command = "$csrfscan scan $profile --no-ansi";

		$output = array();
		$return = null;
		exec($command, $output, $return);
		$output = implode(PHP_EOL, $output);

$expected =  <<<END
Patience...
http://localhost:8888/

http://localhost:8888/tokennotcheckedform.php
   |_ <form name="tokennotcheckedform" action="http://localhost:8888/tokennotcheckedform.php">
      |_ 403 response expected, but got a 200

http://localhost:8888/notokenform.php
   |_ <form name="notokenform" action="http://localhost:8888/notokenform.php">
      |_ No 'token' input field found
      |_ No 'token' input field found

http://localhost:8888/goodform.php
   |_ <form name="goodform" action="http://localhost:8888/goodform.php">
   |_ <form name="bogusform" action="http://localhost:8888/goodform.php">
      |_ No 'token' input field found
      |_ No 'token' input field found

http://localhost:8888/nestedpage.php

Duration: 0 seconds
Found 4 unique forms on 5 pages
5 violations found.
END;

		$this->assertEquals($expected, $output);
		$this->assertEquals(1, $return, "The command's exit code should be 1 (ScanCommand::EXIT_ERROR)");
	}
}