<?php
class calendar extends controller
{
    function action_add_event($date)
    {
        $event = $this->loadModel( 'event' );
        $errorMsg='';
        if( count( $_POST ) )
        {
            $post = $_POST;
            if (!($errorMsg = $this->_errorMsg($post, $event))) {
                $res = $event->add_event($post);
                if ($res)
                    $this->redirect('calendar/index/' . date('Y-m', $post['day_stamp']));
            }
        }

        $date_info = $this->_getDateInfo($date);
        $this->loadView('calendar/event',
            array('date_info'=>$date_info, 'event_info'=>null, 'event_status'=>$event->get_status(), 'errorMsg'=>$errorMsg));
    }

    function action_edit_event($id)
    {
        $event = $this->loadModel( 'event' );
        $errorMsg = '';
        if( count( $_POST ) )
        {
            $post = $_POST;
            if (!($errorMsg = $this->_errorMsg($post, $event))) {
                $event->update_event($id, $post);
                $this->redirect('calendar/index/' . date('Y-m', $post['day_stamp']));
            }
        }

        if ($event_info = $event->get_event($id)) {
            $event_info['time_start'] = date('H:i', $event_info['t_start']);
            $event_info['time_end'] = date('H:i', $event_info['t_end']);
            $date_info = $this->_getDateInfo(date('Y-m-d', $event_info['t_start']));
            $this->loadView('calendar/event',
                array('date_info' => $date_info, 'event_info' => $event_info, 'event_status' => $event->get_status(), 'errorMsg' => $errorMsg));
        }
        else
            $this->redirect('calendar/index');
    }

    function action_index($date)
	{
        $data = $this->_getCalendar($date);
        $event = $this->loadModel( 'event' );
        $data['events'] = $event->get_events($data['start_stamp'], $data['end_stamp']);
        $this->loadView( 'calendar/index', $data );
	}

    private function _errorMsg($post, $event)
    {
        if ($post['t_start'] >= $post['t_end'])
            return 'Start time must be less than the end time.';
        elseif (strlen($post['title']) == 0)
            return 'Title must be non empty';
        elseif ($event->exist_event($post))
            return 'There is an event in a given period of time.';

        return false;
    }


    private function _getDateInfo($date)
    {
        if (!empty($date) && strstr($date,"-")) {
            if (($count = substr_count($date,'-'))==2)
                list($year, $month, $day)  = explode("-", $date);
            else {
                list($year, $month) = explode("-", $date);
            }
        }

        if (!isset($year) OR !is_numeric($year) OR $year < 1970 OR $year > 2037) $year=date("Y");
        if (!isset($month) OR !is_numeric($month) OR $month < 1 OR $month > 12) $month=date("m");
        if (!isset($day) OR !is_numeric($day) OR !checkdate($month, $day, $year)) {
            $day = 1;
        }

        $start_stamp = mktime(0,0,0,$month,1,$year);
        $month_name = date("F",$start_stamp);
        $day_stamp = mktime(0,0,0,$month,$day,$year);

        $data = array(
            'date'=>"$year-$month-$day",
            'month_name'=>$month_name,
            'year'=>$year,
            'day'=>$day,
            'day_stamp'=>$day_stamp,
        );
        return $data;
    }

    private function _getCalendar($date)
    {
        if (!empty($date) && strstr($date,"-")) {
            list($year, $month)  = explode("-", $date);
        }

        if (!isset($year) OR $year < 1970 OR $year > 2037) $year=date("Y");
        if (!isset($month) OR $month < 1 OR $month > 12) $month=date("m");

        $start_stamp=mktime(0,0,0,$month,1,$year);
        $month_name = date("F",$start_stamp);
        $day_count=date("t",$start_stamp);
        $end_stamp = mktime(0,0,-1,$month+1,1,$year);
        $weekday=date("w",$start_stamp);
        if ($weekday==0) $weekday=7;
        $start=-($weekday-2);
        $last=($day_count+$weekday-1) % 7;
        if ($last==0)
            $end=$day_count;
        else
            $end=$day_count+7-$last;

        $prev_month='?route=calendar/index/'.date('Y-m', mktime(0,0,0,$month-1,1,$year));
        $next_month='?route=calendar/index/'.date('Y-m', mktime(0,0,0,$month+1,1,$year));
        $add_link='?route=calendar/add_event/'.$year.'-'.$month;
        $edit_link='?route=calendar/edit_event/';

        $data = array(
            'daysOfWeek' => array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
            'start_stamp'=>$start_stamp,
            'end_stamp'=>$end_stamp,
            'month_name'=>$month_name,
            'day_count'=>$day_count,
            'year'=>$year,
            'month'=>$month,
            'prev_month'=>$prev_month,
            'next_month'=>$next_month,
            'add_link'=>$add_link,
            'edit_link'=>$edit_link,
            'start'=>$start,
            'end'=>$end,
        );
        return $data;
    }
}
