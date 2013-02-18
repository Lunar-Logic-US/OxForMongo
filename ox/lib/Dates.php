<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wil
 * Date: 9/6/12
 * Time: 12:12 PM
 * To change this template use File | Settings | File Templates.
 */

function createDate( $p_dateTime, $p_locationDoc = 0 )
{
    if ($p_dateTime === null || $p_dateTime ==='' ) {
        return null;
    }

    if( is_numeric( $p_dateTime) ){
        $dateTimeString = "@" . $p_dateTime;
    } else if (is_string( $p_dateTime)) {
        $dateTimeString = $p_dateTime;
    } else {
        $dateTimeString = $p_dateTime;
    }

    $dateTime = new DateTime( $dateTimeString, getActiveTimeZone($p_locationDoc ));
    return new MongoDate( $dateTime->getTimestamp() );
}

function localizedDate( $p_mongoDate, $p_locationDoc = 0 ) {
    return formattedDate($p_mongoDate, 'm/d/Y', $p_locationDoc);
}

function localizedDateTime( $p_mongoDate, $p_locationDoc = 0 ) {
    return formattedDate($p_mongoDate, 'm/d/Y h:i:sa', $p_locationDoc);
}

function formattedDate( $p_mongoDate, $format='', $p_locationDoc = 0 ) {
    if(is_null($p_mongoDate)) {
        return '';
    }

    if (!$format) {
        $format = 'm/d/Y';
    }

    $dateTime = new DateTime( "@" . utc($p_mongoDate), new DateTimeZone( 'UTC' ));
    $dateTime->setTimezone(getActiveTimeZone($p_locationDoc));

    return $dateTime->format($format);
}

function utc( $p_mongoDate ) {
    return mongoDate($p_mongoDate)->sec;
}

function timeIntTo24HourPadded( $time ) {
    $time = (string) $time;
    switch(strlen($time)) {
        case 3:
            return '0' . $time;
        case 2:
            return '00' . $time;
        case 1:
            return '000' . $time;
        default:
            return $time;
    }
}

function mongoDate($p_dateTime, $p_locationDoc = 0) {
    if($p_dateTime instanceof MongoDate) {
        return $p_dateTime;
    }

    if($p_dateTime === null || $p_dateTime ==='') {
        return null;
    }

    if(is_numeric($p_dateTime)){
        $dateTimeString = "@" . $p_dateTime;
    } else if(is_string( $p_dateTime)) {
        $expl_str = explode("(", $p_dateTime);
        $dateTimeString = $expl_str[0];
    } else {
        $dateTimeString = $p_dateTime;
    }

    try {
        $dateTime = new DateTime($dateTimeString, getActiveTimeZone($p_locationDoc));
    } catch (Exception $e) {
        global $logger;
        Ox_Logger::logError("Error parsing date/time:");
        Ox_Logger::logError($dateTimeString);
        Ox_Logger::logError($e);
        throw $e;
    }
    return new MongoDate($dateTime->getTimestamp());
}

function sameDateAndTime($p_date1, $p_date2) {
    return utc($p_date1) == utc($p_date2);
}

function earliestDate($p_datesArray, $p_notLessThanEq = null) {
    $earliest = null;
    if(isset($p_datesArray) && is_array($p_datesArray)) {
        foreach($p_datesArray as $date) {
            if(is_null($earliest) || dateLessThan($date, $earliest)) {
                if($p_notLessThanEq == null || dateLessThanEq($p_notLessThanEq, $date)) {
                    $earliest = $date;
                }
            }
        }
    }
    if (is_null($earliest)) {
        throw new Exception('Null date passed to earliestDate.  (Bad data?)');
    }

    return $earliest;
}

function dateLessThan( $p_date1, $p_date2 ) {
    return utc($p_date1) < utc($p_date2);
}

function dateLessThanEq( $p_date1, $p_date2 ) {
    return utc($p_date1) <= utc($p_date2);
}

