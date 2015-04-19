<?php

/**
 * RTF Xml Open Group Test Case
 *
 * This class is made to test the RtfXmlOpenGroup class.
 *
 * @author     Arnaud PETIT
 * @copyright  2014 Arnaud PETIT
 * @license    GNU GPLv2
 * @version    1
 * @link       https://github.com/Anastaszor/PhpRtfToHtml
 */
class RtfXmlOpenGroupTest extends PHPUnit_Framework_TestCase
{
	
	/**
	 * 
	 * @var RtfXmlOpenGroup
	 */
	private $_object = null;
	
	/**
	 * (non-PHPdoc)
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	public function setUp()
	{
		$this->_object = new RtfXmlOpenGroup();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see PHPUnit_Framework_TestCase::tearDown()
	 */
	public function tearDown()
	{
		$this->_object = null;
	}
	
	
	public function test__toString()
	{
		$this->assertSame('\\xmlopen', $this->_object->__toString());
	}
	
	public function test__toRtf()
	{
		$this->assertSame('{\\*\\xmlopen}', $this->_object->__toRtf());
	}
	
	public function test__toHtml()
	{
		$this->assertSame('\\xmlopen', $this->_object->__toHtml());
	}
	
}
