<?php

class ParserTask extends TaskBase {
  
  public function indexAction(){
    
    $fileList = $this->getFileList('/files/uploads/*');
    
    $stockBalanceList = array();
    $parserObj = new ClearParser();
    $parserObj->parseBatch($fileList);
    
//    prexit($parserObj->getStockBalance());
    prexit($parserObj->getTradeList());
  }
  
  private function printStockBalance($stockBalanceList, $lastTradingDay) {
    echo chr(10);
    
    ksort($stockBalanceList);
    foreach ($stockBalanceList as $stock => $balanceInfo) {
      
      $price = bcdiv($balanceInfo['value'], $balanceInfo['quantity'], 2);
      echo $this->coloredString(sprintf('%s%50s%- 25s % 61s% 16s% 16s%s', $lastTradingDay, '', $stock, $balanceInfo['quantity'], $price, $balanceInfo['value'], chr(10)), 'green');
    }
    echo chr(10);
  }
}