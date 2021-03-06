<?php

class DefaultController extends Controller {

    private $unitPrice = 45;
    public $defaultAction = 'user';
    public $pageTitle = "BRAC Inn Service";

    public function filters() {
        return array(
            'accessControl',
            'postOnly + delete',
        );
    }

    # Service access rules

    public function accessRules() {
        return array(
            array('allow',
                'actions' => array('index', 'access', 'tokens', 'confirm', 'topup', 'servertime'),
                'users' => array('*'),
            ),
            array('allow',
                'actions' => array('user', 'create', 'cancel', 'balance', 'alltokens', 'transfer', 'marketPlaceTransfer', 'list', 'queue', 'buy', 'userQueueAdd', 'userQueueRemove', 'userQueueCount'),
                'users' => array('@'),
            ),
            array('allow',
                'actions' => array('admin'),
                //'users' => array('@'),
                'expression' => 'Supervisor::model()->isSupervisor()',
            ),
            array('deny',
                'users' => array('*'),
            ),
        );
    }

    # Return server date time based on pass format date("Y-m-d H:i:s")

    public function actionServertime($format) {
        date_default_timezone_set('Asia/Dhaka');
        echo CJSON::encode(date($forma));
    }

    # INN admin landing page

    public function actionAdmin() {
        $date_name = $_REQUEST['date_name'];
        if (!$date_name)
            $date_name = date('Y-m-d');

        $criteria = new CDbCriteria;
        $criteria->select = 't.id, t.pin, t.date_time, t.status, t1.name as name';
        $criteria->join = 'left join tbl_users as t1 on t.pin=t1.username';
        $criteria->condition = ' DATE(t.date_time)=:dt';
        $criteria->order = "pin DESC";
        $criteria->params = array(
            //':s' => 0,
            ':dt' => $date_name
        );
        $dataProvider = new CActiveDataProvider('Transaction', array(
            'criteria' => $criteria,
            'pagination' => false
        ));
        $totalToken = Transaction::model()->count(' DATE(date_time)=:dt', array(
            //':s' => 0,
            ':dt' => $date_name
        ));
        $this->render('admin', array(
            'dataProvider' => $dataProvider,
            'totalToken' => $totalToken,
            'date_name' => $date_name
        ));
    }

    # Loggedin users landing page

    public function actionUser() {
        $this->render('user');
    }

    public function actionUserQueueCount() {
        $data = Queue::model()->count('pin =:p and DATE(date_time)=:dt and status=0', array(':p' => Yii::app()->user->name, ':dt' => date('Y-m-d')));
        if ($data) {
            $msg = array(
                'status' => 'success',
                'count' => $data
            );
            echo CJSON::encode($msg);
        }
    }

    # actionUserQueueAdd users landing page

    public function actionUserQueueAdd() {
        $msg = array('status' => 'false');

        $zone = new DateTimeZone("Asia/Dhaka");
        $serverDateTime = new DateTime("now", $zone);

        $count = Queue::model()->count('pin =:p and DATE(date_time)=:dt and status=0', array(':p' => Yii::app()->user->name, ':dt' => date('Y-m-d')));
        $userAccount = UserAccount::model()->find(array('condition' => 'pin = :p', 'params' => array(':p' => Yii::app()->user->name)));
        $userBalance = $userAccount->balance;

        if ($userBalance >= ((intval($count) + 1) * $this->unitPrice)) {

            # check if any marketplace token exists
            $criteria = new CDbCriteria();
            $criteria->condition = 'DATE(date_time) = :dt AND status = :st ';
            $criteria->order = "DATE(date_time) ASC";
            $criteria->limit = 1;
            $criteria->params = array(
                ':dt' => $serverDateTime->format('Y-m-d'),
                ':st' => -1
            );
            
            $transaction = Transaction::model()->find($criteria);

            if ($transaction) {
                
                $from_pin = $transaction->pin;
                
                $transaction->sender = $transaction->pin;
                $transaction->status = 0;
                $transaction->pin = Yii::app()->user->name;
                $transaction->date_time = $serverDateTime->format('Y-m-d H:i:s');
                $transaction->save();

                $from_ua = UserAccount::model()->find('pin=:p', array(':p' => $from_pin));
                $to_ua = $userAccount;

                # update from balance
                $to_balance = $to_ua->balance - $this->unitPrice;
                $to_ua->balance = $to_balance;
                $to_ua->update();
                # update to balance
                
                $from_balance = $from_ua->balance + $this->unitPrice;
                $from_ua->balance = $from_balance;
                $from_ua->update();
                
                $msg = array(
                    'status' => 'success',
                    'id' => $transaction->id,
                    'date_time' => $serverDateTime->format('Y-m-d'),
                    'cause' => 'pre-queue-added',
                    'balance' => $to_balance
                );
                
                //echo CJSON::encode($msg);
                
                $hrdata = new HrdService;
                $loopup = $hrdata->getHrUser($from_pin);
                $user = $loopup[0];
                
                
                if (!empty($user['Email'])) {
                    $model = new stdClass();
                    $model->from_pin = $from_pin;
                    $model->from_email = $user['Email'];
                    $model->from_name = $user['Fname'] . " " . $user['Mname'] . " " . $user['Lname'];
                    $this->sendMailIcress($model->from_email, "Lunch Token Sold [myDesk]", "_email_sell", $model);
                }

                $hrdata1 = new HrdService;
                $loopup1 = $hrdata1->getHrUser(Yii::app()->user->name);
                $user1 = $loopup1[0];

                //CVarDumper::dump($user1);

                if (!empty($user1['Email'])) {
                    $model1 = new stdClass();
                    $model1->from_pin = Yii::app()->user->name;
                    $model1->from_email = $user1['Email'];
                    $model1->from_name = $user1['Fname'] . " " . $user1['Mname'] . " " . $user1['Lname'];
                    $this->sendMailIcress($model1->from_email, "Lunch Token Sold [myDesk]", "_email_preorder", $model1);
                }
            } else {
                $userQueue = new Queue();
                $userQueue->pin = Yii::app()->user->name;
                $userQueue->count = 1;
                $userQueue->date_time = $serverDateTime->format('Y-m-d H:i:s');
                $userQueue->status = 0;
                if ($userQueue->save()) {
                    $msg = array(
                        'status' => 'success',
                        'cause' => 'queue-added'
                    );
                    Yii::log("mLunch::QUEUE-ADD Success - PIN: " . $userQueue->pin . "DATETIME:" . $userQueue->date_time);
                } else {
                    throw new CHttpException(503, 'Failed to save queue. Try Again');
                }
            }
        }
        echo CJSON::encode($msg);
    }

