<?php

trait rhythmStorm
{
    public function rhythmStormStart($data)
    {
        $this->log('Storm:' . $data['msg'], 'blue', 'SOCKET');
        if ($data['hadJoin'] == '0' && $data['num'] > 0) {
            //TODO  节奏风暴暂时搁置
            //$msg = $this->sendMsg($data);
            //TODO 暂时打印数据
            var_dump($msg);
            $this->log('Storm:参加成功', 'cyan', 'SOCKET');

        } else {
            $this->log('Storm:节奏风暴结束或者数量为0', 'red', 'SOCKET');
        }
    }
}