function dateGreaterThanEq( $p_date1, $p_date2 ) {
    return utc($p_date1) >= utc($p_date2);
}

function isWeekend( $p_date ) {
    if(intval(formattedDate(mongoDate($p_date), 'N' )) > 5) {
        return true;
    }
    return false;
}

function getActiveTimeZone( $p_locationDoc = 0 ) {
    global $global_time_zone;
    global $config_parser;

    if (!isset($p_locationDoc['time_zone']) || !$p_locationDoc) {
        if ($global_time_zone) {
            $timeZoneString = $global_time_zone;
        } else {
            $timeZoneString = $config_parser->getAppConfigValue('default_time_zone');
        }
    } else {
        $timeZoneString = $p_locationDoc['time_zone'];
    }

    $timeZone = new DateTimeZone($timeZoneString);
    return $timeZone;
}

function isFuture( $p_date ) {
    return dateLessThan( mongoDate('today'), $p_date );
}

function isDateLocked($p_date) {
    global $db;
    
    $date_fence = $db->vars->findOne(array('_id'=>'id_date_fence'));
    if(!$date_fence) {
        return false;
    }
    
    return dateLessThanEq($p_date, $date_fence['value']);
}

class DateRange {
    
    private $indexed_date_range = null;
    const ONE_DAY = 86400;
    const SPLIT_DAY = 15;
    
    public function __construct($p_start_date = null, $p_end_date = null) {
        
        if(is_null($p_start_date) && is_null($p_end_date)) {
            $today = mongoDate('today');

            $today_exploded = DateRange::explodeDate($today);
            $last_day_month_date = mongoDate(mktime(0, 0, 0, $today_exploded['month']+1, 0, $today_exploded['year']));
            $first_day_month_date = DateRange::getFirstDayOfMonth($today);
            $split_day_date = mongoDate(mktime(0, 0, 0, $today_exploded['month'], DateRange::SPLIT_DAY, $today_exploded['year']));
            
            if($today_exploded['day'] <= DateRange::SPLIT_DAY) {
                $this->buildDateRange($first_day_month_date, $split_day_date);
            } else {
                $split_day_date = mongoDate(mktime(0, 0, 0, $today_exploded['month'], DateRange::SPLIT_DAY + 1, $today_exploded['year']));
                $this->buildDateRange($split_day_date, $last_day_month_date);
            }
        } else if($this->validDateRange($p_start_date, $p_end_date)) {
            $this->buildDateRange($p_start_date, $p_end_date);
        } else {
            if( is_null($p_start_date) ) {
                $this->buildDateRange($p_end_date, $p_end_date);
            } else {
                $this->buildDateRange($p_start_date, $p_start_date);
            }
        }
    }
    
    public function getFirstDay() {
        foreach($this->indexed_date_range as $year => $months) {
            foreach($months as $month => $weeks) {               
                foreach($weeks as $week => $days) {
                    foreach($days as $day) {
                        return $day;
                    }
                }
            }
        }
        
        return null;
    }
    
    public function getLastDay() {
        $last_day = null;
         foreach($this->indexed_date_range as $year => $months) {
            foreach($months as $month => $weeks) {               
                foreach($weeks as $week => $days) {
                    foreach($days as $day) {
                       $last_day = $day;
                    }
                }
            }
        }
        
        return $last_day;
    }

    public static function getNextWeek($p_date) {
        $thisWeek = DateRange::getISOWeekForDate($p_date);
        $endExpl= DateRange::explodeDate( $thisWeek->getLastDay());
        $nextDay = mongoDate(mktime(0, 0, 0, $endExpl['month'], $endExpl['day']+1, $endExpl['year']));
        return DateRange::getISOWeekForDate( $nextDay );
    }