    # actionUserQueueRemove users landing page

    public function actionUserQueueRemove() {
        $data = Queue::model()->find('pin =:p and DATE(date_time)=:dt', array(':p' => Yii::app()->user->name, ':dt' => date('Y-m-d')));
        if ($data) {
            if ($data->delete()) {
                $msg = array(
                    'status' => 'success',
                    'cause' => 'queue-removed'
                );
            }
            echo CJSON::encode($msg);
        }
    }

    # KIOSK landing page

    public function actionIndex() {
        $this->render('index');
    }

    # KIOSK Login

    public function actionAccess() {
        $token = $_GET['secret'];
        $pin = $_GET['pin'];
        if (md5("mydesk.ict.brac.net") == $token && !empty($pin)) {
            if ($this->getSetApplicationUser($pin)) {
                $identity = new UserIdentity($pin);
                $identity->authenticate();
                if ($identity->errorCode === UserIdentity::ERROR_NONE) {
                    Yii::app()->user->login($identity, 0);
                    Yii::log("mLunch::ACCESS Success - PIN: " . $pin . " HTTP_REFERER: " . $_SERVER['HTTP_REFERER'] . " HTTP_HOST: " . $_SERVER['HTTP_HOST'] . " HTTP_USER_AGENT: " . $_SERVER['HTTP_USER_AGENT'] . " REMOTE_ADDR: " . $_SERVER['REMOTE_ADDR'] . " REMOTE_HOST: " . $_SERVER['REMOTE_HOST'] . " REQUEST_URI: " . $_SERVER['REQUEST_URI']);
                } else {
                    echo $identity->errorCode;
                    Yii::log("mLunch::ACCESS Error - CODE: " . $identity->errorCode . " PIN: " . $pin . " HTTP_REFERER: " . $_SERVER['HTTP_REFERER'] . " HTTP_HOST: " . $_SERVER['HTTP_HOST'] . " HTTP_USER_AGENT: " . $_SERVER['HTTP_USER_AGENT'] . " REMOTE_ADDR: " . $_SERVER['REMOTE_ADDR'] . " REMOTE_HOST: " . $_SERVER['REMOTE_HOST'] . " REQUEST_URI: " . $_SERVER['REQUEST_URI']);
                }
            }
        } else {
            throw new CHttpException(503, 'Invalid access code.');
        }
    }

    # bKash API gateway through dot net team

