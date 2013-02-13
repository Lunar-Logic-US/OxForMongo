<?php
/**
 *    Copyright (c) 2012 Lunar Logic LLC
 *
 *    This program is free software: you can redistribute it and/or  modify
 *    it under the terms of the GNU Affero General Public License, version 3,
 *    as published by the Free Software Foundation.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DatesTest extends PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        define ('DIR_FRAMEWORK',dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR);
        define ('DIR_FRAMELIB', DIR_FRAMEWORK . 'lib' . DIRECTORY_SEPARATOR );
        define ('OX_FRAMEINTERFACE', DIR_FRAMELIB . 'interfaces' . DIRECTORY_SEPARATOR);
        define ('OX_FRAME_DEFAULT', DIR_FRAMEWORK . 'default' . DIRECTORY_SEPARATOR);
        define ('OX_FRAME_EXCEPTIONS', DIR_FRAMELIB . 'exceptions' . DIRECTORY_SEPARATOR);

        define ('DIR_APP',dirname(dirname(__FILE__)) . '/tmp/');
        define ('DIR_APPLIB', DIR_APP . 'lib'. DIRECTORY_SEPARATOR);
        define('DIR_CONSTRUCT',DIR_APP . 'constructs' . DIRECTORY_SEPARATOR);
        define('DIR_COMMON',DIR_CONSTRUCT . '_common' . DIRECTORY_SEPARATOR);

        require_once(DIR_FRAMELIB . 'Dates.php');
    }

    protected function tearDown()
    {
    }

    /**
     * Get the date range for an ISO week based on a single date
     */
    public function testGetDateRangeISOWeek() {
        $date = mongoDate('3/3/1981');
        
        $date_range = DateRange::getISOWeekForDate($date);
        
        $expected = array(
            1981=>array(
                3=>array(
                    10=>array(
                        mongoDate('3/2/1981'),
                        mongoDate('3/3/1981'),
                        mongoDate('3/4/1981'),
                        mongoDate('3/5/1981'),
                        mongoDate('3/6/1981'),
                        mongoDate('3/7/1981'),
                        mongoDate('3/8/1981')
                    )
                )
            )
        );
        
        $indexed_date_range = $date_range->getIndexedDateRange();
        $diff = array();
        arrayDiffAssocRecursive($expected, $indexed_date_range, $diff);
        
        $this->assertTrue(empty($diff));
    }

    /**
     * Both dates are the same
     */
    public function testDateRangeSameDates() {
        $date_range = new DateRange(mongoDate('10/12/2012'), mongoDate('10/12/2012'));
        
        $expected = array(
            2012=>array(
                10=>array(
                    41=>array(
                        mongoDate('10/12/2012')
                    )
                )
            )
        );
        
        $indexed_date_range = $date_range->getIndexedDateRange();
        $diff = array();
        arrayDiffAssocRecursive($expected, $indexed_date_range, $diff);
        //$date_range->debugPrint();
        
        $this->assertTrue(empty($diff));
        
    }
    
    /**
     * Daylight savings time spring
     */
    public function testDateRangeCrossDaylightSavingsSpring() {
        $date_range = new DateRange(mongoDate('3/10/2012'), mongoDate('3/12/2012'));
        
        $expected = array(
            2012=>array(
                3=>array(
                    10=>array(
                        mongoDate('3/10/2012'),
                        mongoDate('3/11/2012')
                    ),
                    11=>array(
                        mongoDate('3/12/2012')
                    )
                )
            )
        );
       
        $indexed_date_range = $date_range->getIndexedDateRange();
        $diff = array();
        arrayDiffAssocRecursive($expected, $indexed_date_range, $diff);
        
        $this->assertTrue(empty($diff));
    }
    
    /**
     * Daylight savings fall
     */
    public function testDateRangeCrossDaylightSavingsFall() {
        
        $date_range = new DateRange(mongoDate('11/3/2012'), mongoDate('11/5/2012'));
        
        $expected = array(
            2012=>array(
                11=>array(
                    44=>array(
                        mongoDate('11/3/2012'),
                        mongoDate('11/4/2012')
                    ),
                    45=>array(
                        mongoDate('11/5/2012')
                    )
                )
            )
        );
        //$date_range->debugPrint();
       
        $indexed_date_range = $date_range->getIndexedDateRange();
        $diff = array();
        arrayDiffAssocRecursive($expected, $indexed_date_range, $diff);
        
        $this->assertTrue(empty($diff));
    }
    
    /**
     * Leap year
     */
    public function testDateRangeLeapYear() {
        $date_range = new DateRange(mongoDate('2/27/2012'), mongoDate('3/1/2012'));
        
        $expected = array(
            2012=>array(
                2=>array(
                    9=>array(
                        mongoDate('2/27/2012'),
                        mongoDate('2/28/2012'),
                        mongoDate('2/29/2012'),
                    )
                ),
                3=>array(
                    9=>array(
                        mongoDate('3/1/2012')
                    )
                )
            )
        );
       
        $indexed_date_range = $date_range->getIndexedDateRange();
        $diff = array();
        arrayDiffAssocRecursive($expected, $indexed_date_range, $diff);
        
        $this->assertTrue(empty($diff));
    }

    /**
     * Billable date range
     */
    public function testBillableDateRange() {
        $date_range = DateRange::getBillableWeekForDate(mongoDate('8/17/2012'));

        $expected = array(
            2012 => array(
                8 => array(
                    33 => array(
                        mongoDate('8/16/2012'),
                        mongoDate('8/17/2012'),
                        mongoDate('8/18/2012'),
                        mongoDate('8/19/2012'),
                    )
                )
            )
        );

        $indexed_date_range = $date_range->getIndexedDateRange();
        $diff = array();
        arrayDiffAssocRecursive($expected, $indexed_date_range, $diff);
        
        $this->assertTrue(empty($diff));
    }

    /**
     * Billable date range
     */
    public function testBillableDateRange2() {
        $date_range = DateRange::getBillableWeekForDate(mongoDate('8/14/2012'));

        $expected = array(
            2012 => array(
                8 => array(
                    33 => array(
                        mongoDate('8/13/2012'),
                        mongoDate('8/14/2012'),
                        mongoDate('8/15/2012'),
                    )
                )
            )
        );

        $indexed_date_range = $date_range->getIndexedDateRange();
        $diff = array();
        arrayDiffAssocRecursive($expected, $indexed_date_range, $diff);
        
        $this->assertTrue(empty($diff));
    }

    /**
     * Is this the weekend
     */
    public function testIsWeekend() {
        $date = mongoDate('8/18/2012');
        $isWeekend = isWeekend( $date );
        $expected = true;

        $this->assertEquals( $isWeekend, $expected );
    }

    /**
     * Is this the weekend
     */
    public function testIsWeekend2() {
        $date = mongoDate('12/15/2012');
        $isWeekend = isWeekend( $date );
        $expected = true;

        $this->assertEquals( $isWeekend, $expected );
    }

    /**
     * Is this the weekend
     */
    public function testIsWeekend3() {
        $date = mongoDate('2/21/2011');
        $isWeekend = isWeekend( $date );
        $expected = false;

        $this->assertEquals( $expected, $isWeekend );
    }

    /*
     * An ISO non-leap year should contain 52 ISO weeks
     */
    public function testGetISOYearNonLeapYear() {
        // 2012 is not an ISO leap year
        $year = DateRange::getISOYearForDate( '5/5/2012' );
        $expected = array(
            2012 => array(
                1 => array(
                    1 => array(
                        mongoDate('1/2/2012'),
                        mongoDate('1/3/2012'),
                        mongoDate('1/4/2012'),
                        mongoDate('1/5/2012'),
                        mongoDate('1/6/2012'),
                        mongoDate('1/7/2012'),
                        mongoDate('1/8/2012'),
                    )
                ),
                12 => array(
                    52 => array(
                        mongoDate('12/24/2012'),
                        mongoDate('12/25/2012'),
                        mongoDate('12/26/2012'),
                        mongoDate('12/27/2012'),
                        mongoDate('12/28/2012'),
                        mongoDate('12/29/2012'),
                        mongoDate('12/30/2012'),
                    )
                )
            )
        );

        $indexed_date_range = $year->getIndexedDateRange();
        $diff = array();
        arrayDiffAssocRecursive($expected, $indexed_date_range, $diff);

        $this->assertTrue(empty($diff));
    }

    /*
     * An ISO leap year should contain 53 ISO weeks
     */
    public function testGetISOYearLeapYear() {
        // 2009 is a known ISO leap year
        $year = DateRange::getISOYearForDate( '5/5/2009' );
        $expected = array(
            2008 => array(
                12 => array(
                    1 => array(
                        mongoDate('12/29/2008'),
                        mongoDate('12/30/2008'),
                        mongoDate('12/31/2008'),
                    )
                )
            ),
            2009 => array(
                1 => array(
                    1 => array(
                        mongoDate('1/1/2009'),
                        mongoDate('1/2/2009'),
                        mongoDate('1/3/2009'),
                        mongoDate('1/4/2009'),
                    )
                ),
                12 => array(
                    53 => array(
                        mongoDate('12/28/2009'),
                        mongoDate('12/29/2009'),
                        mongoDate('12/30/2009'),
                        mongoDate('12/31/2009'),
                    )
                )
            ),
            2010 => array(
                1 => array(
                    53 => array(
                        mongoDate('1/1/2010'),
                        mongoDate('1/2/2010'),
                        mongoDate('1/3/2010'),
                    )
                )
            )
        );

        $indexed_date_range = $year->getIndexedDateRange();
        $diff = array();
        arrayDiffAssocRecursive($expected, $indexed_date_range, $diff);

        $this->assertTrue(empty($diff));
    }

    public function testGetBillingPeriodOddYear() {
        $date_range = DateRange::getCurrentBillingPeriod( '5/17/2012' );
        $expected = array(
            2012 => array(
                5 => array(
                    19 => array(
                        mongoDate('5/7/2012'),
                        mongoDate('5/8/2012'),
                        mongoDate('5/9/2012'),
                        mongoDate('5/10/2012'),
                        mongoDate('5/11/2012'),
                        mongoDate('5/12/2012'),
                        mongoDate('5/13/2012'),
                    ),
                    20 => array(
                        mongoDate('5/14/2012'),
                        mongoDate('5/15/2012'),
                        mongoDate('5/16/2012'),
                        mongoDate('5/17/2012'),
                        mongoDate('5/18/2012'),
                        mongoDate('5/19/2012'),
                        mongoDate('5/20/2012'),
                    )
                )
            )
        );

        $indexed_date_range = $date_range->getIndexedDateRange();
        $diff = array();
        arrayDiffAssocRecursive($expected, $indexed_date_range, $diff);

        $this->assertTrue(empty($diff));
    }

    /**
     * Test the bridge from a leap year to an even year
     */
    public function testGetBillingPeriodOddToEvenYear() {
        $date_range = DateRange::getCurrentBillingPeriod( '12/31/2015' );

        $expected = array(
            2015 => array(
                12 => array(
                    53 => array(
                        mongoDate('12/28/2015'),
                        mongoDate('12/29/2015'),
                        mongoDate('12/30/2015'),
                        mongoDate('12/31/2015'),
                    ),
                ),
            ),
            2016 => array(
                1 => array(
                    53 => array(
                        mongoDate('1/1/2016'),
                        mongoDate('1/2/2016'),
                        mongoDate('1/3/2016'),
                    ),
                    1 => array(
                        mongoDate('1/4/2016'),
                        mongoDate('1/5/2016'),
                        mongoDate('1/6/2016'),
                        mongoDate('1/7/2016'),
                        mongoDate('1/8/2016'),
                        mongoDate('1/9/2016'),
                        mongoDate('1/10/2016'),
                    )
                )
            )
        );

        $indexed_date_range = $date_range->getIndexedDateRange();
        $diff = array();
        arrayDiffAssocRecursive($expected, $indexed_date_range, $diff);

        $this->assertTrue(empty($diff));
    }

    public function testGetBillingPeriodEvenYear() {
        $date_range = DateRange::getCurrentBillingPeriod( '1/1/2017' );

        $expected = array(
            2016 => array(
                12 => array(
                    52 => array(
                        mongoDate('12/26/2016'),
                        mongoDate('12/27/2016'),
                        mongoDate('12/28/2016'),
                        mongoDate('12/29/2016'),
                        mongoDate('12/30/2016'),
                        mongoDate('12/31/2016'),
                    ),
                ),
            ),
            2017 => array(
                1 => array(
                    52 => array(
                        mongoDate('1/1/2017'),
                    ),
                    1 => array (
                        mongoDate('1/2/2017'),
                        mongoDate('1/3/2017'),
                        mongoDate('1/4/2017'),
                        mongoDate('1/5/2017'),
                        mongoDate('1/6/2017'),
                        mongoDate('1/7/2017'),
                        mongoDate('1/8/2017'),
                    ),
                ),
            ),
        );

        $indexed_date_range = $date_range->getIndexedDateRange();
        $diff = array();
        arrayDiffAssocRecursive($expected, $indexed_date_range, $diff);

        $this->assertTrue(empty($diff));
    }
}

function arrayDiffAssocRecursive($array1, $array2, &$results) {
    /** @var $value mixed */
    if (is_array($array1)) {
        foreach($array1 as $key => $value) {
            if(is_array($value) && is_array($array2[$key])) {
                arrayDiffAssocRecursive($value, $array2[$key], $results);
            } else if(is_object($value) && is_object($array2[$key])) {
                /** @var MongoId $value */
                if($value->__toString() !== $array2[$key]->__toString()) {
                    $results[$key] = $value;
                }
            }
            else if($array1[$key] !== $array2[$key]) {
                $results[$key] = $value;
            }
        }
    }
}