    public static function getLastWeek($p_date) {
        $thisWeek = DateRange::getISOWeekForDate($p_date);
        $endExpl= DateRange::explodeDate( $thisWeek->getFirstDay());
        $lastDay = mongoDate(mktime(0, 0, 0, $endExpl['month'], $endExpl['day']-1, $endExpl['year']));
        return DateRange::getISOWeekForDate( $lastDay );
    }

    public static function getNextPayPeriod($p_date) {
        $curPayPeriod = DateRange::getBillablePeriodForDate($p_date);
        $curLastDay = $curPayPeriod->getLastDay();
        $explRefDate = DateRange::explodeDate($curLastDay);
        $refDate = mongoDate(mktime(0, 0, 0, $explRefDate['month'], $explRefDate['day']+1, $explRefDate['year']));

        $ref_date_exploded = DateRange::explodeDate($refDate);
        $last_day_month_date = mongoDate(mktime(0, 0, 0, $ref_date_exploded['month']+1, 0, $ref_date_exploded['year']));
        $first_day_month_date = DateRange::getFirstDayOfMonth($refDate);
        $split_day_date = mongoDate(mktime(0, 0, 0, $ref_date_exploded['month'], DateRange::SPLIT_DAY, $ref_date_exploded['year']));

        if($ref_date_exploded['day'] <= DateRange::SPLIT_DAY) {
            return new DateRange( $first_day_month_date, $split_day_date );
        } else {
            $split_day_date = mongoDate(mktime(0, 0, 0, $ref_date_exploded['month'], DateRange::SPLIT_DAY + 1, $ref_date_exploded['year']));
            return new DateRange( $split_day_date, $last_day_month_date );
        }
    }

    public static function getLastPayPeriod($p_date) {
        $curPayPeriod = DateRange::getBillablePeriodForDate($p_date);
        $curStartDay = $curPayPeriod->getFirstDay();
        $explRefDate = DateRange::explodeDate($curStartDay);
        $refDate = mongoDate(mktime(0, 0, 0, $explRefDate['month'], $explRefDate['day']-1, $explRefDate['year']));

        $ref_date_exploded = DateRange::explodeDate($refDate);
        $last_day_month_date = mongoDate(mktime(0, 0, 0, $ref_date_exploded['month']+1, 0, $ref_date_exploded['year']));
        $first_day_month_date = DateRange::getFirstDayOfMonth($refDate);
        $split_day_date = mongoDate(mktime(0, 0, 0, $ref_date_exploded['month'], DateRange::SPLIT_DAY, $ref_date_exploded['year']));

        if($ref_date_exploded['day'] <= DateRange::SPLIT_DAY) {
            return new DateRange( $first_day_month_date, $split_day_date );
        } else {
            $split_day_date = mongoDate(mktime(0, 0, 0, $ref_date_exploded['month'], DateRange::SPLIT_DAY + 1, $ref_date_exploded['year']));
            return new DateRange( $split_day_date, $last_day_month_date );
        }
    }

    public static function getNextBillingPeriod($p_date) {
        $last = DateRange::getCurrentBillingPeriod($p_date);
        $expl_date = DateRange::explodeDate($last->getLastDay());
        $next_day = mongoDate(mktime(0, 0, 0, $expl_date['month'], $expl_date['day']+1, $expl_date['year']));
        return DateRange::getCurrentBillingPeriod($next_day);
    }

