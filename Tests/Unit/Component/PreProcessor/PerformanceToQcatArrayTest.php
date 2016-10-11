<?php
namespace CPSIT\CourseQcatExport\Tests\PreProcessor;


/***************************************************************
 *  Copyright notice
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use CPSIT\CourseQcatExport\Component\PreProcessor\PerformanceToQcatArray;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class PerformanceToQcatArrayTest
 *
 * @package CPSIT\CourseQcatExport\Tests\Component\PreProcessor
 */
class PerformanceToQcatArrayTest extends UnitTestCase
{
	/**
	 * @var PerformanceToQcatArray|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface
	 */
	protected $subject;

	public function setUp()
	{
		$this->subject = $this->getAccessibleMock(
			PerformanceToQcatArray::class,
			['dummy'], [], '', FALSE
		);
	}

	/**
	 * @test
	 */
	public function isConfigurationValidReturnsFalseForMissingClass()
	{
		$testConfig = [];

		$this->assertFalse(
			$this->subject->isConfigurationValid($testConfig)
		);
	}
}