    public function actionTopup() {
        $data = CJSON::decode(Yii::app()->request->rawBody);
        $pins = "";
        $tins = array();
        $soapClient = new SoapClient("http://dataservice.brac.net:800/StaffInfo.asmx?wsdl", array('proxy_login' => "", 'proxy_password' => ""));
        if (md5("mydesk.ict.brac.net") == $data['SecurityToken']) {
            $loop = $data['Transactions'];

            foreach ($loop as $key => $value) {
                $t = Sync::model()->findByPk($value['TransId']);
                if ($t) {
                    $f = new stdClass();
                    $f->TransId = $value['TransId'];
                    //array_push($tins, $f);
                } else {

                    if ($value['Sender'] != "") {
                        $ws = $soapClient->__call('StaffInfoByWalletNo', array(array('mobile' => trim($value['Sender']))));
                        $d = CJSON::decode($ws->StaffInfoByWalletNoResult);

                        if (!empty($d)) {
                            $pin = ltrim($d[0]['PIN'], '0');
                            $email = ltrim($d[0]['Email'], '0');

                            if ($pin != "") {
                                $pins .= $pin . ", ";
                                $ua = UserAccount::model()->find(array('condition' => 'pin = :p', 'params' => array(':p' => $pin)));

                                if ($ua) {
                                    $oldBalance = $ua->balance;
                                    $ua->balance = $ua->balance + $value['Amount'];
                                    $ua->wallet = trim($value['Sender']);
                                    $ua->save();

                                    $sync = new Sync;
                                    $sync->trans_id = $value['TransId'];
                                    $sync->pin = $pin;
                                    $sync->wallet = trim($value['Sender']);
                                    $sync->date_time = date("Y-m-d H:i:s");
                                    $sync->type = 1; // 1 credit 0 debit
                                    $sync->amount = $value['Amount'];
                                    $sync->save();

                                    Yii::log("mLunch::TOPUP - PIN: " . $pin . " WALLET: " . $ua->wallet . " AMOUNT: " . $value['Amount'] . " TRANS: " . $value['TransId'] . " BALANCE_OLD: " . $oldBalance . " BALANCE_NEW: " . $ua->balance);
                                } else {
                                    $ua = new UserAccount;
                                    $ua->pin = $pin;
                                    $ua->balance = $value['Amount'];
                                    $ua->wallet = trim($value['Sender']);
                                    $ua->save();

                                    $sync = new Sync;
                                    $sync->trans_id = $value['TransId'];
                                    $sync->pin = $pin;
                                    $sync->wallet = trim($value['Sender']);
                                    $sync->date_time = date("Y-m-d H:i:s");
                                    $sync->type = 1; // 1 credit 0 debit
                                    $sync->amount = $value['Amount'];
                                    $sync->save();

                                    Yii::log("mLunch::TOPUP - PIN: " . $pin . " WALLET: " . $ua->wallet . " AMOUNT: " . $value['Amount'] . " TRANS: " . $value['TransId'] . " BALANCE_OLD: 0  BALANCE_NEW: " . $ua->balance);
                                }

                                if (!empty($email)) {
                                    $model = new stdClass();
                                    $model->balance = $value['Amount'];
                                    $this->sendMailIcress($email, "myDesk Deposit Notification", "_email_deposit", $model);
                                }
                            }
                        } else {
                            $f = new stdClass();
                            $f->TransId = $value['TransId'];
                            array_push($tins, $f);
                        }
                    }
                }
            }
        }
        if ($pins != "") {
            $msg = array(
                'Success' => true,
                'Message' => $pins . " has successful transactions",
                'Failed' => $tins
            );
            echo CJSON::encode($msg);
        } else {
            $msg = array(
                'Success' => false,
                'Message' => " Failed to made any transactions",
                'Failed' => array()
            );
            echo CJSON::encode($msg);
        }
    }

    # Buy new token

    public function actionCreate() {
        $msg = array();
        $zone = new DateTimeZone("Asia/Dhaka");

        $serverDateTime = new DateTime("now", $zone);
        $serverDate = new DateTime($serverDateTime->format('Y-m-d'));

        $bookingDate = new DateTime($_POST['date_time'], $zone);
        $bookingPin = $_POST['pin'];

        $diff = $serverDate->diff($bookingDate);

        if ($diff->days == 0) {
            # Today 
            $msg = array(
                'status' => 'invalid',
                'cause' => 'day-problem',
            );
        } else {
            # Tomorrow or more but not back
            if ($diff->days == 1 && $diff->invert == 0) {
                # Tomorrow                
                if (date("Hi") > "1730") {
                    # Tomorrow After 5:30
                    $msg = array(
                        'status' => 'failed',
                        'cause' => 'tomorrow-time-problem',
                    );
                } else {
                    # Tomorrow Before 5:30
                    $ua = UserAccount::model()->find(array('condition' => 'pin = :p', 'params' => array(':p' => Yii::app()->user->name)));
                    if ($ua->balance >= $this->unitPrice) {
                        $oldBalance = $ua->balance;
                        $t = new Transaction();
                        $t->pin = Yii::app()->user->name;
                        $t->date_time = $_POST['date_time'] . " " . date("H:i:s");
                        $t->count = 1;
                        $t->status = 0;
                        $t->save();

                        $balance = $ua->balance - $this->unitPrice;
                        $ua->balance = $balance;
                        $ua->update();

                        $msg = array(
                            'status' => 'success',
                            'balance' => $balance,
                            'serial' => $t->id
                        );
                        Yii::log("mLunch::BOOKING - PIN: " . $t->pin . " ID " . $t->id . " DATE_TIME: " . $t->date_time . " BALANCE_OLD: " . $oldBalance . " BALANCE_NEW: " . $ua->balance);
                    } else {
                        $msg = array(
                            'status' => 'invalid',
                            'cause' => 'balance-problem',
                            'balance' => $balance,
                            'unitePrice' => $this->unitPrice
                        );
                    }
                }
            } else {
                # More than Tomorrow
                if ($diff->invert == 0) {
                    # But no more previous
                    $ua = UserAccount::model()->find(array('condition' => 'pin = :p', 'params' => array(':p' => Yii::app()->user->name)));
                    if ($ua->balance >= $this->unitPrice) {
                        $oldBalance = $ua->balance;
                        $t = new Transaction();
                        $t->pin = Yii::app()->user->name;
                        $t->date_time = $_POST['date_time'] . " " . date("H:i:s");
                        $t->count = 1;
                        $t->status = 0;
                        $t->save();

                        $balance = $ua->balance - $this->unitPrice;
                        $ua->balance = $balance;
                        $ua->update();

                        $msg = array(
                            'status' => 'success',
                            'balance' => $balance,
                            'serial' => $t->id
                        );
                        Yii::log("mLunch::BOOKING - PIN: " . $t->pin . " ID " . $t->id . " DATE_TIME: " . $t->date_time . " BALANCE_OLD: " . $oldBalance . " BALANCE_NEW: " . $ua->balance);
                    } else {
                        $msg = array(
                            'status' => 'invalid',
                            'cause' => 'balance-problem',
                            'balance' => $balance,
                            'unitePrice' => $this->unitPrice
                        );
                    }
                }
            }
        }
        echo CJSON::encode($msg);
    }