    public static function getCurrentBillingPeriod($p_date) {
        $cur_week = DateRange::getISOWeekForDate($p_date);
        $expl_ref_date = DateRange::explodeDate($cur_week->getFirstDay());
        $expl_ref_date_2 = DateRange::explodeDate($cur_week->getLastDay());
        $week_num = $expl_ref_date['week'];
        $ref_date = ($expl_ref_date['year'] < $expl_ref_date_2['year'])?$cur_week->getLastDay():$cur_week->getFirstDay();

        $retval = null;
        if( DateRange::isOddBillingYear($ref_date) ) {
            // billing periods start on odd weeks
            if( $week_num % 2 ) {
                // starting week
                $expl_ref_date = DateRange::explodeDate($cur_week->getLastDay());
                $next_day = mongoDate(mktime(0, 0, 0, $expl_ref_date['month'], $expl_ref_date['day']+1, $expl_ref_date['year']));
                $week_2 = DateRange::getISOWeekForDate($next_day);
                $retval = new DateRange( $cur_week->getFirstDay(), $week_2->getLastDay() );
            } else {
                // ending week
                $expl_ref_date = DateRange::explodeDate($cur_week->getFirstDay());
                $next_day = mongoDate(mktime(0, 0, 0, $expl_ref_date['month'], $expl_ref_date['day']-1, $expl_ref_date['year']));
                $week_2 = DateRange::getISOWeekForDate($next_day);
                $retval = new DateRange( $week_2->getFirstDay(), $cur_week->getLastDay() );
            }
        } else {
            // billing periods start on even weeks, except on week 53 during the transition
            // from an odd year to an even year.
            if( $week_num % 2 && !( $week_num == 53 )) {
                // ending week
                $expl_ref_date = DateRange::explodeDate($cur_week->getFirstDay());
                $next_day = mongoDate(mktime(0, 0, 0, $expl_ref_date['month'], $expl_ref_date['day']-1, $expl_ref_date['year']));
                $week_2 = DateRange::getISOWeekForDate($next_day);
                $retval = new DateRange( $week_2->getFirstDay(), $cur_week->getLastDay() );
            } else {
                // starting week
                $expl_ref_date = DateRange::explodeDate($cur_week->getLastDay());
                $next_day = mongoDate(mktime(0, 0, 0, $expl_ref_date['month'], $expl_ref_date['day']+1, $expl_ref_date['year']));
                $week_2 = DateRange::getISOWeekForDate($next_day);
                $retval = new DateRange( $cur_week->getFirstDay(), $week_2->getLastDay() );
            }
        }
        return $retval;
    }

    public static function getLastBillingPeriod($p_date) {
        $next = DateRange::getCurrentBillingPeriod($p_date);
        $expl_date = DateRange::explodeDate($next->getFirstDay());
        $next_day = mongoDate(mktime(0, 0, 0, $expl_date['month'], $expl_date['day']-1, $expl_date['year']));
        return DateRange::getCurrentBillingPeriod($next_day);
    }

    public static function getISOWeekForDate($p_date) {
        $exploded_date = DateRange::explodeDate($p_date);
        $tmp_date = $p_date;
        $tmp_exploded_date = $exploded_date;
        
        // Walk backwards to find the first day of the week
        while($tmp_exploded_date['week'] == $exploded_date['week']) {
            $tmp_date = mongoDate(mktime(0, 0, 0, $tmp_exploded_date['month'], $tmp_exploded_date['day']-1, $tmp_exploded_date['year']));
            $tmp_exploded_date = DateRange::explodeDate($tmp_date);
        }
        
        $start_date = $tmp_date = mongoDate(mktime(0, 0, 0, $tmp_exploded_date['month'], $tmp_exploded_date['day']+1, $tmp_exploded_date['year']));
        $start_date_exploded = DateRange::explodeDate($start_date);
        
        // Reset the tmp variables
        $tmp_date = $p_date;
        $tmp_exploded_date = $exploded_date;
        
        // Walk forward to the end of the week
        while($tmp_exploded_date['week'] == $exploded_date['week']) {
            $tmp_date = mongoDate(mktime(0, 0, 0, $tmp_exploded_date['month'], $tmp_exploded_date['day']+1, $tmp_exploded_date['year']));
            $tmp_exploded_date = DateRange::explodeDate($tmp_date);
        }
        
        $end_date = $tmp_date = mongoDate(mktime(0, 0, 0, $tmp_exploded_date['month'], $tmp_exploded_date['day']-1, $tmp_exploded_date['year']));
        $end_date_exploded = DateRange::explodeDate($start_date);
        
        return new DateRange($start_date, $end_date);
    }

