<?php
class uiScheduler extends uiCalendar
{
    function uiScheduler(&$uiBase)
    {
        $this->curr   =& $_SESSION[UI_CALENDAR_SESSNAME]['current'];
        if (!is_array($this->curr)) {
            $this->curr['view']     = 'month';
            $this->curr['year']     = date("Y");
            $this->curr['month']    = date("m");
            $this->curr['day']      = date('d');
        }

        $this->Base =& $uiBase;
        $this->reloadUrl = UI_BROWSER.'?popup[]=_reload_parent&popup[]=_close';

        $this->uiCalendar();
        $this->initXmlRpc();
    }


    function setReload()
    {
        $this->Base->redirUrl = $this->reloadUrl;
    }

    function set($arr)
    {
        extract($arr);
        if ($view)  $this->curr['view'] = $view;
        if ($year)  $this->curr['year'] = $year;
        if (is_numeric($month)) $this->curr['month'] = $month;
        if ($month=='++') {
            if ($this->curr['month']==12) {
                $this->curr['month'] = '01';
                $this->curr['year']++;
            } else {
                $this->curr['month'] = $this->Base->_twoDigits(++$this->curr['month']);
            }
        }
        if ($month=='--') {
            if ($this->curr['month']=='01') {
                $this->curr['month'] = 12;
                $this->curr['year']--;
            } else {
                 $this->curr['month'] = $this->Base->_twoDigits(--$this->curr['month']);
            }
        }
        if ($day)   $this->curr['day']  = $day;
        if ($hour)  $this->curr['hour'] = $hour;
    }


    function _datetime2timestamp($i)
    {
        $formatted = $i[0].$i[1].$i[2].$i[3].'-'.$i[4].$i[5].'-'.$i[6].$i[7].strrchr($i, 'T');
        #echo $formatted;
        return strtotime($formatted);
    }


    function getDayUsage($year, $month, $day)
    {
        $date = $year.$month.$day;
        $arr = $this->displayScheduleMethod($date.'T00:00:00', $date.'T23:59:59.999999');
        if (!count($arr))
            return FALSE;
        #print_r($arr);
        return $arr;
    }

    function getDayUsagePercentage($year, $month, $day)
        {
        #echo "date: ".$year.$month.$day."<br>";
        if (isset($this->_duration[$year.$month.$day]))
            return $this->_duration[$year.$month.$day];

        $this->_duration[$year.$month.$day] = 0;
        if (!$arr = $this->getDayUsage($year, $month, $day))
            return false;

        foreach ($arr as $val) {
            #print_r($val);
            $this->_duration[$year.$month.$day] += ($this->_datetime2timestamp($val['end'])-$this->_datetime2timestamp($val['start']))/86400*100;
        }
        #echo "duration: ".$this->_duration[$year.$month.$day]."<br>";
        return $this->_duration[$year.$month.$day];
    }

    function getPeriodicUsage($start, $end)
    {

    }

    function copyPlFromSP()
    {
        foreach ($this->Base->SCRATCHPAD->get() as $val) {
            if (strtolower($val['type'])=='playlist' && $val['id']!=$this->Base->PLAYLIST->activeId)
                $this->playlists[] = $val;
        }
    }

    ## XML-RPC methods ############################################################################################
    function initXmlRpc()
    {
        include_once dirname(__FILE__).'/SchedulerPhpClient.class.php';
        $this->spc =& SchedulerPhpClient::factory($this->Base->dbc, $mdefs, $this->Base->config);
    }


    function uploadPlaylistMethod(&$formdata)
    {
        $gunid = $formdata['gunid'];
        $datetime = $this->curr['year'].$this->curr['month'].$this->curr['day'].'T'.$formdata['time'];
        #echo $datetime;
        $r = $this->spc->UploadPlaylistMethod($this->Base->sessid, $gunid, $datetime.UI_TIMEZONE);
        #print_r($r);
        if (is_array($r['error']))
            $this->Base->_retMsg('Eroor: $1', $r['error']['message']);
        if (isset($r['scheduleEntryId']))
            $this->Base->_retMsg('ScheduleId: $1', $r['scheduleEntryId']);
    }


    function displayScheduleMethod($from, $to)
    {     #echo $from.$to;
        $r = $this->spc->displayScheduleMethod($this->Base->sessid, $from, $to);
        return $r;
    }
}
?>