    # ALL TOKEN TO RENDER ON CALENDAR

    public function actionAlltokens() {
        $transactions = Transaction::model()->findAll(array('condition' => 'pin = :p AND DATE(date_time) >= DATE(now())  AND status = :s', 'params' => array(':p' => Yii::app()->user->name, ':s' => 0)));
        echo CJSON::encode($transactions);
    }

    # Send Valid Todays Tokens per PIN, For Printing

    public function actionTokens() {
        $t = microtime(true);
        $micro = sprintf("%06d", ($t - floor($t)) * 1000000);
        $d = new DateTime(date('Y-m-d H:i:s.' . $micro, $t));
        Yii::log("Token Request Start: " . $d->format("Y-m-d H:i:s.u"));

        $secret = $_GET['secret'];
        $pin = $_GET['pin'];

        if (md5("mydesk.ict.brac.net") == $secret && !empty($pin)) {
            $dateTime = new DateTime("now", new DateTimeZone('Asia/Dhaka'));
            $mysqldate = $dateTime->format("Y-m-d");
            $transactions = Transaction::model()->findAll(array('condition' => 'pin = :u AND DATE(date_time) = :d AND status = :s', 'params' => array(':u' => $pin, ':d' => $mysqldate, ':s' => 0)));

            $time = time();
            $tokens = array();

            foreach ($transactions as $key => $value) {
                $token = array(
                    'serial' => $value->id,
                    'price' => '45',
                    'date' => $value->date_time,
                );
                array_push($tokens, $token);
            }
            echo CJSON::encode($tokens);

            $t = microtime(true);
            $micro = sprintf("%06d", ($t - floor($t)) * 1000000);
            $d = new DateTime(date('Y-m-d H:i:s.' . $micro, $t));
            Yii::log("Token Request Start: " . $d->format("Y-m-d H:i:s.u"));

            Yii::log("mLunch::PRINT REQUEST - PIN: " . $pin . " RESULT: " . CJSON::encode($tokens));
        } else {
            throw new CHttpException(503, 'Invalid access code.');
        }
    }

    # TOKEN USED

    public function actionConfirm() {

        $secret = $_GET['secret'];
        $pin = $_GET['pin'];
        $serial = $_GET['serial'];

        if (md5("mydesk.ict.brac.net") == $secret && !empty($pin)) {
            $transaction = Transaction::model()->findByPk($serial);
            $transaction->status = "1";
            $transaction->update();

            $msg = array(
                'status' => 'success'
            );
            echo CJSON::encode($msg);

            Yii::log("mLunch::PRINT CONFIRMED - PIN: " . $pin . " ID: " . $serial);
        } else {
            $msg = array(
                'status' => 'failed',
                'cause' => 'empty pin'
            );

            echo CJSON::encode($msg);

            Yii::log("mLunch::PRINT FAILED - PIN: " . $pin . " ID: " . $serial);
        }
    }

    # Balance

    public function actionBalance() {
        $balance = UserAccount::model()->find(array('condition' => 'pin = :p', 'params' => array(':p' => Yii::app()->user->name)))->balance;
        $msg = array(
            'status' => 'success',
            'balance' => $balance
        );
        echo CJSON::encode($msg);
    }

    # Marketplace

    public function actionQueue() {
        date_default_timezone_set('Asia/Dhaka');
        $currentTime = date("Hi");
        if ($currentTime > "1430") {
            $msg = array(
                'status' => 'timesup',
                'queue' => null
            );
        } else {
            $transactions = Transaction::model()->findAll(array('condition' => 'DATE(date_time) = DATE(now()) AND status = :s', 'params' => array(':s' => -1)));
            if ($transactions) {
                $msg = array(
                    'status' => 'available',
                    'queue' => $transactions
                );
            } else {
                $msg = array(
                    'status' => 'empty',
                    'queue' => null
                );
            }
        }
        echo CJSON::encode($msg);
    }

