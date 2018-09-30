<?php
/**
 * Created by PhpStorm.
 * User: qinrongqiang
 * Date: 2018/10/1
 * Time: 上午6:05
 */
namespace  rongqiangqin\mailerqueue;
use Yii;
class MailerQueue extends \yii\swiftmailer\Mailer{
    public $messageClass="rongqiangqin\mailerqueue\Message";
    public $key="mails";
    public $db='1';

    public function process()
    {
        $redis=Yii::$app->redis;
        if(empty($redis)){
            throw  new \yii\base\InvalidConfigException('redis not found in config.');
        }
        if($this->redis->selct($this->db && $messages=$redis->lrange($this->key,0,-1))){
            $messagesObj=new Message();
            foreach($messages as $message){
                $message=json_decode($message,true);
                if(empty($message) || $this->setMessage($messagesObj,$message)){
                    throw  new \ServerErrorHttpException('message error.');
                }
                if($messagesObj->send()){
                    //执行成功后删除
                    $redis->lrem($this->key,-1,json_encode($message));
                }
            }
        }
        return true;
    }

    public function setMessage($messagesObj,$message)
    {
        if(empty($messagesObj)){
            return false;
        }
        if(!empty($message['from']) && !empty($message['to'])){
            $messagesObj->setFrom($message['from'])->setTo($message['to']);
            if(!empty($message['cc'])){
                $messagesObj->setCc($message['cc']);
            }
            if(!empty($message['bcc'])){
                $messagesObj->setcCc($message['bcc']);
            }
            if(!empty($message['reply_to'])){
                $messagesObj->setReplyTo($message['reply_to']);
            }
            if(!empty($message['charset'])){
                $messagesObj->setCharset($message['charset']);
            }
            if(!empty($message['subject'])){
                $messagesObj->setSubject($message['subject']);
            }
            if(!empty($message['html_body'])){
                $messagesObj->setHtmlBody($message['html_body']);
            }
            if(!empty($message['text_body'])){
                $messagesObj->setTextBody($message['text_body']);
            }
            return $messagesObj;
        }
        return false;

    }
}