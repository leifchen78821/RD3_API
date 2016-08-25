<?php

header("Content-Type:text/html; charset=utf-8");
$db = new PDO("mysql:host=localhost;dbname=RD3testAPI", "root", "");
$db->exec("SET CHARACTER SET utf8");

$url = $_SERVER['REQUEST_URI'];
$urlback = explode("/",$url);
$nameAPI = explode("?",$urlback[4]);

/**
 * 建立帳號
 */
if ($nameAPI[0] == "createMember" && isset($_GET["userName"])) {
    // 判斷有無重複帳號
    $sql = "SELECT `userName` FROM `userName`";
    $prepare = $db->prepare($sql);
    $prepare->execute();
    $result = $prepare->fetchALL(PDO::FETCH_ASSOC);
    foreach($result as $list) {
        if ($_GET["userName"] == $list["userName"]) {
            $message = array("userName" => $_GET["userName"], "errorMessage" => "Repeat Account!!");
            echo json_encode($message);
            exit;
        }
    }

    // 新增會員
    $sql = "INSERT INTO `userName` " .
            "(`userName`)" .
            "VALUES " .
            "(:userName);";
    $prepare = $db->prepare($sql);
    $prepare->bindParam(':userName', $_GET["userName"]);
    $prepare->execute();

    // A平台加10萬
    $sql = "INSERT INTO `transferPlatformA` " .
            "(`userName`, `TransactionId`, `action`, `moneyInPlatformA`)" .
            "VALUES " .
            "(:userName, 0, 100000, 100000);";
    $prepare = $db->prepare($sql);
    $prepare->bindParam(':userName', $_GET["userName"]);
    $prepare->execute();
    // B平台加0
    $sql = "INSERT INTO `transferPlatformB` " .
            "(`userName`, `TransactionId`, `action`, `moneyInPlatformB`)" .
            "VALUES " .
            "(:userName, 0, 0, 0);";
    $prepare = $db->prepare($sql);
    $prepare->bindParam(':userName', $_GET["userName"]);
    $prepare->execute();

    $message = array("userName" => $_GET["userName"], "message" => "Create Member Success!!");
    echo json_encode($message);
    exit;

/**
 * 查詢餘額(A平台)
 */
} elseif ($nameAPI[0] == "checkBalanceA" && isset($_GET["userName"])) {
    $sql = "SELECT `moneyInPlatformA` FROM `transferPlatformA` WHERE `userName` = :userName ORDER BY `aID` DESC";
    $prepare = $db->prepare($sql);
    $prepare->bindParam(':userName', $_GET["userName"]);
    $prepare->execute();
    $result = $prepare->fetchALL(PDO::FETCH_ASSOC);

    if ($result[0]["moneyInPlatformA"] != NULL) {
        $message = array("userName" => $_GET["userName"], "BalanceInPlatformA" => $result[0]["moneyInPlatformA"]);
        echo json_encode($message);
        exit;
    } else {
        $message = array("userName" => $_GET["userName"], "errorMessage" => "userName not found!!");
        echo json_encode($message);
        exit;
    }

/**
 * 查詢餘額(B平台)
 */
} elseif ($nameAPI[0] == "checkBalanceB" && isset($_GET["userName"])) {
    $sql = "SELECT `moneyInPlatformB` FROM `transferPlatformB` WHERE `userName` = :userName ORDER BY `bID` DESC";
    $prepare = $db->prepare($sql);
    $prepare->bindParam(':userName', $_GET["userName"]);
    $prepare->execute();
    $result = $prepare->fetchALL(PDO::FETCH_ASSOC);

    if ($result[0]["moneyInPlatformB"] != NULL) {
        $message = array("userName" => $_GET["userName"], "BalanceInPlatformB" => $result[0]["moneyInPlatformB"]);
        echo json_encode($message);
        exit;
    } else {
        $message = array("userName" => $_GET["userName"], "errorMessage" => "userName not found!!");
        echo json_encode($message);
        exit;
    }

/**
 * 檢查轉帳狀態
 */
} elseif ($nameAPI[0] == "checkTransfer" && isset($_GET["userName"]) && isset($_GET["transactionId"])) {
    $sql = "SELECT `transactionId` FROM `transferPlatformA` WHERE `userName` = :userName";
    $prepare = $db->prepare($sql);
    $prepare->bindParam(':userName', $_GET["userName"]);
    $prepare->execute();
    $result = $prepare->fetchALL(PDO::FETCH_ASSOC);
    $transactionResultFormPlatformA = $result[0]["transactionId"];

    $sql = "SELECT `transactionId` FROM `transferPlatformB` WHERE `userName` = :userName";
    $prepare = $db->prepare($sql);
    $prepare->bindParam(':userName', $_GET["userName"]);
    $prepare->execute();
    $result = $prepare->fetchALL(PDO::FETCH_ASSOC);
    $transactionResultFormPlatformB = $result[0]["transactionId"];

    if($transactionResultFormPlatformA != NULL && $transactionResultFormPlatformB != NULL) {
        $message = array("userName" => $_GET["userName"], "transactionId" => $_GET["transactionId"], "message" => "transfer success!!");
        echo json_encode($message);
        exit;
    } else {
        $message = array("userName" => $_GET["userName"], "transactionId" => $_GET["transactionId"], "errorMessage" => "transactionId not found(transfer fail or transactionId is wrong)!!");
        echo json_encode($message);
        exit;
    }

/**
 * 轉帳(A平台)
 */
} elseif ($nameAPI[0] == "transferFromA" && isset($_GET["userName"]) && isset($_GET["transactionId"]) && isset($_GET["action"]) && isset($_GET["money"])) {
    // 檢查有無此帳號
    $checkuserName = 0;
    $sql = "SELECT `userName` FROM `userName`";
    $prepare = $db->prepare($sql);
    $prepare->execute();
    $result = $prepare->fetchALL(PDO::FETCH_ASSOC);
    foreach($result as $list) {
        if($_GET["userName"] == $list["userName"]) {
            $checkuserName = 1;
        }
    }

    if($checkuserName == 1) {
        // 檢查交易序號
        $sql = "SELECT `transactionId` FROM `transferPlatformA` WHERE `userName` = :userName";
        $prepare = $db->prepare($sql);
        $prepare->bindParam(':userName', $_GET["userName"]);
        $prepare->execute();
        $result = $prepare->fetchALL(PDO::FETCH_ASSOC);
        foreach($result as $list) {
            if($_GET["transactionId"] == $list["transactionId"]) {
                $message = array("userName" => $_GET["userName"], "transactionId" => $_GET["transactionId"], "errorMessage" => "transactionId is used!!");
                echo json_encode($message);
                exit;
            }
        }

        try {
            $db->beginTransaction();
            $sql = "SELECT `moneyInPlatformA` FROM `transferPlatformA` WHERE `userName` = :userName ORDER BY `aID` DESC FOR UPDATE";
            $prepare = $db->prepare($sql);
            $prepare->bindParam(':userName', $_GET["userName"]);
            $prepare->execute();
            $result = $prepare->fetch(PDO::FETCH_ASSOC);
            $nowMoney = $result["moneyInPlatformA"];

            if($_GET["action"] == "IN") {
                $sql = "INSERT INTO `transferPlatformA` " .
                    "(`userName`, `transactionId`, `action`, `moneyInPlatformA`)" .
                    "VALUES" .
                    "(:userName, :transactionId, :action, :nowMoney + :action)";
                $prepare = $db->prepare($sql);
                $prepare->bindParam(':userName', $_GET["userName"]);
                $prepare->bindParam(':transactionId', $_GET["transactionId"]);
                $prepare->bindParam(':action', $_GET["money"]);
                $prepare->bindParam(':nowMoney', $nowMoney);
                $prepare->execute();

                $db->commit();

                $message = array("userName" => $_GET["userName"], "transactionId" => $_GET["transactionId"], "action" => $_GET["action"], "money" => $_GET["money"], "message" => "transfer success!!");
                echo json_encode($message);
                exit;

            } elseif ($_GET["action"] == "OUT") {

                if($nowMoney >= $_GET["money"]) {
                    $sql = "INSERT INTO `transferPlatformA` " .
                        "(`userName`, `transactionId`, `action`, `moneyInPlatformA`)" .
                        "VALUES" .
                        "(:userName, :transactionId, - :action, :nowMoney - :action)";
                    $prepare = $db->prepare($sql);
                    $prepare->bindParam(':userName', $_GET["userName"]);
                    $prepare->bindParam(':transactionId', $_GET["transactionId"]);
                    $prepare->bindParam(':action', $_GET["money"]);
                    $prepare->bindParam(':nowMoney', $nowMoney);
                    $prepare->execute();

                    $db->commit();

                    $message = array("userName" => $_GET["userName"], "transactionId" => $_GET["transactionId"], "action" => $_GET["action"], "money" => $_GET["money"], "message" => "transfer success!!");
                    echo json_encode($message);
                    exit;
                } else {
                    $message = array("userName" => $_GET["userName"], "transactionId" => $_GET["transactionId"], "errorMessage" => "money you have is not enough!!");
                    echo json_encode($message);
                    $db->rollback();
                    exit;
                }

            } else {
                $message = array("userName" => $_GET["userName"], "transactionId" => $_GET["transactionId"], "errorMessage" => "action is wrong!!");
                echo json_encode($message);
                $db->rollback();
                exit;
            }
        } catch (Exception $err) {
            $db->rollback();
            $message = array("userName" => $_GET["userName"], "transactionId" => $_GET["transactionId"], "errorMessage" => "transfer fail!!");
            echo json_encode($message);
            exit;
        }
    } else {
        $message = array("userName" => $_GET["userName"], "transactionId" => $_GET["transactionId"], "errorMessage" => "userName not found!!");
        echo json_encode($message);
        exit;
    }

/**
 * 轉帳(B平台)
 */
} elseif($nameAPI[0] == "transferFromB" && isset($_GET["userName"]) && isset($_GET["transactionId"]) && isset($_GET["action"]) && isset($_GET["money"])) {
    // 檢查有無此帳號
    $checkuserName = 0;
    $sql = "SELECT `userName` FROM `userName`";
    $prepare = $db->prepare($sql);
    $prepare->execute();
    $result = $prepare->fetchALL(PDO::FETCH_ASSOC);
    foreach($result as $list) {
        if($_GET["userName"] == $list["userName"]) {
            $checkuserName = 1;
        }
    }

    if($checkuserName == 1) {
        // 檢查交易序號
        $sql = "SELECT `transactionId` FROM `transferPlatformB` WHERE `userName` = :userName";
        $prepare = $db->prepare($sql);
        $prepare->bindParam(':userName', $_GET["userName"]);
        $prepare->execute();
        $result = $prepare->fetchALL(PDO::FETCH_ASSOC);
        foreach($result as $list) {
            if($_GET["transactionId"] == $list["transactionId"]) {
                $message = array("userName" => $_GET["userName"], "transactionId" => $_GET["transactionId"], "errorMessage" => "transactionId is used!!");
                echo json_encode($message);
                exit;
            }
        }

        try {
            $db->beginTransaction();
            $sql = "SELECT `moneyInPlatformB` FROM `transferPlatformB` WHERE `userName` = :userName ORDER BY `bID` DESC FOR UPDATE";
            $prepare = $db->prepare($sql);
            $prepare->bindParam(':userName', $_GET["userName"]);
            $prepare->execute();
            $result = $prepare->fetch(PDO::FETCH_ASSOC);
            $nowMoney = $result["moneyInPlatformB"];

            if($_GET["action"] == "IN") {
                $sql = "INSERT INTO `transferPlatformB` " .
                    "(`userName`, `transactionId`, `action`, `moneyInPlatformB`)" .
                    "VALUES" .
                    "(:userName, :transactionId, :action, :nowMoney + :action)";
                $prepare = $db->prepare($sql);
                $prepare->bindParam(':userName', $_GET["userName"]);
                $prepare->bindParam(':transactionId', $_GET["transactionId"]);
                $prepare->bindParam(':action', $_GET["money"]);
                $prepare->bindParam(':nowMoney', $nowMoney);
                $prepare->execute();

                $db->commit();

                $message = array("userName" => $_GET["userName"], "transactionId" => $_GET["transactionId"], "action" => $_GET["action"], "money" => $_GET["money"], "message" => "transfer success!!");
                echo json_encode($message);
                exit;

            } elseif ($_GET["action"] == "OUT") {

                if($nowMoney >= $_GET["money"]) {
                    $sql = "INSERT INTO `transferPlatformB` " .
                        "(`userName`, `transactionId`, `action`, `moneyInPlatformB`)" .
                        "VALUES" .
                        "(:userName, :transactionId, - :action, :nowMoney - :action)";
                    $prepare = $db->prepare($sql);
                    $prepare->bindParam(':userName', $_GET["userName"]);
                    $prepare->bindParam(':transactionId', $_GET["transactionId"]);
                    $prepare->bindParam(':action', $_GET["money"]);
                    $prepare->bindParam(':nowMoney', $nowMoney);
                    $prepare->execute();

                    $db->commit();

                    $message = array("userName" => $_GET["userName"], "transactionId" => $_GET["transactionId"], "action" => $_GET["action"], "money" => $_GET["money"], "message" => "transfer success!!");
                    echo json_encode($message);
                    exit;
                } else {
                    $message = array("userName" => $_GET["userName"], "transactionId" => $_GET["transactionId"], "errorMessage" => "money you have is not enough!!");
                    echo json_encode($message);
                    $db->rollback();
                    exit;
                }

            } else {
                $message = array("userName" => $_GET["userName"], "transactionId" => $_GET["transactionId"], "errorMessage" => "action is wrong!!");
                echo json_encode($message);
                $db->rollback();
                exit;
            }
        } catch (Exception $err) {
            $db->rollback();
            $message = array("userName" => $_GET["userName"], "transactionId" => $_GET["transactionId"], "errorMessage" => "transfer fail!!");
            echo json_encode($message);
            exit;
        }
    } else {
        $message = array("userName" => $_GET["userName"], "transactionId" => $_GET["transactionId"], "errorMessage" => "userName not found!!");
        echo json_encode($message);
        exit;
    }

} else {
    $message = array("errorMessage" => "input error!!");
    echo json_encode($message);
    exit;
}
?>