    public static function getYearForDate($p_date) {
        $expl_date = DateRange::explodeDate($p_date);
        $start_date = mongoDate(mktime(0, 0, 0, 1, 1, $expl_date['year']));
        $end_date = mongoDate(mktime(0, 0, 0, 12, 31, $expl_date['year']));
        return new DateRange($start_date, $end_date);
    }

    public static function getISOYearForDate($p_date) {
        $ref_date = DateRange::explodeDate($p_date);
        // get our current Gregorian year
        $faux_year = DateRange::getYearForDate($p_date);

        // figure out what year the first day belongs to.
        $week_1 = DateRange::getISOWeekForDate($faux_year->getFirstDay());
        $expl_date = DateRange::explodeDate($week_1->getLastDay());
        if( $expl_date['week'] != 1 ) {
            // the first of the year belongs to last year
            $week_1 = DateRange::getISOWeekForDate(mongoDate(mktime(0, 0, 0, $expl_date['month'], $expl_date['day']+1, $expl_date['year'])));
        }

        // figure out what year the last day belongs to
        $week_last = DateRange::getISOWeekForDate($faux_year->getLastDay());
        $expl_date = DateRange::explodeDate($week_last->getFirstDay());
        if( $expl_date['week'] < 52 ) {
            // the first of the year belongs to last year
            $week_last = DateRange::getISOWeekForDate(mongoDate(mktime(0, 0, 0, $expl_date['month'], $expl_date['day']-1, $expl_date['year'])));
        }

        return new DateRange( $week_1->getFirstDay(), $week_last->getLastDay() );
    }

    public static function getBillablePeriodForDate($p_date) {
        $refDate = mongoDate($p_date);

        $ref_date_exploded = DateRange::explodeDate($refDate);
        $last_day_month_date = mongoDate(mktime(0, 0, 0, $ref_date_exploded['month']+1, 0, $ref_date_exploded['year']));
        $first_day_month_date = DateRange::getFirstDayOfMonth($refDate);
        $split_day_date = mongoDate(mktime(0, 0, 0, $ref_date_exploded['month'], DateRange::SPLIT_DAY, $ref_date_exploded['year']));

        if($ref_date_exploded['day'] <= DateRange::SPLIT_DAY) {
            return new DateRange( $first_day_month_date, $split_day_date );
        } else {
            $split_day_date = mongoDate(mktime(0, 0, 0, $ref_date_exploded['month'], DateRange::SPLIT_DAY + 1, $ref_date_exploded['year']));
            return new DateRange( $split_day_date, $last_day_month_date );
        }
    }

    /**
     * This function returns the billable portion of the week for the specified
     * date.
     */
    public static function getBillableWeekForDate($p_date) {
        $exploded_date = DateRange::explodeDate($p_date);

        // figure out what pay period we're in
        if( $exploded_date['day'] > 0 && $exploded_date['day'] < DateRange::SPLIT_DAY + 1 ) {
            // first pay period of the month
            $innerbound = 1;
            $outerbound = DateRange::SPLIT_DAY;
        } else {
            // second pay period of the month
            $innerbound = DateRange::SPLIT_DAY + 1;
            $last_day = DateRange::explodeDate(DateRange::getLastDayOfMonth( $p_date ));
            $outerbound = $last_day['day'];
        }

        // initialize our date range from the current ISO week
        $InitialRange = DateRange::getISOWeekForDate( $p_date )->getIndexedDateRange();
        $CurrentWeek = $InitialRange[$exploded_date['year']][$exploded_date['month']][$exploded_date['week']];

        $finalrange = array();
        foreach( $CurrentWeek as $check_date ) {
            $exp_date = DateRange::explodeDate( $check_date );

            // Ensure the date is within the billable period
            if( $exp_date['day'] >= $innerbound && $exp_date['day'] <= $outerbound ) {
                $finalrange[] = $check_date;
            }
        }

        // return the final date range
        if( count( $finalrange ) < 1 ) { return null; }

        $first = array_shift( $finalrange );
        if( count( $finalrange ) < 1 ) {
            $last = $first;
        } else {
            $last = array_pop( $finalrange );
        }
        return new DateRange( $first, $last );
    }

