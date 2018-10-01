<?php
namespace  rongqiangqin\mailerqueue;
use Yii;
class Message extends \yii\swiftmailer\Message{

    public function queue()
    {
        $redis=Yii::$app->redis;
        if(empty($redis)){
            throw  new \yii\base\InvalidConfigException('redis not found in config.');
        }

        $mailer=Yii::$app->mailer;

        if(empty($mailer) || !$redis->select($mailer->db)){
            throw  new \yii\base\InvalidConfigException('db not defined.');
        }


        //print_r($this->getBcc());
        //exit;
        $message=[];
        $message['from'] = array_keys($this->from);
        $message['to'] = array_keys($this->getTo());
        if($this->getBcc()){
            $message['cc'] = array_keys($this->getCc());
        }
        if($this->getBcc()){
            $message['bcc'] = array_keys($this->getBcc());
        }
        if($this->getReplyTo()){
            $message['reply_to'] = array_keys($this->getReplyTo());
        }
        if($this->getCharset()){
            $message['charset'] = array_keys($this->getCharset());
        }
        if($this->getSubject()){
            $message['subject'] = $this->getSubject();
        }

        $parts=$this->getSwiftMessage()->getChildren();

        if(!is_array($parts) || !sizeof($parts)){
            $parts=[$this->getSwiftMessage()];
        }
        foreach($parts as $part){
            if(!$part instanceof  \Swift_Mime_Attachment){
                switch ($part->getContentType()){
                    case 'text/html':
                        $message['html_body']=$part->getBody();
                        break;
                    case 'text/plain':
                        $message['text_body']=$part->getBody();
                        break;
                }
                if(!isset($message['charset'])){
                    $message['charset']=$part->getCharset();
                }
            }
        }
        return $redis->rpush($mailer->key,json_encode($message));
    }
}