    # Cancel any token

    public function actionCancel() {
        $serial = $_POST['serial'];
        $msg = array();
        $transaction = Transaction::model()->find(array('condition' => 'id=:i AND status=:s AND pin=:p', 'params' => array(':i' => $serial, ':s' => 0, ':p' => Yii::app()->user->name)));

        if ($transaction) {
            $msg = array();
            $zone = new DateTimeZone("Asia/Dhaka");

            $serverDateTime = new DateTime("now", $zone);
            $serverDate = new DateTime($serverDateTime->format('Y-m-d'));

            $tokenDateTime = new DateTime($transaction->date_time, $zone);
            $tokenDate = new DateTime($tokenDateTime->format('Y-m-d'));

            $tomorrowDateTime = new DateTime('tomorrow', $zone);
            $tomorrowDate = new DateTime($tomorrowDateTime->format('Y-m-d'));

            $diff = $serverDate->diff($tokenDate);

            if ($diff->days == 0) {                
                # Today 
                if ($serverDateTime->format("Hi") > "1430") { // 1330
                    # Today 1:30 passed
                    $msg = array(
                        'status' => 'failed',
                        'cause' => 'time-up'
                    );
                } else if ($serverDateTime->format("Hi") <= "1430") { // 1330
                    # Today before 1:30
                    # User Queue update
                    $criteria = new CDbCriteria();
                    $criteria->condition = 'DATE(date_time) = DATE(now()) AND status = 0 AND pin != ' . $transaction->pin;
                    $criteria->order = "id ASC";
                    $criteria->limit = 1;

                    $userQueue = Queue::model()->find($criteria);
                    
                    if ($userQueue) {
                        # users
                        $from_pin = $transaction->pin;
                        $to_pin = $userQueue->pin;

                        # user account
                        $from_ua = UserAccount::model()->find('pin=:p', array(':p' => $from_pin));
                        $to_ua = UserAccount::model()->find('pin=:p', array(':p' => $to_pin));
                                                

                        if ($to_ua && $to_ua->balance >= $this->unitPrice) {                            
                            # update transaction
                            $transaction->sender = $from_pin;
                            $transaction->pin = $to_pin;
                            $transaction->save();
                            # update queue
                            $userQueue->status = 1;
                            $userQueue->save();
                            # update from balance
                            $to_balance = $to_ua->balance - $this->unitPrice;
                            $to_ua->balance = $to_balance;
                            $to_ua->update();
                            # update to balance
                            $from_balance = $from_ua->balance + $this->unitPrice;
                            $from_ua->balance = $from_balance;
                            $from_ua->update();

                            $msg = array(
                                'status' => 'success',
                                'cause' => 'queue-sold',
                                'balance' => $from_ua->balance
                            );

                            $hrdata = new HrdService;
                            $loopup = $hrdata->getHrUser($from_pin);
                            $user = $loopup[0];

                            if (!empty($user['Email'])) {
                                $model = new stdClass();
                                $model->from_pin = $from_pin;
                                $model->from_email = $user['Email'];
                                $model->from_name = $user['Fname'] . " " . $user['Mname'] . " " . $user['Lname'];
                                $this->sendMailIcress($model->from_email, "Lunch Token Sold [myDesk]", "_email_sell", $model);
                            }

                            $hrdata1 = new HrdService;
                            $loopup1 = $hrdata1->getHrUser($to_pin);
                            $user1 = $loopup1[0];

                            //CVarDumper::dump($user1);

                            if (!empty($user1['Email'])) {
                                $model1 = new stdClass();
                                $model1->from_pin = $to_pin;
                                $model1->from_email = $user1['Email'];
                                $model1->from_name = $user1['Fname'] . " " . $user1['Mname'] . " " . $user1['Lname'];
                                $this->sendMailIcress($model1->from_email, "Lunch Token Sold [myDesk]", "_email_preorder", $model1);
                            }
                            
                            Yii::log("mLunch::CANCEL TO QUEUE - ID: " . $serial . " DATE_TIME: " . $serverDateTime . " FROM PIN: " . $transaction->pin . " TO PIN: " . $userQueue->pin . " Form Balance" . $from_ua->balance . "To Balance" . $to_ua->balance);                            
                        }
                    } else {
                        # Otherwise elegble for marketplace
                        $transaction->status = -1;
                        $transaction->save();
                        $msg = array(
                            'status' => 'success',
                            'cause' => 'marketplace',
                        );
                        Yii::log("mLunch::CANCEL TO MARKET - ID: " . $serial . " DATE_TIME: " . $serverDateTime . " PIN: " . $transaction->pin);
                    }
                }
            } else {
                # Tomorrow or more
                if ($diff->days == 1) {
                    # Tomorrow
                    if (date("Hi") > "1730") {
                        # Tomorrow after 5:30
                        //$tomorrowDateTime->format('Y-m-d')

                        $criteria = new CDbCriteria();
                        $criteria->condition = 'DATE(date_time) = "' . $tomorrowDateTime->format('Y-m-d') . '" AND status = 0 AND pin != ' . $transaction->pin;
                        $criteria->order = "id ASC";
                        $criteria->limit = 1;

                        $userQueue = Queue::model()->find($criteria);

                        //if(Supervisor::model()->isSuperSupervisor()) CVarDumper::dump ($userQueue);
                        if ($userQueue) {
                            # users
                            $from_pin = $transaction->pin;
                            $to_pin = $userQueue->pin;

                            # user account
                            $from_ua = UserAccount::model()->find('pin=:p', array(':p' => $from_pin));
                            $to_ua = UserAccount::model()->find('pin=:p', array(':p' => $to_pin));

                            if ($to_ua && $to_ua->balance >= $this->unitPrice) {
                                # update transaction
                                $transaction->sender = $from_pin;
                                $transaction->pin = $to_pin;
                                $transaction->save();
                                # update queue
                                $userQueue->status = 1;
                                $userQueue->save();
                                # update from balance
                                $to_balance = $to_ua->balance - $this->unitPrice;
                                $to_ua->balance = $to_balance;
                                $to_ua->update();
                                # update to balance
                                $from_balance = $from_ua->balance + $this->unitPrice;
                                $from_ua->balance = $from_balance;
                                $from_ua->update();

                                $msg = array(
                                    'status' => 'success',
                                    'cause' => 'queue-sold',
                                    'more' => 'tomorrow',
                                );

                                $hrdata = new HrdService;
                                $loopup = $hrdata->getHrUser($from_pin);
                                $user = $loopup[0];

                                if (!empty($user['Email'])) {
                                    $model = new stdClass();
                                    $model->from_pin = $from_pin;
                                    $model->from_email = $user['Email'];
                                    $model->from_name = $user['Fname'] . " " . $user['Mname'] . " " . $user['Lname'];
                                    $this->sendMailIcress($model->from_email, "Lunch Token Sold [myDesk]", "_email_sell", $model);
                                }

                                $hrdata1 = new HrdService;
                                $loopup1 = $hrdata1->getHrUser($to_pin);
                                $user1 = $loopup[0];

                                if (!empty($user1['Email'])) {
                                    $model1 = new stdClass();
                                    $model1->from_pin = $to_pin;
                                    $model1->from_email = $user1['Email'];
                                    $model1->from_name = $user1['Fname'] . " " . $user1['Mname'] . " " . $user1['Lname'];
                                    $this->sendMailIcress($model->from_email, "Lunch Token Sold [myDesk]", "_email_preorder", $model1);
                                }

                                Yii::log("mLunch::CANCEL TO QUEUE - ID: " . $serial . " DATE_TIME: " . $serverDateTime . " FROM PIN: " . $transaction->pin . " TO PIN: " . $userQueue->pin . " Form Balance" . $from_ua->balance . "To Balance" . $to_ua->balance);
                            }
                        } else {
                            # Otherwise elegble for marketplace
                            $transaction->status = -1;
                            $transaction->save();
                            $msg = array(
                                'status' => 'success',
                                'cause' => 'marketplace',
                                'more' => 'tomorrow',
                            );
                            Yii::log("mLunch::CANCEL TO MARKET - ID: " . $serial . " DATE_TIME: " . $serverDateTime . " PIN: " . $transaction->pin);
                        }
                    } else {
                        # Tomorrow before 5:30
                        $ua = UserAccount::model()->find(array('condition' => 'pin=:p', 'params' => array(':p' => $transaction->pin)));
                        if ($ua) {
                            $oldBalance = $ua->balance;
                            $balance = $ua->balance;
                            $balance+=$this->unitPrice;
                            $ua->balance = $balance;
                            $ua->update();
                            $transaction->delete();
                            $msg = array(
                                'status' => 'success',
                                'balance' => $balance
                            );
                        } else {
                            $ua = new UserAccount;
                            $ua->pin = Yii::app()->user->name;
                            $oldBalance = "00.0";
                            $balance = $this->unitPrice;
                            $ua->balance = $balance;
                            $ua->save();
                            $transaction->delete();
                            $msg = array(
                                'status' => 'success',
                                'balance' => $balance
                            );
                        }

                        Yii::log("mLunch::CANCEL - ID: " . $serial . " PIN: " . $transaction->pin . " DATE_TIME: " . $transaction->date_time . " CANCEL_TIME: " . $serverDateTime->format('Y-m-d H:i') . " BALANCE_OLD: " . $oldBalance . " BALANCE_NEW: " . $ua->balance);
                    }
                } else {
                    # More than tomorrow
                    if ($diff->invert == 0) {
                        # but not previous
                        $ua = UserAccount::model()->find(array('condition' => 'pin=:p', 'params' => array(':p' => $transaction->pin)));
                        $oldBalance = $ua->balance;
                        $balance = $ua->balance;
                        $balance+=$this->unitPrice;
                        $ua->balance = $balance;
                        $ua->update();
                        $transaction->delete();
                        $msg = array(
                            'status' => 'success',
                            'balance' => $balance
                        );
                        Yii::log("mLunch::CANCEL - ID: " . $serial . " PIN: " . $transaction->pin . " DATE_TIME: " . $serverDateTime->format('Y-m-d H:i') . " BALANCE_OLD: " . $oldBalance . " BALANCE_NEW: " . $ua->balance);
                    } else {
                        $msg = array(
                            'status' => 'failed',
                            'cause' => 'time-invert'
                        );
                    }
                }
            }
        } else {
            $msg = array(
                'status' => 'failed',
                'cause' => 'invalid-token'
            );
        }

        echo CJSON::encode($msg);
    }

