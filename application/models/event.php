<?php
class Model_event extends model {

    private $db_settings = array(
        'db_host'=>'localhost',
        'db_name' => 'calendar',
        'db_charset' => 'utf8',
        'db_user' => 'root',
        'db_password' => '12345',
    );

    protected $_connection;

    private $status_names = array(
        '0' => 'Active',
        '1' => 'Completed',
        '2' => 'Canceled',
        '3' => 'Postponed',
    );

    public function connect()
    {
        if ($this->_connection)
            return;

        $dsn = "mysql:host={$this->db_settings['db_host']};dbname={$this->db_settings['db_name']};charset={$this->db_settings['db_charset']}";
        $opt = array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        );

        $this->_connection = new PDO($dsn, $this->db_settings['db_user'], $this->db_settings['db_password'], $opt);
    }

    function get_status()
    {
        return $this->status_names;
    }

    function add_event( $data )
    {
        $this->_connection or $this->connect();
        $sql_data = $this->_get_sql_data($data);
        $cmd = $this->_connection->prepare('insert into event( t_start, t_end, status, title) values (:t_start, :t_end, :status, :title)');
        $cmd->execute($sql_data);
        return $this->_connection->lastInsertId();
    }

    function update_event($id, $data)
    {
        $this->_connection or $this->connect();
        $sql_data = $this->_get_sql_data($data);
        $sql_data['id'] = $id;
        $cmd = $this->_connection->prepare('update event set t_start=:t_start, t_end=:t_end, status=:status, title=:title where id=:id');
        $cmd->execute($sql_data);
        return $cmd->rowCount();
    }

    function exist_event($data)
    {
        $this->_connection or $this->connect();
        $t_start = strtotime($data['t_start'], $data['day_stamp']);
        $t_end = strtotime($data['t_end'], $data['day_stamp']);
        $cmd = $this->_connection->prepare('select 1 from event where
          (t_start = :t_start and t_end = :t_end and id != :id) or
          (t_start > :t_start and t_start < :t_end) or
          (t_end > :t_start and t_end < :t_end) or
          (:t_start > t_start and :t_end < t_end)
          ');
        $cmd->execute(array('t_start'=>$t_start, 't_end'=>$t_end, 'id'=>$data['id']));
        return $cmd->fetch();
    }

    function get_event($id)
    {
        $this->_connection or $this->connect();
        $cmd = $this->_connection->prepare('select * from event where id = :id');
        $cmd->execute(array('id'=>$id));
        $res = $cmd->fetch();
        return $res;
    }

    function get_events($start_stamp, $end_stamp)
    {
        $this->_connection or $this->connect();
        $cmd = $this->_connection->prepare('select * from event where t_start >= :start_stamp and t_end <= :end_stamp order by t_start');
        $cmd->execute(array('start_stamp'=>$start_stamp, 'end_stamp'=>$end_stamp));

        $res = array();
        while($row = $cmd->fetch()) {
            $day = date("j",$row['t_start']);
            $res[$day][] = $row;
        }
        return $res;
    }

    private function _get_sql_data($data)
    {
        $sql_data['t_start'] = strtotime($data['t_start'], $data['day_stamp']);
        $sql_data['t_end'] = strtotime($data['t_end'], $data['day_stamp']);
        $sql_data['title'] = $data['title'];
        $sql_data['status'] = $data['status'];
        return $sql_data;
    }
}