    public function getIndexedDateRange() {
        return $this->indexed_date_range;
    }

    private function getLastDayOfMonth( $date )
    {
        $exploded_date = DateRange::explodeDate( $date );
        return mongoDate(mktime(0, 0, 0, $exploded_date['month']+1, 0, $exploded_date['year']));
    }

    private function getFirstDayOfMonth( $date )
    {
        $exploded_date = DateRange::explodeDate( $date );
        return mongoDate(mktime(0, 0, 0, $exploded_date['month'], 1, $exploded_date['year']));
    }

    private function addToRange($day, $exploded_date) {
        $current_date = mongoDate(mktime(0, 0, 0, $exploded_date['month'], $day, $exploded_date['year']));
        $cde = DateRange::explodeDate($current_date);
        $this->indexed_date_range[$cde['year']][$cde['month']][$cde['week']][] = $current_date;
    }
    
    private function buildDateRange($p_start_date, $p_end_date) {
        $start_date_secs = $p_start_date->sec;
        $calc_end_date_secs = $p_end_date->sec; 
        $date_secs = $start_date_secs;
        $calc_date = $p_start_date;
        
        while($calc_date->sec <= $calc_end_date_secs) {
            $this->addDateToRange($calc_date);
            // Advance one day
            $calc_date_exploded = DateRange::explodeDate($calc_date);
            $calc_date = mongoDate(mktime(0, 0, 0, $calc_date_exploded['month'], $calc_date_exploded['day']+1, $calc_date_exploded['year']));
        }
    }
    
    private function addDateToRange($date) {
        $exploded_date = DateRange::explodeDate($date);
        $ed = $exploded_date;
        $this->indexed_date_range[$ed['year']][$ed['month']][$ed['week']][] = $date;
    }
    
    private function validDateRange($p_start_date, $p_end_date) {
        if(is_null($p_start_date) || is_null($p_end_date)) {
            return false;
        }
        
        if($p_start_date->sec > $p_end_date->sec) {
            return false;
        }
        
        return true;
        
    }
    
    public static function explodeDate($date) {
        return array(
            'day'=>intval(formattedDate( $date, 'j')),
            'week'=>intval(formattedDate( $date, 'W')),
            'month'=>intval(formattedDate( $date, 'n')),
            'year'=>intval(formattedDate( $date, 'Y'))
        );
    }
    
    public function debugPrint() {
        if(is_null($this->indexed_date_range)) {
            print "\n[EMPTY]\n";
            return;
        }
        foreach($this->indexed_date_range as $year => $months) {
            print "\n" . $year . "\n";
            foreach($months as $month => $weeks) {
                print "    +---" . $month . "\n";
                foreach($weeks as $week => $days) {
                    print "        +---" . $week . "\n";
                    print "            +---";
                    foreach($days as $day) {
                        print "(" . formattedDate( $day, 'D') . " " . $month . "/" . formattedDate( $day, 'j') . ") ";
                    }
                    print "\n";
                }
            }
        }
    }

    /*
     * This function concerns itself with ISO years from 2009 until 2043.  Years outside that
     * date range are not supported
     */
    public static function isOddBillingYear($p_date) {
        $expl_date = DateRange::explodeDate($p_date);
        if( $expl_date['year'] > 2037 ) {
            return false;
        }
        if( $expl_date['year'] > 2032 ) {
            return true;
        }
        if( $expl_date['year'] > 2026 ) {
            return false;
        }
        if( $expl_date['year'] > 2020 ) {
            return true;
        }
        if( $expl_date['year'] > 2015 ) {
            return false;
        }
        return true;
    }
}