    # Transfer

    public function actionTransfer() {

        $serial = $_POST['serial'];
        $to_pin = $_POST['to_pin'];
        $msg = array();
        //$t = Transaction::model()->findByPk($serial);
        $t = Transaction::model()->find(array('condition' => 'id=:i AND status=:s AND pin=:p', 'params' => array(':i' => $serial, ':s' => 0, ':p' => Yii::app()->user->name)));
        $from_pin = null;
        $user = null;
        $tokenTime = null;

        if ($t) {
            $from_pin = $t->pin;

            if ($t->pin == $to_pin) {
                $msg = array(
                    'status' => 'failed',
                    'cause' => 'same-pin'
                );
            } else {
                $hrdata = new HrdService;
                $loopup = $hrdata->getHrUser($to_pin);
                $user = $loopup[0];

                if (!empty($user)) {
                    $zone = new DateTimeZone("Asia/Dhaka");
                    $serverDateTime = new DateTime("now", $zone);
                    $serverDate = new DateTime($serverDateTime->format('Y-m-d'), $zone);
                    $tokenTime = new DateTime($t->date_time, $zone);
                    $diff = $serverDate->diff($tokenTime);

                    if ($diff->days > 0) {
                        $t->pin = $to_pin;
                        $t->sender = $from_pin;
                        $t->save();
                        $msg = array(
                            'status' => 'success',
                            'serial' => $serial,
                        );
                        Yii::log("mLunch::TRANSFER - FROM: " . $t->sender . " TO: " . $t->pin . " ID: " . $serial . " DATE_TIME: " . $serverDateTime);
                    } else if ($diff->days == 0) {
                        if ($serverDateTime->format("Hi") > "1430") {
                            $msg = array(
                                'status' => 'failed',
                                'cause' => 'time-up',
                            );
                        } else if ($serverDateTime->format("Hi") <= "1430") {
                            $t->pin = $to_pin;
                            $t->sender = $from_pin;
                            $t->save();
                            $msg = array(
                                'status' => 'success',
                                'serial' => $serial,
                            );
                            Yii::log("mLunch::TRANSFER - FROM: " . $t->sender . " TO: " . $t->pin . " ID: " . $serial . " DATE_TIME: " . $serverDateTime . " | HTTP_REFERER: " . $_SERVER['HTTP_REFERER'] . " HTTP_HOST: " . $_SERVER['HTTP_HOST'] . " HTTP_USER_AGENT: " . $_SERVER['HTTP_USER_AGENT'] . " REMOTE_ADDR: " . $_SERVER['REMOTE_ADDR'] . " REMOTE_HOST: " . $_SERVER['REMOTE_HOST'] . " REQUEST_URI: " . $_SERVER['REQUEST_URI']);
                        }
                    }
                } else {
                    $msg = array(
                        'status' => 'failed',
                        'cause' => 'invalid-pin'
                    );
                }
            }
        } else {
            $msg = array(
                'status' => 'failed',
                'cause' => 'invalid-token'
            );
        }

        if ($msg['status'] == "success" && !empty($user['Email'])) {
            $model = new stdClass();
            $model->from_pin = $from_pin;
            $model->from_name = $this->bracUser['Fname'] . " " . $this->bracUser['Mname'] . " " . $this->bracUser['Lname'];
            $model->to_email = $user['Email'];
            $model->to_pin = $to_pin;
            $model->to_name = $user['Fname'] . " " . $user['Mname'] . " " . $user['Lname'];
            $model->date = $tokenTime->format('Y-m-d');
            $this->sendMailIcress($model->to_email, "Lunch Token Transfered [myDesk]", "_email_transfer", $model);
        }
        echo CJSON::encode($msg);
    }

