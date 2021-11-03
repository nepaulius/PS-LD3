<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use phpseclib\Net\SSH2;
use phpseclib\File\ANSI;
use Illuminate\Support\Str;
use App\Models\UserNotification;

class Service extends Model
{
    public function action($action, $output = false) {
        $data = json_decode($this->data);

        $template = $this->template;
        if (!$template) {
            return json_encode(["status" => "error", "message" => "Template not found."]);
        }
        $steps = json_decode($template->data, true)[$action];

        if(!$steps) {
            return json_encode(["status" => "error", "message" => "Action not found."]);
        }

        $node = $this->node;
        if (!$node) {
            return json_encode(["status" => "error", "message" => "Node not found."]);
        }

        $ssh = new SSH2($node->ip, $node->ssh_port);
        if (!$ssh->login($node->ssh_login, $node->ssh_password)) {
            return json_encode(["status" => "error", "message" => "Can not connect to node."]);
        }

        $ansi = new ANSI();

        foreach ($steps as $step) {
            $step = str_replace("<user>", "s".$this->id, $step);
            $step = str_replace("<password>", $this->password, $step);
            $ssh->write($step . "\n");
            if(Str::startsWith($step, "./")) {
                $ssh->setTimeout(10);
            }
            $ansi->appendString($ssh->read());
        }

        $log = str_replace("\n", "<br>", $ansi->getHistory());

        if($output){
            foreach (json_decode($template->data, true)["_panel"] as $item){
                if($item['action'] == $action && $item['substr_from']) {
                    $offset = strpos($log, $item['substr_from']) + strlen($item['substr_from']);
                    $log = substr($log, $offset);
                }
            }
        }

        $log = "<pre width='80' style='color:white; background: black;'>" . $log;
        $log = str_replace("s" . $this->id . "@" . $node->ssh_host . ":~$", "", $log);

        return json_encode(
            [
                "status" => "success",
                "message" => "Action performed successfully.",
                "data" => ($output || auth()->user()->admin ? $log : "")
            ]
        );
    }

}
