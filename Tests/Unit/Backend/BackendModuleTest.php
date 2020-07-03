<?php

declare(strict_types=1);

namespace AOE\Crawler\Tests\Unit\Backend;

/*
 * (c) 2020 AOE GmbH <dev@aoe.com>
 *
 * This file is part of the TYPO3 Crawler Extension.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use AOE\Crawler\Backend\BackendModule;
use AOE\Crawler\Converter\JsonCompatibilityConverter;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Localization\LanguageService;

class BackendModuleTest extends UnitTestCase
{
    /**
     * @var BackendModule
     */
    protected $subject;

    protected function setUp(): void
    {
        $mockedLanguageService = self::getAccessibleMock(LanguageService::class, ['sL'], [], '', false);
        $mockedLanguageService->expects($this->any())->method('sL')->willReturn('language string');

        $this->subject = self::getAccessibleMock(BackendModule::class, ['getLanguageService'], [], '', false);
        $this->subject->expects($this->any())->method('getLanguageService')->willReturn($mockedLanguageService);

        $jsonCompatibilityConverter = new JsonCompatibilityConverter();
        $this->subject->_set('jsonCompatibilityConverter', $jsonCompatibilityConverter);
    }

    /**
     * @test
     */
    public function modMenuReturnsExpectedArray(): void
    {
        $modMenu = $this->subject->modMenu();

        self::assertIsArray($modMenu);
        self::assertCount(
            7,
            $modMenu
        );

        self::assertArrayHasKey('depth', $modMenu);
        self::assertArrayHasKey('0', $modMenu['depth']);
        self::assertArrayHasKey('1', $modMenu['depth']);
        self::assertArrayHasKey('2', $modMenu['depth']);
        self::assertArrayHasKey('3', $modMenu['depth']);
        self::assertArrayHasKey('4', $modMenu['depth']);
        self::assertArrayHasKey('99', $modMenu['depth']);
        self::assertIsString($modMenu['depth'][0]);
        self::assertIsString($modMenu['depth'][1]);
        self::assertIsString($modMenu['depth'][2]);
        self::assertIsString($modMenu['depth'][3]);
        self::assertIsString($modMenu['depth'][4]);
        self::assertIsString($modMenu['depth'][99]);
        self::assertArrayHasKey('crawlaction', $modMenu);
        self::assertArrayHasKey('start', $modMenu['crawlaction']);
        self::assertArrayHasKey('log', $modMenu['crawlaction']);
        self::assertArrayHasKey('multiprocess', $modMenu['crawlaction']);
        self::assertIsString($modMenu['crawlaction']['start']);
        self::assertIsString($modMenu['crawlaction']['log']);
        self::assertIsString($modMenu['crawlaction']['multiprocess']);
        self::assertArrayHasKey('log_resultLog', $modMenu);
        self::assertArrayHasKey('log_feVars', $modMenu);
        self::assertArrayHasKey('processListMode', $modMenu);
        self::assertArrayHasKey('log_display', $modMenu);
        self::assertArrayHasKey('all', $modMenu['log_display']);
        self::assertArrayHasKey('pending', $modMenu['log_display']);
        self::assertArrayHasKey('finished', $modMenu['log_display']);
        self::assertIsString($modMenu['log_display']['all']);
        self::assertIsString($modMenu['log_display']['pending']);
        self::assertIsString($modMenu['log_display']['finished']);
        self::assertArrayHasKey('itemsPerPage', $modMenu);
        self::assertArrayHasKey('5', $modMenu['itemsPerPage']);
        self::assertArrayHasKey('10', $modMenu['itemsPerPage']);
        self::assertArrayHasKey('50', $modMenu['itemsPerPage']);
        self::assertArrayHasKey('0', $modMenu['itemsPerPage']);
        self::assertIsString($modMenu['itemsPerPage'][5]);
        self::assertIsString($modMenu['itemsPerPage'][10]);
        self::assertIsString($modMenu['itemsPerPage'][50]);
        self::assertIsString($modMenu['itemsPerPage'][0]);
    }

    /**
     * @test
     * @dataProvider getResFeVarsDataProvider
     */
    public function getResFeVars(array $resultData, array $expected): void
    {
        self::assertSame(
            $expected,
            $this->subject->_call('getResFeVars', $resultData)
        );
    }

    public function getResFeVarsDataProvider(): array
    {
        return [
            'ResultData is empty, therefore empty array returned' => [
                'resultData' => [],
                'expected' => [],
            ],
            'result data does not contain vars' => [
                'resultData' => [
                    'content' => json_encode(['not-vars' => 'some value']),
                ],
                'expected' => [],
            ],
            'Result data vars is present by empty, therefore empty array is returned' => [
                'resultData' => [
                    'content' => json_encode(['vars' => []]),
                ],
                'expected' => [],
            ],
            'Result data vars is present and not empty' => [
                'resultData' => [
                    'content' => json_encode(['vars' => ['fe-one', 'fe-two']]),
                ],
                'expected' => ['fe-one', 'fe-two'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getResultLogDataProvider
     */
    public function getResultLog(array $resultLog, string $expected): void
    {
        self::assertSame(
            $expected,
            $this->subject->_call('getResultLog', $resultLog)
        );
    }

    public function getResultLogDataProvider(): array
    {
        return [
            'ResultRow key result_data does not exist' => [
                'resultRow' => [
                    'other-key' => 'value',
                ],
                'expected' => '',
            ],
            'ResultRow key result_data does exist, but empty' => [
                'resultRow' => [
                    'result_data' => '',
                ],
                'expected' => '',
            ],
            /* Bug We don't handle when result row doesn't contain content key */
            'ResultRow key result_data exits, is not empty, but does not contain content key' => [
                'resultRow' => [
                    'result_data' => json_encode(['not-content' => 'value']),
                ],
                'expected' => '',
            ],
            'ResultRow key result_data exits and is not empty, does not contain log' => [
                'resultRow' => [
                    'result_data' => json_encode(['content' => json_encode(['not-log' => ['ok']])]),
                ],
                'expected' => '',
            ],
            'ResultRow key result_data exits and is not empty, does contain log (1 element)' => [
                'resultRow' => [
                    'result_data' => json_encode(['content' => json_encode(['log' => ['ok']])]),
                ],
                'expected' => 'ok',
            ],
            'ResultRow key result_data exits and is not empty, does contain log (2 elements)' => [
                'resultRow' => [
                    'result_data' => json_encode(['content' => json_encode(['log' => ['ok', 'success']])]),
                ],
                'expected' => 'ok' . chr(10) . 'success',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getResStatusDataProvider
     */
    public function getResStatus($requestContent, string $expected): void
    {
        self::assertSame(
            $expected,
            $this->subject->_call('getResStatus', $requestContent)
        );
    }

    public function getResStatusDataProvider(): array
    {
        return [
            'requestContent is not array' => [
                'requestContent' => null,
                'expected' => '-',
            ],
            'requestContent is empty array' => [
                'requestContent' => [],
                'expected' => '-',
            ],
            'requestContent["content"] index does not exist' => [
                'requestContent' => ['not-content' => 'value'],
                'expected' => 'Content index does not exists in requestContent array',
            ],
            'errorlog is present but empty' => [
                'requestContent' => ['content' => json_encode(['errorlog' => []])],
                'expected' => 'OK',
            ],
            'errorlog is present and not empty (1 Element)' => [
                'requestContent' => ['content' => json_encode(['errorlog' => ['500 Internal Server error']])],
                'expected' => '500 Internal Server error',
            ],
            'errorlog is present and not empty (2 Element)' => [
                'requestContent' => ['content' => json_encode(['errorlog' => ['500 Internal Server Error', '503 Service Unavailable']])],
                'expected' => '500 Internal Server Error' . chr(10) . '503 Service Unavailable',
            ],
            'requestResult is boolean' => [
                'requestContent' => ['content' => 'This string is neither json or serialized, therefor convert returns false'],
                'expected' => 'Error - no info, sorry!',
            ],
            // Missing test case for the return 'Error: ' (last return)

        ];
    }
}