    # Buy from marketplace

    public function actionBuy() {

        $msg = array();
        $serial = $_POST['serial'];
        $to_pin = Yii::app()->user->name;
        $t = Transaction::model()->findByPk($serial);
        $from_pin = $t->pin;

        if ($t && $t->status == -1) {
            if ($to_pin == $from_pin) {
                $t->status = 0;
                $t->save();
                $msg = array(
                    'status' => 'revert',
                    'id' => $t->id,
                    'date_time' => $t->date_time
                );
            } else {
                $ua = UserAccount::model()->find('pin=:p', array(':p' => $to_pin));
                $ub = UserAccount::model()->find('pin=:p', array(':p' => $from_pin));

                if ($ua && $ua->balance >= $this->unitPrice) {
                    $t->pin = $to_pin;
                    $t->status = 0;
                    $t->count = 1;
                    $t->save();

                    $balance = $ua->balance - $this->unitPrice;
                    $ua->balance = $balance;
                    $ua->update();

                    $balance = $ub->balance + $this->unitPrice;
                    $ub->balance = $balance;
                    $ub->update();

                    $msg = array(
                        'status' => 'success',
                        'id' => $t->id,
                        'date_time' => $t->date_time,
                        'balance' => $ua->balance
                    );
                } else {
                    $msg = array(
                        'status' => 'failed',
                        'cause' => 'balance-problem'
                    );
                }

                $hrdata = new HrdService;
                $loopup = $hrdata->getHrUser($from_pin);
                $user = $loopup[0];

                if ($msg['status'] == "success" && !empty($user['Email'])) {
                    $model = new stdClass();
                    $model->from_pin = $from_pin;
                    $model->from_email = $user['Email'];
                    $model->from_name = $user['Fname'] . " " . $user['Mname'] . " " . $user['Lname'];
                    $this->sendMailIcress($model->from_email, "myDesk: Lunch Token Sell", "_email_sell", $model);
                }
            }
        } else {
            $msg = array(
                'status' => 'failed',
                'cause' => 'not-exists',
                'serial' => $serial
            );
        }
        echo CJSON::encode($msg);
    }

