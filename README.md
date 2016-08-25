#本API使用規則:
#
#API位址:<br>https://test20160620-leif-chen.c9users.io/_testAPI/Q6/Q6_API.php/API名稱?參數=值
#ex.建立帳號:<br>https://test20160620-leif-chen.c9users.io/_testAPI/Q6/Q6_API.php/createMember?userName=LeifChen
#
#1.建立帳號
#
#API名稱:createMember
#參數1:userName(帳號) = (varchar)
#p.s.帳號建立後，自動在A平台新增100,000，B平台新增0，交易序號為0
#
#2.查詢餘額
#
#A平台API名稱:checkBalanceA
#B平台API名稱:checkBalanceB
#參數1:userName(帳號) = (varchar)
#
#3.檢查轉帳狀態
#
#API名稱:checkTransfer
#參數1:userName(帳號) = (varchar)
#參數2:transactionId(交易序號) = (int)
#
#4.轉帳
#
#A平台轉帳API名稱:transferFromA
#B平台轉帳API名稱:transferFromB
#參數1:userName(帳號) = (varchar)
#參數2:transactionId(交易序號) = (int(50))
#參數3:action(轉帳動作) = IN/OUT
#參數4:money(轉帳金額) = (int)