    # Required for bypass SSO login

    private function getSetApplicationUser($user) {
        $find = User::model()->findByAttributes(array('username' => $user));
        if ($find) {
            return true;
        } else {
            $b = new User;
            $b->username = $user;
            $b->password = UserModule::encrypting("br@c#1231321");

            $hrdata = new HrdService;
            $model1 = $hrdata->getHrUser($user);
            $b->email = $model1[0]['Email'];

            if (empty($b->email))
                $b->email = $b->username . '@bracsso.net';

            $b->activkey = UserModule::encrypting(microtime() . $pass);
            $b->create_at = date('Y-m-d H:i:s');
            $b->lastvisit_at = date('Y-m-d H:i:s');
            $b->superuser = 0;
            $b->status = 1;
            $b->save();

            $c = new Profile;
            $c->user_id = $b->id;
            $c->lastname = $user;
            $c->firstname = $user;
            $c->save();
            return true;
        }
        return false;
    }

    # Send isoap email API

    public function sendMailIcress($to, $sub, $template, $model) {
        try {
            $soapClient = new SoapClient("http://172.25.100.41:8080/isoap.comm.imail/EmailWS?wsdl");
            $job = new jobs;
            $job->jobContentType = 'html';
            $job->fromAddress = 'mydesk@brac.net';
            $job->udValue1 = 'myDesk';
            $job->requester = 'myDesk';
            $job->jobRecipients[0] = new jobRecipients;
            $job->jobRecipients[0]->recipientEmail = $to;
            $job->subject = $sub;
            $job->body = $this->renderPartial($template, array('model' => $model, 'model1' => $model1, 'rec_mail' => $rec_mail), true);
            $send_email = $soapClient->__call('sendEmail', array(array('jobs' => $job)));
        } catch (SoapFault $fault) {
            $error = 1;
            //print($fault->faultcode . "-" . $fault->faultstring);
            echo '<div class="ui error message">BRAC HRD HRMS server seems down. Please call for support at 4400(HRD) 3200(ICT)</div>';
        }
    }

}

class jobs {

    public $appUserId; // string
    public $attachments; // attachment
    public $bcc; // string
    public $body; // string
    public $caption; // string
    public $cc; // string
    public $complete; // boolean
    public $feedbackDate; // dateTime
    public $feedbackEmail; // string
    public $feedbackName; // string
    public $feedbackSent; // boolean
    public $fromAddress; // string
    public $fromText; // string
    public $gateway; // string
    public $jobContentType; // string
    public $jobId; // long
    public $jobRecipients; // jobRecipients
    public $mode; // string
    public $numberOfItem; // int
    public $numberOfItemFailed; // int
    public $numberOfItemSent; // int
    public $priority; // string
    public $requester; // string
    public $status; // string
    public $subject; // string
    public $toAddress; // string
    public $toText; // string
    public $udValue1; // string
    public $udValue2; // string
    public $udValue3; // string
    public $udValue4; // string
    public $udValue5; // string
    public $udValue6; // string
    public $udValue7; // string
    public $vtemplate; // string

}

class jobRecipients {

    public $failCount; // int
    public $image; // base64Binary
    public $job; // jobs
    public $jobDetailId; // long
    public $recipientEmail; // string
    public $sent; // boolean
    public $sentDate; // dateTime
    public $toText; // string

}
